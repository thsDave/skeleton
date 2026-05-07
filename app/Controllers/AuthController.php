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
use App\Models\User;
use App\Models\LoginLog;
use App\Services\Mailer;
use App\Services\SmsService;

class AuthController extends Controller
{
    private User $userModel;
    private LoginLog $logModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->logModel  = new LoginLog();
    }

    public function loginForm(): void
    {
        Auth::requireGuest();
        $this->view('auth.login');
    }

    public function loginProcess(): void
    {
        Auth::requireGuest();
        CSRF::validateOrFail();

        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');
        $config   = require dirname(__DIR__, 2) . '/config/app.php';

        $validator = new Validator();
        $validator->required('email', $email, 'Correo electrónico')
                  ->email('email', $email)
                  ->required('password', $password, 'Contraseña');

        if ($validator->fails()) {
            Redirect::withErrors('/login', $validator->errors(), ['email' => $email]);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $this->logModel->record(null, $email, 'failed', 'Email no encontrado');
            Logger::security("Login fallido - email no existe: {$email}");
            Audit::log(['module' => 'auth', 'action' => 'login_failed',
                'description' => "Intento de login con email desconocido: {$email}", 'status' => 'failed',
                'user_id' => null]);
            Redirect::withErrors('/login', ['general' => 'Las credenciales ingresadas no son válidas.'], ['email' => $email]);
        }

        // Verificar bloqueo
        if ($this->userModel->isLocked($user)) {
            $this->logModel->record($user['id'], $email, 'blocked', 'Cuenta bloqueada temporalmente');
            Logger::security("Login bloqueado para usuario ID {$user['id']}");
            Audit::log(['module' => 'auth', 'action' => 'login_blocked',
                'entity' => 'user', 'entity_id' => $user['id'],
                'description' => 'Intento de login con cuenta bloqueada', 'status' => 'denied',
                'user_id' => $user['id']]);
            Redirect::withErrors('/login', ['general' => 'La cuenta está bloqueada temporalmente. Intenta en 15 minutos.'], ['email' => $email]);
        }

        // Verificar status (usa status_slug del JOIN con tbl_statuses)
        if (($user['status_slug'] ?? '') !== 'active') {
            $this->logModel->record($user['id'], $email, 'failed', 'Cuenta inactiva o bloqueada');
            Logger::security("Login fallido - cuenta inactiva ID {$user['id']}");
            Audit::log(['module' => 'auth', 'action' => 'login_failed',
                'entity' => 'user', 'entity_id' => $user['id'],
                'description' => 'Intento de login con cuenta inactiva', 'status' => 'failed',
                'user_id' => $user['id']]);
            Redirect::withErrors('/login', ['general' => 'Las credenciales ingresadas no son válidas.'], ['email' => $email]);
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            $this->userModel->incrementFailedAttempts($user['id']);

            $newAttempts = $user['failed_login_attempts'] + 1;
            if ($newAttempts >= $config['max_login_attempts']) {
                $this->userModel->lockAccount($user['id'], $config['lockout_minutes']);
                $this->logModel->record($user['id'], $email, 'blocked', 'Máximo de intentos alcanzado');
                Logger::security("Cuenta bloqueada por intentos fallidos - ID {$user['id']}");
                Audit::log(['module' => 'auth', 'action' => 'login_blocked',
                    'entity' => 'user', 'entity_id' => $user['id'],
                    'description' => 'Cuenta bloqueada por máximo de intentos fallidos', 'status' => 'warning',
                    'user_id' => $user['id']]);
                Redirect::withErrors('/login', ['general' => 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.'], ['email' => $email]);
            }

            $this->logModel->record($user['id'], $email, 'failed', 'Contraseña incorrecta');
            Logger::security("Login fallido - contraseña incorrecta ID {$user['id']}");
            Audit::log(['module' => 'auth', 'action' => 'login_failed',
                'entity' => 'user', 'entity_id' => $user['id'],
                'description' => 'Login fallido: contraseña incorrecta', 'status' => 'failed',
                'user_id' => $user['id']]);
            Redirect::withErrors('/login', ['general' => 'Las credenciales ingresadas no son válidas.'], ['email' => $email]);
        }

        // Login exitoso — credenciales válidas
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->userModel->updateLastLogin($user['id'], $ip);
        $this->logModel->record($user['id'], $email, 'success', 'Login exitoso');
        Logger::security("Login exitoso - ID {$user['id']} desde {$ip}");

        // 2FA check — si el usuario tiene 2FA activo y el método sigue habilitado globalmente
        if (!empty($user['two_factor_enabled']) && !empty($user['two_factor_method'])) {
            $mfaSettings = (new \App\Models\MfaSettings())->get();
            $globalOn    = match ($user['two_factor_method']) {
                'email'         => !empty($mfaSettings['email_enabled']),
                'sms'           => !empty($mfaSettings['sms_enabled']),
                'authenticator' => !empty($mfaSettings['authenticator_enabled']),
                default         => false,
            };

            if ($globalOn) {
                Session::set('pending_2fa_user_id', $user['id']);
                Session::set('pending_2fa_method',  $user['two_factor_method']);

                if (in_array($user['two_factor_method'], ['email', 'sms'], true)) {
                    $tf     = new \App\Services\TwoFactorService();
                    $codes  = new \App\Models\TwoFactorCode();
                    $expiry = (int) env('TWO_FACTOR_CODE_EXPIRATION_MINUTES', 10);
                    $code   = $tf->generateNumericCode();
                    $codes->deleteForUser($user['id'], $user['two_factor_method']);
                    $codes->create($user['id'], $tf->hashCode($code), $user['two_factor_method'], $expiry);

                    if ($user['two_factor_method'] === 'email') {
                        $html = self::build2faEmailHtml($user['nombres'], $code, $expiry);
                        \App\Services\Mailer::send($user['email'], $user['nombres'], __('2fa.email_subject'), $html);
                    } else {
                        $phone = $user['two_factor_phone'] ?? '';
                        if ($phone !== '') {
                            $msg = __('2fa.sms_body', ['code' => $code, 'minutes' => $expiry]);
                            \App\Services\SmsService::send($phone, $msg);
                        }
                    }
                }

                Audit::log(['module' => 'auth', 'action' => 'login_2fa_required',
                    'entity' => 'user', 'entity_id' => $user['id'],
                    'description' => "2FA requerido ({$user['two_factor_method']}) desde {$ip}",
                    'status' => 'pending']);
                Redirect::to('/two-factor/challenge');
            }
        }

        Auth::login($user);

        Audit::log(['module' => 'auth', 'action' => 'login_success',
            'entity' => 'user', 'entity_id' => $user['id'],
            'description' => "Login exitoso desde {$ip}", 'status' => 'success']);

        Redirect::to('/dashboard');
    }

    private static function build2faEmailHtml(string $nombre, string $code, int $expiry): string
    {
        return '<div style="font-family:sans-serif;max-width:520px;margin:auto;padding:24px;">'
             . '<h2 style="color:#1a1a2e;">' . __('2fa.email_subject') . '</h2>'
             . '<p>Hola <strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
             . '<p>' . __('2fa.email_intro') . '</p>'
             . '<div style="text-align:center;margin:24px 0;padding:20px;background:#f0f4ff;border-radius:10px;border:2px dashed #0d6efd;">'
             . '<span style="font-size:40px;font-weight:800;letter-spacing:12px;color:#0d6efd;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</span>'
             . '</div>'
             . '<p style="color:#666;font-size:13px;">' . __('2fa.email_expiry', ['minutes' => $expiry]) . '</p>'
             . '<p style="color:#999;font-size:12px;">' . __('2fa.email_no_request') . '</p>'
             . '</div>';
    }

    public function logout(): void
    {
        if (!$this->isPost()) {
            Redirect::to('/dashboard');
        }

        CSRF::validateOrFail();

        $userId = Auth::id();
        Logger::security("Logout - ID {$userId}");
        Audit::log(['module' => 'auth', 'action' => 'logout',
            'entity' => 'user', 'entity_id' => $userId,
            'description' => 'Cierre de sesión', 'status' => 'success']);
        Auth::logout();
        Redirect::to('/login');
    }
}
