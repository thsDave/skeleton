<?php

namespace App\Controllers;

use App\Models\AuthenticationSettings;
use App\Models\EmailChangeVerification;
use App\Models\ExternalAuthProvider;
use App\Models\SmtpSettings;
use App\Models\User;
use App\Models\UserExternalAccount;
use App\Services\Mailer;
use App\Services\PasswordPolicyService;
use App\Services\UserSessionService;
use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\Logger;
use Core\Redirect;
use Core\Session;
use Core\Validator;

class AccountController extends Controller
{
    private User $userModel;
    private EmailChangeVerification $emailChangeModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->emailChangeModel = new EmailChangeVerification();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $authUser         = Auth::user();
        $user             = $this->userModel->findById(Auth::id());
        $authSettings     = (new AuthenticationSettings())->get();
        $enabledProviders = (new ExternalAuthProvider())->allEnabled();
        $linkedAccounts   = (new UserExternalAccount())->allForUser(Auth::id());
        $this->view('account.index',
            compact('authUser', 'user', 'authSettings', 'enabledProviders', 'linkedAccounts'));
    }

    public function editEmail(): void
    {
        Auth::requireAuth();
        $authUser = Auth::user();
        $user = $this->userModel->findById(Auth::id());
        $pending = $this->emailChangeModel->findPendingForUser(Auth::id());
        $maskedPendingEmail = $pending ? $this->maskEmail($pending['new_email']) : null;
        $this->view('account.edit_email', compact('authUser', 'user', 'pending', 'maskedPendingEmail'));
    }

    public function updateEmail(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/account');
        }

        CSRF::validateOrFail();

        $email = trim($this->input('email', ''));
        $id    = Auth::id();
        $user  = $this->userModel->findById($id);

        if (!$user || ($user['status_slug'] ?? '') !== 'active') {
            Logger::security("Email change rejected for inactive/missing user ID {$id}");
            Audit::log([
                'module'      => 'account',
                'action'      => 'account.email_change_failed',
                'entity'      => 'user',
                'entity_id'   => $id,
                'description' => 'Solicitud de cambio de correo rechazada: usuario no activo',
                'status'      => 'denied',
            ]);
            Redirect::withError('/account/edit-email', __('account.email_change_error'));
        }

        $validator = new Validator();
        $validator->required('email', $email, __('account.new_email'))
                  ->email('email', $email)
                  ->maxLength('email', $email, 150, __('account.new_email'));

        if ($validator->fails()) {
            Redirect::withErrors('/account/edit-email', $validator->errors(), ['email' => $email]);
        }

        if (strcasecmp($email, (string) $user['email']) === 0) {
            Redirect::withErrors('/account/edit-email', ['email' => __('account.email_change_same_email')], ['email' => $email]);
        }

        if ($this->userModel->emailExists($email, $id)) {
            Redirect::withErrors('/account/edit-email', ['email' => __('account.email_change_email_exists')], ['email' => $email]);
        }

        if (!(new AuthenticationSettings())->isDomainAllowed($email)) {
            Logger::security("Email change domain denied for user ID {$id}");
            Audit::log([
                'module'      => 'account',
                'action'      => 'account.email_change_failed',
                'entity'      => 'user',
                'entity_id'   => $id,
                'description' => 'Solicitud de cambio de correo rechazada por dominio no permitido',
                'old_values'  => ['email' => $user['email']],
                'new_values'  => ['email' => $email],
                'status'      => 'denied',
            ]);
            Redirect::withErrors('/account/edit-email', ['email' => __('account.email_change_domain_not_allowed')], ['email' => $email]);
        }

        if (!$this->isSmtpReady()) {
            Logger::error("Email change rejected because SMTP is unavailable for user ID {$id}");
            Audit::log([
                'module'      => 'account',
                'action'      => 'account.email_change_failed',
                'entity'      => 'user',
                'entity_id'   => $id,
                'description' => 'Solicitud de cambio de correo rechazada: SMTP no disponible',
                'status'      => 'failed',
            ]);
            Redirect::withError('/account/edit-email', __('account.email_change_smtp_required'));
        }

        if (!$this->createAndSendEmailChangeCode($user, $email, false)) {
            Redirect::withError('/account/edit-email', __('account.email_change_error'));
        }

        Redirect::withSuccess('/account/email/verify', __('account.email_change_code_sent'));
    }

    public function verifyEmailChangeForm(): void
    {
        Auth::requireAuth();
        $authUser = Auth::user();
        $pending = $this->emailChangeModel->findPendingForUser(Auth::id());

        if (!$pending) {
            Session::flash('error', __('account.email_change_no_pending'));
            Redirect::to('/account/edit-email');
        }

        $maskedEmail = $this->maskEmail($pending['new_email']);
        $this->view('account.verify_email', compact('authUser', 'pending', 'maskedEmail'));
    }

    public function verifyEmailChange(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $userId = Auth::id();
        $code = preg_replace('/\D+/', '', (string) $this->input('code', ''));
        $pending = $this->emailChangeModel->findPendingForUser($userId);

        if (!$pending) {
            Logger::security("Email change verification without pending request - user ID {$userId}");
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Verificacion de cambio de correo sin solicitud pendiente',
                'status' => 'failed',
            ]);
            Redirect::withError('/account/edit-email', __('account.email_change_no_pending'));
        }

        if ($this->emailChangeModel->isExpired($pending)) {
            $this->emailChangeModel->markUsed((int) $pending['id']);
            Logger::security("Expired email change code - user ID {$userId}");
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Codigo de cambio de correo expirado',
                'old_values' => ['email' => $pending['current_email']],
                'new_values' => ['email' => $pending['new_email']],
                'status' => 'failed',
            ]);
            Redirect::withError('/account/email/verify', __('account.email_change_expired_code'));
        }

        if ($this->emailChangeModel->hasTooManyAttempts($pending)) {
            $this->emailChangeModel->markUsed((int) $pending['id']);
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Cambio de correo bloqueado por demasiados intentos',
                'old_values' => ['email' => $pending['current_email']],
                'new_values' => ['email' => $pending['new_email']],
                'status' => 'denied',
            ]);
            Redirect::withError('/account/edit-email', __('account.email_change_too_many_attempts'));
        }

        if (strlen($code) !== 6 || !password_verify($code, $pending['code_hash'])) {
            $this->emailChangeModel->incrementAttempts((int) $pending['id']);
            $updatedAttempts = (int) $pending['attempts'] + 1;
            Logger::security("Invalid email change code - user ID {$userId}, attempt {$updatedAttempts}");
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Codigo de cambio de correo incorrecto',
                'old_values' => ['email' => $pending['current_email']],
                'new_values' => ['email' => $pending['new_email'], 'attempts' => $updatedAttempts],
                'status' => 'failed',
            ]);

            if ($updatedAttempts >= $this->emailChangeModel->maxAttempts()) {
                $this->emailChangeModel->markUsed((int) $pending['id']);
                Redirect::withError('/account/edit-email', __('account.email_change_too_many_attempts'));
            }

            Redirect::withErrors('/account/email/verify', ['code' => __('account.email_change_invalid_code')]);
        }

        $user = $this->userModel->findById($userId);
        if (!$user || ($user['status_slug'] ?? '') !== 'active') {
            Redirect::withError('/account/edit-email', __('account.email_change_error'));
        }
        if ($this->userModel->emailExists($pending['new_email'], $userId)) {
            $this->emailChangeModel->markUsed((int) $pending['id']);
            Redirect::withError('/account/edit-email', __('account.email_change_email_exists'));
        }
        if (!(new AuthenticationSettings())->isDomainAllowed($pending['new_email'])) {
            $this->emailChangeModel->markUsed((int) $pending['id']);
            Redirect::withError('/account/edit-email', __('account.email_change_domain_not_allowed'));
        }

        try {
            $this->emailChangeModel->completeEmailChange((int) $pending['id'], $userId, $pending['new_email']);
            Auth::updateSession(['email' => $pending['new_email']]);
            $closedSessions = (new UserSessionService())->revokeOtherSessionsForUser((int)$userId, 'email_changed', (int)$userId);
            Logger::security("Email change completed - user ID {$userId}");
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_verified',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Codigo de cambio de correo verificado',
                'old_values' => ['email' => $pending['current_email']],
                'new_values' => ['email' => $pending['new_email']],
                'status' => 'success',
            ]);
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_completed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Correo electronico actualizado despues de verificacion',
                'old_values' => ['email' => $pending['current_email']],
                'new_values' => ['email' => $pending['new_email']],
                'status' => 'success',
            ]);
            $message = __('account.email_change_success');
            if ($closedSessions > 0) {
                $message .= ' ' . __('sessions.other_sessions_closed');
            }
            Redirect::withSuccess('/account', $message);
        } catch (\Throwable $e) {
            Logger::error('AccountController::verifyEmailChange update failed - ' . $e->getMessage());
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Error tecnico al completar cambio de correo',
                'status' => 'failed',
            ]);
            Redirect::withError('/account/email/verify', __('account.email_change_error'));
        }
    }

    public function resendEmailChangeCode(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $userId = Auth::id();
        $pending = $this->emailChangeModel->findPendingForUser($userId);

        if (!$pending) {
            Logger::security("Email change resend without pending request - user ID {$userId}");
            Redirect::withError('/account/edit-email', __('account.email_change_no_pending'));
        }

        if ($this->emailChangeModel->countRecentRequests($userId, $pending['new_email'], 10) >= 3) {
            Logger::security("Email change resend rate limited - user ID {$userId}");
            Redirect::withError('/account/email/verify', __('account.email_change_resend_limited'));
        }

        $user = $this->userModel->findById($userId);
        if (!$user || ($user['status_slug'] ?? '') !== 'active') {
            Redirect::withError('/account/edit-email', __('account.email_change_error'));
        }

        if (!$this->isSmtpReady()) {
            Redirect::withError('/account/email/verify', __('account.email_change_smtp_required'));
        }

        if (!$this->createAndSendEmailChangeCode($user, $pending['new_email'], true)) {
            Redirect::withError('/account/email/verify', __('account.email_change_error'));
        }

        Redirect::withSuccess('/account/email/verify', __('account.email_change_code_resent'));
    }

    public function cancelEmailChange(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $userId = Auth::id();
        $pending = $this->emailChangeModel->findPendingForUser($userId);
        $this->emailChangeModel->invalidatePendingForUser($userId);

        if ($pending) {
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_cancelled',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Cambio de correo cancelado',
                'old_values' => ['email' => $pending['current_email']],
                'new_values' => ['email' => $pending['new_email']],
                'status' => 'success',
            ]);
        }

        Redirect::withSuccess('/account', __('account.email_change_cancelled'));
    }

    public function editPassword(): void
    {
        Auth::requireAuth();
        $authUser   = Auth::user();
        $user       = $this->userModel->findById(Auth::id());
        $policyReqs = (new PasswordPolicyService())->getRequirements();
        $this->view('account.edit_password', compact('authUser', 'user', 'policyReqs'));
    }

    public function sessions(): void
    {
        Auth::requirePermission('account.sessions.view');
        $authUser = Auth::user();
        $sessions = (new UserSessionService())->getCurrentUserSessions((int)Auth::id());
        $this->view('account.sessions', compact('authUser', 'sessions'));
    }

    public function revokeSession(string $id): void
    {
        Auth::requirePermission('account.sessions.revoke');
        CSRF::validateOrFail();

        $sessionId = (int)$id;
        $service = new UserSessionService();
        $session = $service->findActiveSession($sessionId);

        if (!$session || (int)$session['user_id'] !== (int)Auth::id()) {
            Redirect::withError('/account/sessions', __('sessions.closed_error'));
        }

        $currentHash = $service->getCurrentSessionHash();
        if (hash_equals((string)$session['session_hash'], $currentHash)) {
            Redirect::withError('/account/sessions', __('sessions.closed_error'));
        }

        $ok = $service->revokeSession($sessionId, Auth::id(), 'user_revoke');
        Session::flash($ok ? 'success' : 'error', $ok ? __('sessions.closed_success') : __('sessions.closed_error'));
        Redirect::to('/account/sessions');
    }

    public function revokeOtherSessions(): void
    {
        Auth::requirePermission('account.sessions.revoke');
        CSRF::validateOrFail();

        $count = (new UserSessionService())->revokeOtherSessions((int)Auth::id(), Auth::id(), 'user_revoke_others');
        Session::flash('success', __('sessions.closed_others_success', ['count' => (string)$count]));
        Redirect::to('/account/sessions');
    }

    public function sessionHistory(): void
    {
        Auth::requirePermission('account.sessions.history');

        $authUser = Auth::user();
        $status = in_array($_GET['status'] ?? '', ['active', 'closed'], true) ? $_GET['status'] : '';
        $sessions = (new UserSessionService())->getCurrentUserSessionHistory((int)Auth::id(), ['status' => $status]);

        Audit::log([
            'module' => 'account',
            'action' => 'account.sessions_history_viewed',
            'entity' => 'user',
            'entity_id' => Auth::id(),
            'description' => 'Historial propio de sesiones consultado',
            'new_values' => ['status_filter' => $status ?: 'all'],
            'status' => 'info',
        ]);

        $this->view('account.sessions_history', compact('authUser', 'sessions', 'status'));
    }

    public function revokeHistorySession(string $id): void
    {
        Auth::requirePermission('account.sessions.revoke');
        CSRF::validateOrFail();

        $service = new UserSessionService();
        $session = $service->findSession((int)$id);
        if (!$session || (int)$session['user_id'] !== (int)Auth::id() || !empty($session['revoked_at'])) {
            Redirect::withError('/account/sessions/history', __('sessions.closed_error'));
        }

        if (hash_equals((string)$session['session_hash'], $service->getCurrentSessionHash())) {
            Redirect::withError('/account/sessions/history', __('sessions.closed_error'));
        }

        $ok = $service->revokeSession((int)$id, Auth::id(), 'user_revoke');
        Audit::log([
            'module' => 'sessions',
            'action' => 'sessions.revoked_from_history',
            'entity' => 'user_session',
            'entity_id' => (int)$id,
            'description' => 'Sesion propia revocada desde historial',
            'new_values' => ['user_id' => Auth::id()],
            'status' => $ok ? 'success' : 'failed',
        ]);

        Session::flash($ok ? 'success' : 'error', $ok ? __('sessions.closed_success') : __('sessions.closed_error'));
        Redirect::to('/account/sessions/history');
    }

    public function updatePassword(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/account');
        }

        CSRF::validateOrFail();

        $currentPassword  = $this->input('current_password', '');
        $newPassword      = $this->input('new_password', '');
        $confirmPassword  = $this->input('confirm_password', '');
        $id               = Auth::id();

        $validator = new Validator();
        $validator->required('current_password', $currentPassword, 'Contraseña actual')
                  ->required('new_password', $newPassword, 'Nueva contraseña')
                  ->required('confirm_password', $confirmPassword, 'Confirmar contraseña')
                  ->matches('confirm_password', $newPassword, $confirmPassword);

        if ($validator->fails()) {
            Redirect::withErrors('/account/edit-password', $validator->errors());
        }

        $user = $this->userModel->findById($id);

        if (!password_verify($currentPassword, $user['password'])) {
            Audit::log(['module' => 'account', 'action' => 'account.password_change_failed',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Intento de cambio de contraseña fallido: contraseña actual incorrecta',
                'status' => 'failed']);
            Redirect::withErrors('/account/edit-password', ['current_password' => 'La contraseña actual es incorrecta.']);
        }

        $policySvc    = new PasswordPolicyService();
        $policyResult = $policySvc->validate($newPassword, [
            'email'     => $user['email'],
            'nombres'   => $user['nombres'],
            'apellidos' => $user['apellidos'],
        ]);
        if (!$policyResult['valid']) {
            Audit::log(['module' => 'account', 'action' => 'password_policy.validation_failed',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Cambio de contraseña rechazado por política',
                'status' => 'denied']);
            Redirect::withErrors('/account/edit-password', ['new_password' => implode(' ', $policyResult['errors'])]);
        }
        if ($policySvc->isPasswordReused($newPassword, $id)) {
            Audit::log(['module' => 'account', 'action' => 'password_policy.history_reuse_blocked',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Cambio de contraseña rechazado por reutilización',
                'status' => 'denied']);
            Redirect::withErrors('/account/edit-password', ['new_password' => __('password_policy.error_reused', ['count' => (string)(int)($policySvc->getPolicy()['password_history_count'] ?? 3)])]);
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($this->userModel->updatePassword($id, $hashed)) {
            $policySvc->saveHistory($id, $hashed);
            Session::regenerate();
            $sessionService = new UserSessionService();
            $sessionService->registerCurrentSession((int)$id);
            $closedSessions = $sessionService->revokeOtherSessionsForUser((int)$id, 'password_changed', (int)$id);
            Logger::security("Contraseña actualizada - ID {$id}");
            Audit::log(['module' => 'account', 'action' => 'account.password_changed',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Contraseña de cuenta actualizada',
                'status' => 'success']);
            $message = 'Contraseña actualizada correctamente.';
            if ($closedSessions > 0) {
                $message .= ' ' . __('sessions.other_sessions_closed');
            }
            Redirect::withSuccess('/account', $message);
        } else {
            Redirect::withError('/account/edit-password', 'No se pudo actualizar la contraseña. Intenta de nuevo.');
        }
    }

    public function initiateLink(string $provider): void
    {
        Auth::requireAuth();
        $allowed = \App\Models\ExternalAuthProvider::allowedSlugs();
        if (!in_array($provider, $allowed, true)) {
            Redirect::to('/account');
        }
        Redirect::to('/auth/external/' . $provider . '/redirect?action=link');
    }

    public function unlinkAccount(int $id): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $userId    = Auth::id();
        $linkModel = new UserExternalAccount();
        $link      = $linkModel->findById($id);

        if (!$link || (int) $link['user_id'] !== $userId) {
            Session::flash('error', __('account.unlink_not_found'));
            Redirect::to('/account');
        }

        $settings = (new AuthenticationSettings())->get();
        if (!$settings['local_login_enabled']) {
            $count = $linkModel->countForUser($userId);
            if ($count <= 1) {
                Session::flash('error', __('account.unlink_last_method'));
                Redirect::to('/account');
            }
        }

        if ($linkModel->deleteById($id)) {
            $providerName = $link['provider_display_name'] ?? $link['provider_slug'] ?? '?';
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.account_unlinked',
                'entity'      => 'user',
                'entity_id'   => $userId,
                'description' => "Cuenta externa desvinculada: {$providerName}",
                'status'      => 'success',
                'user_id'     => $userId,
            ]);
            Session::flash('success', __('account.provider_unlinked'));
        } else {
            Logger::error("AccountController::unlinkAccount - failed to delete link id={$id}");
            Session::flash('error', __('account.unlink_error'));
        }

        Redirect::to('/account');
    }

    private function createAndSendEmailChangeCode(array $user, string $newEmail, bool $resent): bool
    {
        $userId = (int) $user['id'];
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresInMinutes = 10;
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));

        $this->emailChangeModel->invalidatePendingForUser($userId);
        $verificationId = $this->emailChangeModel->create([
            'user_id'       => $userId,
            'current_email' => $user['email'],
            'new_email'     => $newEmail,
            'code_hash'     => password_hash($code, PASSWORD_BCRYPT, ['cost' => 12]),
            'expires_at'    => $expiresAt,
        ]);

        if (!$verificationId) {
            Logger::error("Email change verification create failed for user ID {$userId}");
            return false;
        }

        Audit::log([
            'module' => 'account', 'action' => 'account.email_change_requested',
            'entity' => 'user', 'entity_id' => $userId,
            'description' => 'Solicitud de cambio de correo creada',
            'old_values' => ['email' => $user['email']],
            'new_values' => ['email' => $newEmail],
            'status' => 'success',
        ]);

        $appName = (string) env('APP_NAME', 'Skeleton');
        $userName = trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''));

        ob_start();
        require dirname(__DIR__, 2) . '/app/Views/emails/email_change_code.php';
        $htmlBody = ob_get_clean();

        $subject = $appName . ' - ' . __('mail.email_change_subject');
        $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        $sent = Mailer::send($newEmail, $userName, $subject, $htmlBody, $plainBody);

        if (!$sent) {
            $this->emailChangeModel->markUsed((int) $verificationId);
            Logger::error("Email change code send failed for user ID {$userId}");
            Audit::log([
                'module' => 'account', 'action' => 'account.email_change_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'No se pudo enviar codigo de cambio de correo',
                'old_values' => ['email' => $user['email']],
                'new_values' => ['email' => $newEmail],
                'status' => 'failed',
            ]);
            return false;
        }

        Audit::log([
            'module' => 'account',
            'action' => $resent ? 'account.email_change_code_resent' : 'account.email_change_code_sent',
            'entity' => 'user',
            'entity_id' => $userId,
            'description' => $resent ? 'Codigo de cambio de correo reenviado' : 'Codigo de cambio de correo enviado',
            'old_values' => ['email' => $user['email']],
            'new_values' => ['email' => $newEmail],
            'status' => 'success',
        ]);

        return true;
    }

    private function isSmtpReady(): bool
    {
        try {
            $settings = (new SmtpSettings())->get();
            return trim((string) ($settings['host'] ?? '')) !== ''
                && trim((string) ($settings['username'] ?? '')) !== ''
                && trim((string) ($settings['password_enc'] ?? '')) !== ''
                && (int) ($settings['is_verified'] ?? 0) === 1;
        } catch (\Throwable $e) {
            Logger::error('AccountController::isSmtpReady - ' . $e->getMessage());
            return false;
        }
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return $email;
        }

        $first = substr($local, 0, 1);
        return $first . str_repeat('*', max(3, strlen($local) - 1)) . '@' . $domain;
    }
}
