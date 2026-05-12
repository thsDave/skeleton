<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Session;
use Core\Validator;
use Core\Audit;
use Core\Logger;
use App\Models\AuthenticationSettings;
use App\Models\User;
use App\Models\PasswordReset;
use App\Services\Mailer;
use App\Services\PasswordPolicyService;
use App\Services\UserSessionService;

class PasswordResetController extends Controller
{
    private User $userModel;
    private PasswordReset $resetModel;

    public function __construct()
    {
        $this->userModel  = new User();
        $this->resetModel = new PasswordReset();
    }

    // ─── GET /forgot-password ─────────────────────────────────────────────────

    public function showForgotForm(): void
    {
        Auth::requireGuest();
        $this->view('auth.forgot_password');
    }

    // ─── POST /forgot-password ────────────────────────────────────────────────

    public function sendResetLink(): void
    {
        Auth::requireGuest();
        CSRF::validateOrFail();

        $email = trim($this->input('email', ''));

        $validator = new Validator();
        $validator->required('email', $email, 'Correo electrónico')
                  ->email('email', $email);

        if ($validator->fails()) {
            Redirect::withErrors('/forgot-password', $validator->errors(), ['email' => $email]);
        }

        // ── Restricción de dominio institucional ──────────────────────────────────
        if (!(new AuthenticationSettings())->isDomainAllowed($email)) {
            $emailDomain = strtolower(substr($email, (int) strrpos($email, '@') + 1));
            Logger::security("PasswordResetController::sendResetLink — domain not allowed: {$emailDomain}");
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.domain_denied',
                'description' => "Recuperación de contraseña rechazada por dominio no autorizado: {$emailDomain}",
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Session::flash('info', __('auth.reset_link_generic_message'));
            Redirect::to('/forgot-password');
        }

        $maxRequests      = (int) env('PASSWORD_RESET_MAX_REQUESTS', 3);
        $rateLimitMinutes = (int) env('PASSWORD_RESET_RATE_LIMIT_MINUTES', 15);

        // Rate limit — no revelar que el correo existe
        $recent = $this->resetModel->countRecentRequests($email, $rateLimitMinutes);
        if ($recent >= $maxRequests) {
            Logger::security("Password reset rate limited for: {$email}");
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.rate_limited',
                'description' => "Rate limit alcanzado para: {$email}",
                'status'      => 'warning',
                'user_id'     => null,
            ]);
            Session::flash('info', __('auth.reset_link_generic_message'));
            Redirect::to('/forgot-password');
        }

        $user = $this->userModel->findByEmail($email);

        // No revelar si el correo existe o el usuario está inactivo
        if (!$user || ($user['status_slug'] ?? '') !== 'active') {
            Logger::security("Password reset for unknown/inactive email: {$email}");
            Session::flash('info', __('auth.reset_link_generic_message'));
            Redirect::to('/forgot-password');
        }

        // Invalidar tokens anteriores no usados
        $this->resetModel->invalidatePreviousTokens($user['id']);

        // Generar token seguro
        $token          = bin2hex(random_bytes(32));
        $tokenHash      = hash('sha256', $token);
        $expiresMinutes = (int) env('PASSWORD_RESET_TOKEN_EXPIRATION_MINUTES', 60);
        $expiresAt      = date('Y-m-d H:i:s', time() + $expiresMinutes * 60);

        $this->resetModel->create([
            'user_id'    => $user['id'],
            'email'      => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);

        Audit::log([
            'module'      => 'password_reset',
            'action'      => 'password_reset.requested',
            'entity'      => 'user',
            'entity_id'   => $user['id'],
            'description' => 'Solicitud de recuperación de contraseña',
            'status'      => 'info',
            'user_id'     => null,
        ]);

        // Construir URL y plantilla del correo
        $appUrl   = rtrim((string) env('APP_URL', ''), '/');
        $resetUrl = $appUrl . '/reset-password/' . $token;
        $appName  = (string) env('APP_NAME', 'Skeleton');
        $userName = trim($user['nombres'] . ' ' . $user['apellidos']);

        // Ajustar idioma del correo al idioma del usuario
        $savedLocale = Session::get('user_lang', 'es');
        \Core\Lang::setLocale($user['lang_code'] ?? 'es');

        ob_start();
        require dirname(__DIR__, 2) . '/app/Views/emails/password_reset.php';
        $htmlBody = ob_get_clean();

        \Core\Lang::setLocale($savedLocale);

        $subject   = $appName . ' — ' . __('mail.password_reset_subject');
        $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $sent = Mailer::send($email, $userName, $subject, $htmlBody, $plainBody);

        if ($sent) {
            Logger::security("Password reset email sent to user ID {$user['id']}");
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.email_sent',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => 'Correo de recuperación enviado',
                'status'      => 'success',
                'user_id'     => null,
            ]);
        } else {
            Logger::error("Password reset email failed for user ID {$user['id']}");
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.email_failed',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => 'Fallo al enviar correo de recuperación',
                'status'      => 'failed',
                'user_id'     => null,
            ]);
        }

        // Siempre mostrar mensaje genérico
        Session::flash('info', __('auth.reset_link_generic_message'));
        Redirect::to('/forgot-password');
    }

    // ─── GET /reset-password/{token} ─────────────────────────────────────────

    public function showResetForm(string $token): void
    {
        Auth::requireGuest();

        if (!$this->isValidTokenFormat($token)) {
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.token_invalid',
                'description' => 'Token con formato inválido en GET',
                'status'      => 'warning',
                'user_id'     => null,
            ]);
            Session::flash('error', __('auth.reset_token_invalid'));
            Redirect::to('/forgot-password');
        }

        $tokenHash = hash('sha256', $token);
        $record    = $this->resetModel->findValidByTokenHash($tokenHash);

        if (!$record) {
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.token_invalid',
                'description' => 'Token no encontrado, expirado o ya usado en GET',
                'status'      => 'warning',
                'user_id'     => null,
            ]);
            Session::flash('error', __('auth.reset_token_invalid'));
            Redirect::to('/forgot-password');
        }

        $policyReqs = (new PasswordPolicyService())->getRequirements();
        $this->view('auth.reset_password', ['token' => $token, 'policyReqs' => $policyReqs]);
    }

    // ─── POST /reset-password ────────────────────────────────────────────────

    public function resetPassword(): void
    {
        Auth::requireGuest();
        CSRF::validateOrFail();

        $token           = trim($this->input('token', ''));
        $newPassword     = $this->input('new_password', '');
        $confirmPassword = $this->input('confirm_password', '');

        // Validar formato de token antes de cualquier otra cosa
        if (!$this->isValidTokenFormat($token)) {
            Session::flash('error', __('auth.reset_token_invalid'));
            Redirect::to('/forgot-password');
        }

        $validator = new Validator();
        $validator->required('new_password', $newPassword, 'Nueva contraseña')
                  ->required('confirm_password', $confirmPassword, 'Confirmar contraseña')
                  ->matches('confirm_password', $confirmPassword, $newPassword);

        if ($validator->fails()) {
            Redirect::withErrors('/reset-password/' . $token, $validator->errors());
        }

        $tokenHash = hash('sha256', $token);
        $record    = $this->resetModel->findValidByTokenHash($tokenHash);

        if (!$record) {
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.token_invalid',
                'description' => 'Token inválido o expirado en POST',
                'status'      => 'warning',
                'user_id'     => null,
            ]);
            Session::flash('error', __('auth.reset_token_invalid'));
            Redirect::to('/forgot-password');
        }

        $userId = (int)$record['user_id'];
        $user   = $this->userModel->findById($userId);

        // ── Política de contraseñas ───────────────────────────────────────────
        $policySvc    = new PasswordPolicyService();
        $policyResult = $policySvc->validate($newPassword, [
            'email'     => $user['email']     ?? '',
            'nombres'   => $user['nombres']   ?? '',
            'apellidos' => $user['apellidos'] ?? '',
        ]);
        if (!$policyResult['valid']) {
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.policy_validation_failed',
                'entity'      => 'user',
                'entity_id'   => $userId,
                'description' => 'Restablecimiento rechazado por política de contraseñas',
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/reset-password/' . $token, ['new_password' => implode(' ', $policyResult['errors'])]);
        }
        if ($policySvc->isPasswordReused($newPassword, $userId)) {
            Audit::log([
                'module'      => 'password_reset',
                'action'      => 'password_reset.history_reuse_blocked',
                'entity'      => 'user',
                'entity_id'   => $userId,
                'description' => 'Restablecimiento rechazado por reutilización de contraseña',
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/reset-password/' . $token, ['new_password' => __('password_policy.error_reused', ['count' => (string)(int)($policySvc->getPolicy()['password_history_count'] ?? 3)])]);
        }

        // Actualizar contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userModel->updatePassword($userId, $hashedPassword);
        $policySvc->saveHistory($userId, $hashedPassword);

        // Marcar token como usado + invalidar otros tokens del mismo usuario
        $this->resetModel->markAsUsed((int)$record['id']);
        $this->resetModel->invalidatePreviousTokens($userId);
        (new UserSessionService())->revokeAllUserSessions($userId, null, 'password_reset');

        Logger::security("Password reset completed for user ID {$userId}");
        Audit::log([
            'module'      => 'password_reset',
            'action'      => 'password_reset.completed',
            'entity'      => 'user',
            'entity_id'   => $userId,
            'description' => 'Contraseña restablecida mediante enlace de recuperación',
            'status'      => 'success',
            'user_id'     => null,
        ]);

        $this->view('auth.reset_success');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function isValidTokenFormat(string $token): bool
    {
        return strlen($token) === 64 && ctype_xdigit($token);
    }
}
