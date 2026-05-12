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
use App\Models\AuthenticationSettings;
use App\Models\ExternalAuthProvider;
use App\Services\LoginSecurityService;
use App\Services\Mailer;

class AuthController extends Controller
{
    private User                 $userModel;
    private LoginLog             $logModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->logModel  = new LoginLog();
    }

    public function loginForm(): void
    {
        Auth::requireGuest();
        $authSettings     = (new AuthenticationSettings())->get();
        $enabledProviders = [];
        if ($authSettings['external_login_enabled']) {
            $enabledProviders = (new ExternalAuthProvider())->allVerifiedAndEnabled();
            if (empty($enabledProviders)) {
                Logger::error('AuthController::loginForm — external_login_enabled but no verified providers available');
            }
        }
        $this->view('auth.login', compact('authSettings', 'enabledProviders'));
    }

    public function loginProcess(): void
    {
        Auth::requireGuest();
        CSRF::validateOrFail();

        $authSettings = (new AuthenticationSettings())->get();
        if (!$authSettings['local_login_enabled']) {
            Audit::log([
                'module'      => 'auth',
                'action'      => 'auth.local_login_disabled',
                'description' => 'Intento de login local cuando está deshabilitado',
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::to('/login');
        }

        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');
        $config   = require dirname(__DIR__, 2) . '/config/app.php';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Inicializar servicio de seguridad (falla gracefully si la tabla no existe aún)
        $secSvc      = null;
        $secSettings = null;
        try {
            $secSvc      = new LoginSecurityService();
            $secSettings = $secSvc->getSettings();
        } catch (\Throwable $e) {
            Logger::error('AuthController: LoginSecurityService init failed — ' . $e->getMessage());
        }

        // ── Bloqueo por IP ────────────────────────────────────────────────────────
        if ($secSvc !== null && $secSvc->isIpBlocked($ip)) {
            $secSvc->recordAttempt([
                'user_id'        => null,
                'email'          => $email,
                'ip_address'     => $ip,
                'user_agent'     => $ua,
                'status'         => 'blocked_ip',
                'failure_reason' => 'IP bloqueada por exceso de intentos',
            ]);
            try {
                Audit::log([
                    'module'      => 'auth',
                    'action'      => 'login_attempts.ip_locked',
                    'description' => "Login bloqueado por IP: {$ip}",
                    'status'      => 'denied',
                    'user_id'     => null,
                ]);
            } catch (\Throwable) {}
            Redirect::withErrors('/login', ['general' => __('auth.login_temporarily_blocked')], ['email' => $email]);
        }

        // ── Validación básica de campos ───────────────────────────────────────────
        $validator = new Validator();
        $validator->required('email', $email, 'Correo electrónico')
                  ->email('email', $email)
                  ->required('password', $password, 'Contraseña');

        if ($validator->fails()) {
            Redirect::withErrors('/login', $validator->errors(), ['email' => $email]);
        }

        // ── Buscar usuario ────────────────────────────────────────────────────────
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $this->logModel->record(null, $email, 'failed', 'Email no encontrado');
            if ($secSvc) {
                $secSvc->recordAttempt([
                    'email' => $email, 'ip_address' => $ip, 'user_agent' => $ua,
                    'status' => 'failed', 'failure_reason' => 'Email no encontrado',
                ]);
            }
            Logger::security("Login fallido - email no existe: {$email}");
            Audit::log(['module' => 'auth', 'action' => 'auth.login_failed',
                'description' => "Intento de login con email desconocido: {$email}", 'status' => 'failed',
                'user_id' => null]);
            Redirect::withErrors('/login', ['general' => __('auth.login_invalid_credentials')], ['email' => $email]);
        }

        // ── Verificar bloqueo ─────────────────────────────────────────────────────
        if ($this->userModel->isLocked($user)) {
            $this->logModel->record($user['id'], $email, 'blocked', 'Cuenta bloqueada temporalmente');
            if ($secSvc) {
                $secSvc->recordAttempt([
                    'user_id' => $user['id'], 'email' => $email, 'ip_address' => $ip, 'user_agent' => $ua,
                    'status' => 'locked_user', 'failure_reason' => 'Cuenta bloqueada temporalmente',
                ]);
            }
            Logger::security("Login bloqueado para usuario ID {$user['id']}");
            Audit::log(['module' => 'auth', 'action' => 'auth.user_blocked',
                'entity' => 'user', 'entity_id' => $user['id'],
                'description' => 'Intento de login con cuenta bloqueada', 'status' => 'denied',
                'user_id' => $user['id']]);
            Redirect::withErrors('/login', ['general' => __('auth.login_temporarily_blocked')], ['email' => $email]);
        }

        // ── Verificar estado ──────────────────────────────────────────────────────
        if (($user['status_slug'] ?? '') !== 'active') {
            $this->logModel->record($user['id'], $email, 'failed', 'Cuenta inactiva o bloqueada');
            if ($secSvc) {
                $secSvc->recordAttempt([
                    'user_id' => $user['id'], 'email' => $email, 'ip_address' => $ip, 'user_agent' => $ua,
                    'status' => 'failed', 'failure_reason' => 'Cuenta inactiva',
                ]);
            }
            Logger::security("Login fallido - cuenta inactiva ID {$user['id']}");
            Audit::log(['module' => 'auth', 'action' => 'auth.user_inactive',
                'entity' => 'user', 'entity_id' => $user['id'],
                'description' => 'Intento de login con cuenta inactiva', 'status' => 'failed',
                'user_id' => $user['id']]);
            Redirect::withErrors('/login', ['general' => __('auth.login_invalid_credentials')], ['email' => $email]);
        }

        // ── Verificar contraseña ──────────────────────────────────────────────────
        if (!password_verify($password, $user['password'])) {
            $this->userModel->incrementFailedAttempts($user['id']);

            $newAttempts    = $user['failed_login_attempts'] + 1;
            $maxAttempts    = $secSvc  ? $secSvc->getMaxUserAttempts()      : $config['max_login_attempts'];
            $lockoutMinutes = $secSvc  ? $secSvc->getUserLockoutMinutes()   : $config['lockout_minutes'];
            $protectionOn   = $secSvc === null || $secSvc->isUserProtectionEnabled();

            if ($secSvc) {
                $secSvc->recordAttempt([
                    'user_id' => $user['id'], 'email' => $email, 'ip_address' => $ip, 'user_agent' => $ua,
                    'status' => 'failed', 'failure_reason' => 'Contraseña incorrecta',
                ]);
            }

            if ($protectionOn && $newAttempts >= $maxAttempts) {
                $this->userModel->lockAccount($user['id'], $lockoutMinutes);
                $this->logModel->record($user['id'], $email, 'blocked', 'Máximo de intentos alcanzado');
                Logger::security("Cuenta bloqueada por intentos fallidos - ID {$user['id']}");
                Audit::log(['module' => 'auth', 'action' => 'auth.account_locked',
                    'entity' => 'user', 'entity_id' => $user['id'],
                    'description' => 'Cuenta bloqueada por máximo de intentos fallidos', 'status' => 'warning',
                    'user_id' => $user['id']]);
                Redirect::withErrors('/login', ['general' => __('auth.login_temporarily_blocked')], ['email' => $email]);
            }

            $this->logModel->record($user['id'], $email, 'failed', 'Contraseña incorrecta');
            Logger::security("Login fallido - contraseña incorrecta ID {$user['id']}");
            Audit::log(['module' => 'auth', 'action' => 'auth.login_failed',
                'entity' => 'user', 'entity_id' => $user['id'],
                'description' => 'Login fallido: contraseña incorrecta', 'status' => 'failed',
                'user_id' => $user['id']]);
            Redirect::withErrors('/login', ['general' => __('auth.login_invalid_credentials')], ['email' => $email]);
        }

        // ── Restricción de dominio institucional ──────────────────────────────────
        if (!(new AuthenticationSettings())->isDomainAllowed($email, $authSettings)) {
            $emailDomain = strtolower(substr($email, (int) strrpos($email, '@') + 1));
            Logger::security("AuthController::loginProcess — domain not allowed: {$emailDomain}");
            Audit::log([
                'module'      => 'auth',
                'action'      => 'auth.domain_denied',
                'description' => "Login local rechazado por dominio no autorizado: {$emailDomain}",
                'status'      => 'denied',
                'user_id'     => $user['id'],
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_account_not_authorized')], ['email' => $email]);
        }

        // ── Login exitoso — credenciales válidas ──────────────────────────────────
        $this->userModel->updateLastLogin($user['id'], $ip);
        $this->logModel->record($user['id'], $email, 'success', 'Login exitoso');
        if ($secSvc) {
            $secSvc->recordAttempt([
                'user_id' => $user['id'], 'email' => $email, 'ip_address' => $ip, 'user_agent' => $ua,
                'status' => 'success',
            ]);
        }
        Logger::security("Login exitoso - ID {$user['id']} desde {$ip}");

        // ── Verificación 2FA ──────────────────────────────────────────────────────
        if (!empty($user['two_factor_enabled']) && !empty($user['two_factor_method'])) {
            if ($user['two_factor_method'] === 'sms') {
                Audit::log(['module' => 'auth', 'action' => 'mfa.method_unavailable',
                    'entity' => 'user', 'entity_id' => $user['id'],
                    'description' => "Login bloqueado — método 2FA 'sms' ya no disponible desde {$ip}",
                    'status' => 'denied']);
                Redirect::withErrors('/login',
                    ['general' => 'Tu método de verificación en 2 pasos ya no está disponible. Contacta al administrador.'],
                    ['email' => $email]);
            }

            $mfaSettings = (new \App\Models\MfaSettings())->get();
            $globalOn    = match ($user['two_factor_method']) {
                'email'         => !empty($mfaSettings['email_enabled']),
                'authenticator' => !empty($mfaSettings['authenticator_enabled']),
                default         => false,
            };

            if ($globalOn) {
                Session::set('pending_2fa_user_id', $user['id']);
                Session::set('pending_2fa_method',  $user['two_factor_method']);

                if ($user['two_factor_method'] === 'email') {
                    $tf     = new \App\Services\TwoFactorService();
                    $codes  = new \App\Models\TwoFactorCode();
                    $expiry = (int) env('TWO_FACTOR_CODE_EXPIRATION_MINUTES', 10);
                    $code   = $tf->generateNumericCode();
                    $codes->deleteForUser($user['id'], 'email');
                    $codes->create($user['id'], $tf->hashCode($code), 'email', $expiry);
                    $html = self::build2faEmailHtml($user['nombres'], $code, $expiry);
                    Mailer::send($user['email'], $user['nombres'], __('2fa.email_subject'), $html);
                }

                Audit::log(['module' => 'auth', 'action' => 'mfa.challenge_required',
                    'entity' => 'user', 'entity_id' => $user['id'],
                    'description' => "2FA requerido ({$user['two_factor_method']}) desde {$ip}",
                    'status' => 'info']);
                Redirect::to('/two-factor/challenge');
            }
        }

        Auth::login($user);

        Audit::log(['module' => 'auth', 'action' => 'auth.login_success',
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
        Audit::log(['module' => 'auth', 'action' => 'auth.logout',
            'entity' => 'user', 'entity_id' => $userId,
            'description' => 'Cierre de sesión', 'status' => 'success']);
        Auth::logout();
        Redirect::to('/login');
    }
}
