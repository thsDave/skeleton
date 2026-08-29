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
use App\Services\RateLimitService;

class SmtpSettingsController
{
    // Rate limit de la prueba SMTP administrativa (Etapa 3.7). Limite
    // aprobado: 5 pruebas / 5 minutos, por administrador. Una prueba
    // SMTP puede disparar un correo real, por lo que se limita el
    // volumen de pruebas sin bloquear el guardado de configuracion.
    private const SMTP_TEST_RATE_LIMIT_ACTION = 'admin.smtp_test';
    private const SMTP_TEST_RATE_LIMIT_IDENTIFIER_TYPE = 'user';
    private const SMTP_TEST_RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const SMTP_TEST_RATE_LIMIT_WINDOW_SECONDS = 300;

    public function index(): void
    {
        Auth::requirePermission('security_smtp.view');

        $authUser = Auth::user();
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
                'action'      => 'smtp.updated',
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
                    'action'      => 'smtp.test_failed',
                    'description' => 'Prueba SMTP fallida — contraseña no configurada',
                    'status'      => 'failed',
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
                    'action'      => 'smtp.test_failed',
                    'description' => 'Prueba SMTP fallida — no se pudo descifrar la contraseña',
                    'status'      => 'failed',
                ]);
                Session::flash('error', __('smtp.error.password_decrypt_fail'));
                Redirect::to('/security/smtp');
                exit;
            }

            // ── Rate limit de la prueba SMTP (Etapa 3.7) ────────────────────────
            // Se evalua justo antes de enviar el correo real: las validaciones de
            // configuracion previas (host/usuario vacios, password vacia o sin
            // descifrar) no cuentan como intento, porque nunca llegan a intentar
            // un envio real.
            $rateLimiter = new RateLimitService();
            $adminId     = (string) Auth::id();

            if ($rateLimiter->tooManyAttempts(
                self::SMTP_TEST_RATE_LIMIT_ACTION,
                $adminId,
                self::SMTP_TEST_RATE_LIMIT_MAX_ATTEMPTS,
                self::SMTP_TEST_RATE_LIMIT_WINDOW_SECONDS,
                self::SMTP_TEST_RATE_LIMIT_IDENTIFIER_TYPE
            )) {
                $availableIn = $rateLimiter->availableIn(
                    self::SMTP_TEST_RATE_LIMIT_ACTION,
                    $adminId,
                    self::SMTP_TEST_RATE_LIMIT_IDENTIFIER_TYPE
                );

                Audit::log([
                    'module'      => 'security_smtp',
                    'action'      => 'smtp.test_rate_limited',
                    'description' => 'Prueba SMTP bloqueada temporalmente por exceso de intentos',
                    'status'      => 'denied',
                    'new_values'  => [
                        'action'          => self::SMTP_TEST_RATE_LIMIT_ACTION,
                        'identifier_type' => self::SMTP_TEST_RATE_LIMIT_IDENTIFIER_TYPE,
                        'available_in'    => $availableIn,
                    ],
                ]);

                Session::flash('error', __('smtp.test_rate_limited', ['seconds' => $availableIn]));
                Redirect::to('/security/smtp');
                exit;
            }

            // La solicitud cuenta aunque el envio falle despues. A diferencia de
            // los flujos de fuerza bruta (Etapas 3.2/3.3/3.4), el intento que
            // alcanza el limite (#5) SI se procesa normalmente — es una accion
            // administrativa de diagnostico, no un intento adversario — y el
            // bloqueo aplica recien desde la siguiente solicitud (#6), detectada
            // por tooManyAttempts() en la proxima llamada a este metodo.
            $rateLimiter->hit(
                self::SMTP_TEST_RATE_LIMIT_ACTION,
                $adminId,
                self::SMTP_TEST_RATE_LIMIT_IDENTIFIER_TYPE,
                self::SMTP_TEST_RATE_LIMIT_MAX_ATTEMPTS,
                self::SMTP_TEST_RATE_LIMIT_WINDOW_SECONDS
            );

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
                'action'      => $sent ? 'smtp.test_success' : 'smtp.test_failed',
                'description' => $sent
                    ? 'Prueba SMTP exitosa — enviado a ' . $testEmail
                    : 'Prueba SMTP fallida — ' . Mailer::$lastError,
                'status'      => $sent ? 'success' : 'failed',
            ]);

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
