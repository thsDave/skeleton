<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use Core\Logger;
use App\Models\LoginSecuritySetting;

class LoginAttemptsSettingsController
{
    public function index(): void
    {
        Auth::requirePermission('security_attempts.view');

        $authUser      = Auth::user();
        $loginSecurity = (new LoginSecuritySetting())->get();
        $errors        = Session::getFlash('errors', []);
        $old           = Session::getFlash('old', []);

        require dirname(__DIR__) . '/Views/security/attempts/index.php';
    }

    public function update(): void
    {
        Auth::requirePermission('security_attempts.edit');
        CSRF::validateOrFail();

        $data = [
            'failed_login_protection_enabled' => isset($_POST['failed_login_protection_enabled']) ? 1 : 0,
            'max_failed_attempts_user'        => (int)($_POST['max_failed_attempts_user'] ?? 5),
            'user_attempt_window_minutes'     => (int)($_POST['user_attempt_window_minutes'] ?? 15),
            'user_lockout_minutes'            => (int)($_POST['user_lockout_minutes'] ?? 15),
            'ip_protection_enabled'           => isset($_POST['ip_protection_enabled']) ? 1 : 0,
            'max_failed_attempts_ip'          => (int)($_POST['max_failed_attempts_ip'] ?? 20),
            'ip_attempt_window_minutes'       => (int)($_POST['ip_attempt_window_minutes'] ?? 15),
            'ip_lockout_minutes'              => (int)($_POST['ip_lockout_minutes'] ?? 30),
        ];

        $errors = $this->validateData($data);

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Redirect::to('/security/attempts');
            exit;
        }

        $saved = (new LoginSecuritySetting())->save($data);

        if ($saved) {
            try {
                Audit::log([
                    'module'      => 'security_attempts',
                    'action'      => 'login_security_settings_updated',
                    'description' => 'Configuración de intentos fallidos actualizada',
                    'new_values'  => $data,
                    'status'      => 'success',
                ]);
            } catch (\Throwable $e) {
                Logger::error('LoginAttemptsSettingsController::update audit — ' . $e->getMessage());
            }
            Session::flash('success', __('security_attempts.updated'));
        } else {
            Session::flash('error', __('security_attempts.update_error'));
        }

        Redirect::to('/security/attempts');
        exit;
    }

    private function validateData(array $data): array
    {
        $errors = [];

        if ($data['max_failed_attempts_user'] < 1 || $data['max_failed_attempts_user'] > 20)
            $errors[] = __('security_attempts.error_max_user_attempts');
        if ($data['user_attempt_window_minutes'] < 1 || $data['user_attempt_window_minutes'] > 1440)
            $errors[] = __('security_attempts.error_user_window');
        if ($data['user_lockout_minutes'] < 1 || $data['user_lockout_minutes'] > 1440)
            $errors[] = __('security_attempts.error_user_lockout');
        if ($data['max_failed_attempts_ip'] < 1 || $data['max_failed_attempts_ip'] > 200)
            $errors[] = __('security_attempts.error_max_ip_attempts');
        if ($data['ip_attempt_window_minutes'] < 1 || $data['ip_attempt_window_minutes'] > 1440)
            $errors[] = __('security_attempts.error_ip_window');
        if ($data['ip_lockout_minutes'] < 1 || $data['ip_lockout_minutes'] > 1440)
            $errors[] = __('security_attempts.error_ip_lockout');

        return $errors;
    }
}
