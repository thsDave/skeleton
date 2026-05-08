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

class MfaSettingsController
{
    public function index(): void
    {
        Auth::requirePermission('security_mfa.view');

        $authUser  = Auth::user();
        $model     = new MfaSettings();
        $settings  = $model->get();
        $smtpReady = $this->isSmtpReady();

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
            Redirect::withErrors('/security/mfa', $errors, $_POST);
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

        Redirect::to('/security/mfa');
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
}
