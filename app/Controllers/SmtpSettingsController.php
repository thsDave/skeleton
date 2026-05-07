<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use Core\Logger;
use App\Models\SmtpSettings;
use App\Services\Mailer;

class SmtpSettingsController
{
    public function index(): void
    {
        Auth::requirePermission('security_smtp.view');

        $model    = new SmtpSettings();
        $settings = $model->getDecrypted();

        require dirname(__DIR__) . '/Views/security/smtp/index.php';
    }

    public function update(): void
    {
        Auth::requirePermission('security_smtp.edit');
        CSRF::validateOrFail();

        $host        = trim($_POST['host']        ?? '');
        $port        = (int) ($_POST['port']       ?? 587);
        $username    = trim($_POST['username']     ?? '');
        $password    = $_POST['password']          ?? '';
        $encryption  = $_POST['encryption']        ?? 'tls';
        $fromAddress = trim($_POST['from_address'] ?? '');
        $fromName    = trim($_POST['from_name']    ?? '');

        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }

        $errors = [];
        if ($host === '') {
            $errors[] = __('smtp.validation.host_required');
        }
        if ($port < 1 || $port > 65535) {
            $errors[] = __('smtp.validation.port_invalid');
        }
        if ($username === '') {
            $errors[] = __('smtp.validation.username_required');
        }
        if ($fromAddress === '') {
            $errors[] = __('smtp.validation.from_address_required');
        } elseif (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('smtp.validation.from_address_invalid');
        }

        if ($errors) {
            Redirect::withErrors('/security/smtp', $errors, $_POST);
            exit;
        }

        $model = new SmtpSettings();
        $saved = $model->update([
            'host'         => $host,
            'port'         => $port,
            'username'     => $username,
            'password'     => $password,
            'encryption'   => $encryption,
            'from_address' => $fromAddress,
            'from_name'    => $fromName,
        ]);

        if ($saved) {
            Audit::log([
                'module'      => 'security_smtp',
                'action'      => 'settings_updated',
                'description' => 'Configuración SMTP actualizada',
                'new_values'  => [
                    'host'         => $host,
                    'port'         => $port,
                    'username'     => $username,
                    'encryption'   => $encryption,
                    'from_address' => $fromAddress,
                ],
                'status' => 'success',
            ]);
            Session::flash('success', __('smtp.updated'));
        } else {
            Session::flash('error', __('alerts.internal'));
        }

        Redirect::to('/security/smtp');
        exit;
    }

    public function test(): void
    {
        Auth::requirePermission('security_smtp.test');
        CSRF::validateOrFail();

        try {
            $model    = new SmtpSettings();
            $settings = $model->getDecrypted();

            // Verify minimum configuration
            if ($settings['host'] === '' || $settings['username'] === '') {
                Session::flash('error', __('smtp.test.not_configured'));
                Redirect::to('/security/smtp');
                exit;
            }

            // Detect password issues before sending
            if ($settings['password_enc'] === '') {
                $model->markTested(false, __('smtp.test.status_fail'));
                Audit::log([
                    'module'      => 'security_smtp',
                    'action'      => 'smtp_tested',
                    'description' => 'Prueba SMTP fallida — contraseña no configurada',
                    'status'      => 'error',
                ]);
                Session::flash('error', __('smtp.error.password_empty'));
                Redirect::to('/security/smtp');
                exit;
            }

            if ($settings['password_enc'] !== '' && $settings['password'] === '') {
                $model->markTested(false, __('smtp.test.status_fail'));
                Logger::error('Mailer test: password decryption failed — APP_KEY may have changed');
                Audit::log([
                    'module'      => 'security_smtp',
                    'action'      => 'smtp_tested',
                    'description' => 'Prueba SMTP fallida — no se pudo descifrar la contraseña',
                    'status'      => 'error',
                ]);
                Session::flash('error', __('smtp.error.password_decrypt_fail'));
                Redirect::to('/security/smtp');
                exit;
            }

            // Determine recipient for the test email
            $testEmail = trim($_POST['test_email'] ?? '');
            if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $user      = Auth::user();
                $testEmail = $user['email'] ?? $settings['from_address'];
            }
            $toName = 'SMTP Test';

            $subject = __('smtp.test.email_subject');
            $html    = '<div style="font-family:sans-serif;max-width:500px;margin:auto;padding:24px;">'
                     . '<h2 style="color:#0d6efd;">' . htmlspecialchars(__('smtp.test.email_subject')) . '</h2>'
                     . '<p>' . htmlspecialchars(__('smtp.test.email_body')) . '</p>'
                     . '<p style="color:#888;font-size:12px;margin-top:24px;">Skeleton MVC</p>'
                     . '</div>';

            Logger::info(sprintf(
                'SMTP test initiated — host=%s port=%d enc=%s from=%s to=%s',
                $settings['host'],
                $settings['port'],
                $settings['encryption'],
                $settings['from_address'],
                $testEmail
            ));

            $sent = Mailer::send($testEmail, $toName, $subject, $html);

            $model->markTested($sent, $sent
                ? __('smtp.test.status_ok')
                : __('smtp.test.status_fail')
            );

            Audit::log([
                'module'      => 'security_smtp',
                'action'      => 'smtp_tested',
                'description' => $sent
                    ? 'Prueba SMTP exitosa — enviado a ' . $testEmail
                    : 'Prueba SMTP fallida — ' . Mailer::$lastError,
                'status'      => $sent ? 'success' : 'error',
            ]);

            $resultMessage = $sent
                ? __('smtp.test.sent_ok', ['email' => $testEmail])
                : self::friendlyError(Mailer::$lastError);

            Session::flash('smtp_test_result', ['success' => $sent, 'message' => $resultMessage]);

            if ($sent) {
                Session::flash('success', __('smtp.test.sent_ok', ['email' => $testEmail]));
            } else {
                Session::flash('error', self::friendlyError(Mailer::$lastError));
            }

        } catch (\Throwable $e) {
            Logger::error('SmtpSettingsController::test() unexpected error — ' . $e->getMessage());
            Session::flash('error', __('alerts.internal'));
        }

        Redirect::to('/security/smtp');
        exit;
    }

    private static function friendlyError(string $category): string
    {
        return match ($category) {
            'auth_failed'         => __('smtp.error.auth_failed'),
            'connect_failed'      => __('smtp.error.connect_failed'),
            'invalid_address'     => __('smtp.error.invalid_address'),
            'password_empty'      => __('smtp.error.password_empty'),
            'password_decrypt_fail' => __('smtp.error.password_decrypt_fail'),
            'not_configured'      => __('smtp.test.not_configured'),
            default               => __('smtp.test.sent_fail'),
        };
    }
}
