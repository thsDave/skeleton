<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\Logger;
use Core\Redirect;
use Core\Session;
use Core\Validator;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PasswordPolicyService;
use App\Services\UserSessionService;

class RequiredPasswordChangeController extends Controller
{
    // ─── GET /account/password/required-change ────────────────────────────────

    public function show(): void
    {
        Auth::requireAuth();

        // If no change is required (e.g., user hit back after completing), go to dashboard
        if (!Auth::requiresPasswordChange()) {
            Redirect::to('/dashboard');
        }

        $reason     = Session::get('pwd_change_reason', 'expired');
        $policyReqs = (new PasswordPolicyService())->getRequirements();
        $errors     = Session::getFlash('errors', []);

        Audit::log([
            'module'      => 'account',
            'action'      => 'password.required_change_redirected',
            'entity'      => 'user',
            'entity_id'   => Auth::id(),
            'description' => 'Usuario redirigido a cambio obligatorio de contraseña (motivo: ' . $reason . ')',
            'status'      => 'info',
            'user_id'     => Auth::id(),
        ]);

        $this->view('auth.required_password_change', compact('reason', 'policyReqs', 'errors'));
    }

    // ─── POST /account/password/required-change ───────────────────────────────

    public function process(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $userId          = Auth::id();
        $newPassword     = $this->input('new_password', '');
        $confirmPassword = $this->input('confirm_password', '');

        $validator = new Validator();
        $validator->required('new_password',     $newPassword,     'Nueva contraseña')
                  ->required('confirm_password', $confirmPassword, 'Confirmar contraseña')
                  ->matches('confirm_password',  $confirmPassword,  $newPassword);

        if ($validator->fails()) {
            Redirect::withErrors('/account/password/required-change', $validator->errors());
        }

        $userModel = new User();
        $user      = $userModel->findById($userId);

        if (!$user) {
            Logger::error("RequiredPasswordChangeController: user {$userId} not found");
            Auth::logout();
            Redirect::to('/login');
        }

        // ── Validar política de contraseñas ───────────────────────────────────
        $policySvc    = new PasswordPolicyService();
        $policyResult = $policySvc->validate($newPassword, [
            'email'     => $user['email']     ?? '',
            'nombres'   => $user['nombres']   ?? '',
            'apellidos' => $user['apellidos'] ?? '',
        ]);

        if (!$policyResult['valid']) {
            Audit::log([
                'module'      => 'account',
                'action'      => 'password.required_change_failed',
                'entity'      => 'user',
                'entity_id'   => $userId,
                'description' => 'Cambio obligatorio rechazado por política de contraseñas',
                'status'      => 'denied',
                'user_id'     => $userId,
            ]);
            Redirect::withErrors('/account/password/required-change', ['new_password' => implode(' ', $policyResult['errors'])]);
        }

        if ($policySvc->isPasswordReused($newPassword, $userId)) {
            Audit::log([
                'module'      => 'account',
                'action'      => 'password.required_change_failed',
                'entity'      => 'user',
                'entity_id'   => $userId,
                'description' => 'Cambio obligatorio rechazado por reutilización de contraseña',
                'status'      => 'denied',
                'user_id'     => $userId,
            ]);
            Redirect::withErrors('/account/password/required-change', [
                'new_password' => __('password_policy.error_reused', [
                    'count' => (string)(int)($policySvc->getPolicy()['password_history_count'] ?? 3),
                ]),
            ]);
        }

        $reason         = Session::get('pwd_change_reason', 'expired');
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // ── Actualizar contraseña y limpiar flag de cambio obligatorio ─────────
        $ok = $userModel->updatePasswordAndClearForce($userId, $hashedPassword);

        if (!$ok) {
            Logger::error("RequiredPasswordChangeController: failed to update password for user {$userId}");
            Session::flash('error', __('password_policy.password_update_error'));
            Redirect::to('/account/password/required-change');
        }

        // ── Historial ─────────────────────────────────────────────────────────
        $policySvc->saveHistory($userId, $hashedPassword);

        // ── Limpiar flags de sesión ────────────────────────────────────────────
        Auth::clearPasswordChangeRequired();
        $closedSessions = (new UserSessionService())->revokeOtherSessionsForUser((int)$userId, 'required_password_change', (int)$userId);

        Logger::security("Required password change completed for user ID {$userId}, reason: {$reason}");
        Audit::log([
            'module'      => 'account',
            'action'      => 'password.required_change_completed',
            'entity'      => 'user',
            'entity_id'   => $userId,
            'description' => 'Cambio obligatorio de contraseña completado (motivo: ' . $reason . ')',
            'status'      => 'success',
            'user_id'     => $userId,
        ]);
        (new NotificationService())->notifyPasswordChanged((int)$userId);

        $message = __('password_policy.password_updated');
        if ($closedSessions > 0) {
            $message .= ' ' . __('sessions.other_sessions_closed');
        }
        Session::flash('success', $message);
        Redirect::to('/dashboard');
    }
}
