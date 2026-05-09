<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use Core\Logger;
use App\Models\MfaSettings;
use App\Models\SmtpSettings;
use App\Models\LoginSecuritySetting;

class MfaSettingsController
{
    public function index(): void
    {
        Auth::requirePermission('security_mfa.view');

        $authUser      = Auth::user();
        $mfaModel      = new MfaSettings();
        $settings      = $mfaModel->get();
        $smtpReady     = $this->isSmtpReady();

        $lsModel       = new LoginSecuritySetting();
        $loginSecurity = $lsModel->get();

        $errors        = Session::getFlash('errors', []);
        $old           = Session::getFlash('old', []);
        $activeTab     = $_GET['tab'] ?? 'mfa';

        require dirname(__DIR__) . '/Views/security/mfa/index.php';
    }

    public function update(): void
    {
        Auth::requirePermission('security_mfa.edit');
        CSRF::validateOrFail();

        $emailEnabled         = isset($_POST['email_enabled'])         ? 1 : 0;
        $authenticatorEnabled = isset($_POST['authenticator_enabled']) ? 1 : 0;

        $errors = [];

        if ($emailEnabled && !$this->isSmtpReady()) {
            $errors[] = __('mfa.error.email_requires_smtp');
        }

        if ($errors) {
            Redirect::withErrors('/security/mfa?tab=mfa', $errors, $_POST);
            exit;
        }

        $model = new MfaSettings();
        $saved = $model->update([
            'email_enabled'         => $emailEnabled,
            'authenticator_enabled' => $authenticatorEnabled,
        ]);

        if ($saved) {
            try {
                Audit::log([
                    'module'      => 'security_mfa',
                    'action'      => 'settings_updated',
                    'description' => 'Configuración MFA actualizada',
                    'new_values'  => [
                        'email_enabled'         => $emailEnabled,
                        'authenticator_enabled' => $authenticatorEnabled,
                    ],
                    'status' => 'success',
                ]);
            } catch (\Throwable $e) {
                Logger::error('MfaSettingsController::update audit error — ' . $e->getMessage());
            }
            Session::flash('success', __('mfa.updated'));
        } else {
            Session::flash('error', __('alerts.internal'));
        }

        Redirect::to('/security/mfa?tab=mfa');
        exit;
    }

    public function updateLoginSecurity(): void
    {
        Auth::requirePermission('security_mfa.edit');
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

        $errors = $this->validateLoginSecurityData($data);

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Redirect::to('/security/mfa?tab=failed-attempts');
            exit;
        }

        $model = new LoginSecuritySetting();
        $saved = $model->save($data);

        if ($saved) {
            try {
                Audit::log([
                    'module'      => 'security_mfa',
                    'action'      => 'login_security_settings_updated',
                    'description' => 'Configuración de intentos fallidos actualizada',
                    'new_values'  => $data,
                    'status'      => 'success',
                ]);
            } catch (\Throwable $e) {
                Logger::error('MfaSettingsController::updateLoginSecurity audit — ' . $e->getMessage());
            }
            Session::flash('success', __('authentication.failed_attempts.updated'));
        } else {
            Session::flash('error', __('authentication.failed_attempts.update_error'));
        }

        Redirect::to('/security/mfa?tab=failed-attempts');
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function isSmtpReady(): bool
    {
        try {
            $smtp = (new SmtpSettings())->get();
            return (
                !empty($smtp['host']) &&
                !empty($smtp['port']) &&
                !empty($smtp['username']) &&
                !empty($smtp['password_enc']) &&
                !empty($smtp['from_address']) &&
                (int) $smtp['is_verified'] === 1
            );
        } catch (\Throwable $e) {
            Logger::error('MfaSettingsController::isSmtpReady error — ' . $e->getMessage());
            return false;
        }
    }

    private function validateLoginSecurityData(array $data): array
    {
        $errors = [];

        if ($data['max_failed_attempts_user'] < 1 || $data['max_failed_attempts_user'] > 20) {
            $errors[] = __('authentication.failed_attempts.error_max_user_attempts');
        }
        if ($data['user_attempt_window_minutes'] < 1 || $data['user_attempt_window_minutes'] > 1440) {
            $errors[] = __('authentication.failed_attempts.error_user_window');
        }
        if ($data['user_lockout_minutes'] < 1 || $data['user_lockout_minutes'] > 1440) {
            $errors[] = __('authentication.failed_attempts.error_user_lockout');
        }
        if ($data['max_failed_attempts_ip'] < 1 || $data['max_failed_attempts_ip'] > 200) {
            $errors[] = __('authentication.failed_attempts.error_max_ip_attempts');
        }
        if ($data['ip_attempt_window_minutes'] < 1 || $data['ip_attempt_window_minutes'] > 1440) {
            $errors[] = __('authentication.failed_attempts.error_ip_window');
        }
        if ($data['ip_lockout_minutes'] < 1 || $data['ip_lockout_minutes'] > 1440) {
            $errors[] = __('authentication.failed_attempts.error_ip_lockout');
        }

        return $errors;
    }
}
