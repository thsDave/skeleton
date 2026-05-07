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
use App\Services\SmsService;

class MfaSettingsController
{
    public function index(): void
    {
        Auth::requirePermission('security_mfa.view');

        $authUser = Auth::user();
        $model    = new MfaSettings();
        $settings = $model->get();
        $smtpReady = $this->isSmtpReady();

        require dirname(__DIR__) . '/Views/security/mfa/index.php';
    }

    public function update(): void
    {
        Auth::requirePermission('security_mfa.edit');
        CSRF::validateOrFail();

        $emailEnabled         = isset($_POST['email_enabled'])         ? 1 : 0;
        $smsEnabled           = isset($_POST['sms_enabled'])           ? 1 : 0;
        $authenticatorEnabled = isset($_POST['authenticator_enabled']) ? 1 : 0;

        $smsProvider    = trim($_POST['sms_provider']    ?? '');
        $smsApiKey      = trim($_POST['sms_api_key']     ?? '');
        $smsApiSecret   = $_POST['sms_api_secret']       ?? '';
        $smsFrom        = trim($_POST['sms_from']        ?? '');
        $smsEndpoint    = trim($_POST['sms_endpoint']    ?? '');
        $smsExtraConfig = trim($_POST['sms_extra_config'] ?? '');

        $errors = [];

        // Validar: email MFA requiere SMTP activo y verificado
        if ($emailEnabled && !$this->isSmtpReady()) {
            $errors[] = __('mfa.error.email_requires_smtp');
        }

        // Validar: SMS MFA requiere configuración mínima del proveedor
        if ($smsEnabled) {
            $model   = new MfaSettings();
            $current = $model->get();

            $hasSecret = ($smsApiSecret !== '')
                || (!empty($current['sms_api_secret_enc']));

            if ($smsProvider === '') {
                $errors[] = __('mfa.validation.sms_provider_required');
            }
            if ($smsApiKey === '') {
                $errors[] = __('mfa.validation.sms_api_key_required');
            }
            if (!$hasSecret) {
                $errors[] = __('mfa.validation.sms_api_secret_required');
            }
            if ($smsFrom === '') {
                $errors[] = __('mfa.validation.sms_from_required');
            }
        }

        // Validar JSON extra config si se proporcionó
        if ($smsExtraConfig !== '') {
            json_decode($smsExtraConfig);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = __('mfa.validation.sms_extra_config_invalid');
            }
        }

        if ($errors) {
            Redirect::withErrors('/security/mfa', $errors, $_POST);
            exit;
        }

        $model = new MfaSettings();
        $saved = $model->update([
            'email_enabled'         => $emailEnabled,
            'sms_enabled'           => $smsEnabled,
            'authenticator_enabled' => $authenticatorEnabled,
            'sms_provider'          => $smsProvider,
            'sms_api_key'           => $smsApiKey,
            'sms_api_secret'        => $smsApiSecret,
            'sms_from'              => $smsFrom,
            'sms_endpoint'          => $smsEndpoint,
            'sms_extra_config'      => $smsExtraConfig,
        ]);

        if ($saved) {
            try {
                Audit::log([
                    'module'      => 'security_mfa',
                    'action'      => 'settings_updated',
                    'description' => 'Configuración MFA actualizada',
                    'new_values'  => [
                        'email_enabled'         => $emailEnabled,
                        'sms_enabled'           => $smsEnabled,
                        'authenticator_enabled' => $authenticatorEnabled,
                        'sms_provider'          => $smsProvider,
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

    public function testSms(): void
    {
        Auth::requirePermission('security_mfa.test');
        CSRF::validateOrFail();

        $testPhone = trim($_POST['test_phone'] ?? '');

        if ($testPhone === '') {
            Session::flash('error', __('mfa.validation.test_phone_required'));
            Redirect::to('/security/mfa');
            exit;
        }

        if (!SmsService::isConfigured()) {
            Session::flash('error', __('mfa.error.sms_not_configured'));
            Redirect::to('/security/mfa');
            exit;
        }

        $message = __('mfa.sms_test_message');

        Logger::info(sprintf(
            'MFA SMS test initiated — provider=%s to=%s',
            (new MfaSettings())->get()['sms_provider'] ?? 'unknown',
            $testPhone
        ));

        $sent = SmsService::send($testPhone, $message);

        $isNotImplemented = in_array(SmsService::$lastError, [
            'provider_not_implemented',
            'provider_not_supported',
            'custom_no_endpoint',
        ], true);

        try {
            Audit::log([
                'module'      => 'security_mfa',
                'action'      => $sent ? 'sms_test_success' : 'sms_test_failed',
                'description' => $sent
                    ? 'Prueba SMS exitosa'
                    : 'Prueba SMS fallida — ' . SmsService::$lastError,
                'status'      => $sent ? 'success' : 'error',
            ]);
        } catch (\Throwable $e) {
            Logger::error('MfaSettingsController::testSms audit error — ' . $e->getMessage());
        }

        if ($sent) {
            Session::flash('success', __('mfa.sms_test_success'));
        } elseif ($isNotImplemented) {
            Session::flash('error', __('mfa.error.provider_not_implemented'));
        } else {
            Session::flash('error', __('mfa.sms_test_failed'));
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
