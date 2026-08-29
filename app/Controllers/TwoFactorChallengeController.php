<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Session;
use Core\Audit;
use Core\Crypt;
use App\Models\User;
use App\Models\TwoFactorCode;
use App\Services\TwoFactorService;
use App\Services\Mailer;
use App\Services\RateLimitService;

class TwoFactorChallengeController extends Controller
{
    private User $userModel;
    private TwoFactorCode $codeModel;
    private TwoFactorService $tf;

    public function __construct()
    {
        $this->userModel = new User();
        $this->codeModel = new TwoFactorCode();
        $this->tf        = new TwoFactorService();
    }

    // GET /two-factor/challenge
    public function show(): void
    {
        if (Auth::check()) {
            Redirect::to('/dashboard');
        }

        $userId = Session::get('pending_2fa_user_id');
        $method = Session::get('pending_2fa_method');

        if (!$userId || !$method) {
            Redirect::to('/login');
        }

        // Block legacy SMS method
        if ($method === 'sms') {
            $this->clearPendingState();
            Redirect::withErrors('/login',
                ['general' => 'Tu método de verificación en 2 pasos ya no está disponible. Contacta al administrador.'],
                []);
        }

        $pageTitle = __('2fa.challenge_title');
        require dirname(__DIR__) . '/Views/auth/two_factor_challenge.php';
    }

    // POST /two-factor/challenge
    public function verify(): void
    {
        if (Auth::check()) {
            Redirect::to('/dashboard');
        }

        $userId = Session::get('pending_2fa_user_id');
        $method = Session::get('pending_2fa_method');

        if (!$userId || !$method) {
            Redirect::to('/login');
        }

        // Block legacy SMS method
        if ($method === 'sms') {
            $this->clearPendingState();
            Redirect::to('/login');
        }

        CSRF::validateOrFail();

        $code = preg_replace('/\s+/', '', $this->input('code', ''));
        $user = $this->userModel->findById((int) $userId);

        if (!$user) {
            $this->clearPendingState();
            Redirect::to('/login');
        }

        if ($method === 'authenticator') {
            $this->verifyTotp($user, $code);
        } else {
            $this->verifyOtpCode($user, $method, $code);
        }
    }

    // POST /two-factor/resend
    public function resend(): void
    {
        $userId = Session::get('pending_2fa_user_id');
        $method = Session::get('pending_2fa_method');

        // Only email supports resend
        if (!$userId || $method !== 'email') {
            Redirect::to('/login');
        }

        CSRF::validateOrFail();

        $lastSent   = $this->codeModel->getLastCreatedAt((int) $userId, 'email');
        $resendSecs = (int) ($_ENV['TWO_FACTOR_RESEND_SECONDS'] ?? 60);
        if ($lastSent && (time() - strtotime($lastSent)) < $resendSecs) {
            Session::flash('error', __('2fa.resend_too_soon', ['seconds' => $resendSecs]));
            Redirect::to('/two-factor/challenge');
        }

        $user = $this->userModel->findById((int) $userId);
        $sent = $this->sendEmailCode((int) $userId, $user['email'], $user['nombres']);

        Audit::log([
            'module'      => 'auth',
            'action'      => $sent ? 'mfa.code_resent' : 'mfa.code_resend_failed',
            'entity'      => 'user',
            'entity_id'   => (int)$userId,
            'description' => $sent ? 'Codigo MFA reenviado por correo' : 'No se pudo reenviar codigo MFA por correo',
            'status'      => $sent ? 'success' : 'failed',
        ]);

        Session::flash($sent ? 'success' : 'error', $sent ? __('2fa.code_resent') : __('2fa.code_send_failed'));
        Redirect::to('/two-factor/challenge');
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    // Rate limit de la verificacion TOTP (Etapa 3.2). Limite inicial
    // aprobado: 5 intentos / 10 minutos, por usuario. Reutiliza
    // TWO_FACTOR_MAX_ATTEMPTS (ya usado para el mismo proposito en el
    // OTP por correo) para no introducir un segundo numero de intentos
    // distinto sin necesidad. La ventana no tiene variable de entorno
    // existente reutilizable con el significado correcto, por lo que se
    // usa una nueva variable opcional (TWO_FACTOR_TOTP_WINDOW_SECONDS,
    // con fallback 600) en vez de modificar .env/.env.example en esta
    // etapa.
    private const TOTP_RATE_LIMIT_ACTION = 'mfa.totp_challenge';
    private const TOTP_RATE_LIMIT_IDENTIFIER_TYPE = 'user';

    private function verifyTotp(array $user, string $code): void
    {
        $rateLimiter   = new RateLimitService();
        $identifier    = (string) $user['id'];
        $maxAttempts   = (int) env('TWO_FACTOR_MAX_ATTEMPTS', 5);
        $windowSeconds = (int) env('TWO_FACTOR_TOTP_WINDOW_SECONDS', 600);

        // ── Bloqueado por intentos previos: no validar el codigo ────────────────
        if ($rateLimiter->tooManyAttempts(
            self::TOTP_RATE_LIMIT_ACTION,
            $identifier,
            $maxAttempts,
            $windowSeconds,
            self::TOTP_RATE_LIMIT_IDENTIFIER_TYPE
        )) {
            $availableIn = $rateLimiter->availableIn(
                self::TOTP_RATE_LIMIT_ACTION,
                $identifier,
                self::TOTP_RATE_LIMIT_IDENTIFIER_TYPE
            );

            Audit::log([
                'module'      => 'auth',
                'action'      => 'mfa.totp_rate_limited',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => 'Intento de verificacion TOTP mientras el usuario esta bloqueado temporalmente',
                'status'      => 'denied',
                'new_values'  => [
                    'action'          => self::TOTP_RATE_LIMIT_ACTION,
                    'identifier_type' => self::TOTP_RATE_LIMIT_IDENTIFIER_TYPE,
                    'available_in'    => $availableIn,
                ],
            ]);

            Session::flash('error', __('2fa.totp_too_many_attempts', ['seconds' => $availableIn]));
            Redirect::to('/two-factor/challenge');
        }

        $secretEnc = $user['two_factor_secret_enc'] ?? '';
        $secret    = $secretEnc !== '' ? Crypt::decrypt($secretEnc) : '';

        if ($secret === '' || !$this->tf->verifyTotp($secret, $code)) {
            $result = $rateLimiter->hit(
                self::TOTP_RATE_LIMIT_ACTION,
                $identifier,
                self::TOTP_RATE_LIMIT_IDENTIFIER_TYPE,
                $maxAttempts,
                $windowSeconds
            );

            Audit::log([
                'module'      => 'auth',
                'action'      => 'mfa.challenge_failed',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => 'Verificacion MFA fallida (authenticator)',
                'status'      => 'failed',
            ]);

            if ($result['blocked']) {
                Audit::log([
                    'module'      => 'auth',
                    'action'      => 'mfa.totp_rate_limited',
                    'entity'      => 'user',
                    'entity_id'   => $user['id'],
                    'description' => 'Verificacion TOTP bloqueada temporalmente por exceso de intentos',
                    'status'      => 'denied',
                    'new_values'  => [
                        'action'          => self::TOTP_RATE_LIMIT_ACTION,
                        'identifier_type' => self::TOTP_RATE_LIMIT_IDENTIFIER_TYPE,
                        'attempts'        => $result['attempts'],
                        'available_in'    => $result['available_in'],
                    ],
                ]);
                Session::flash('error', __('2fa.totp_too_many_attempts', ['seconds' => $result['available_in']]));
            } else {
                Session::flash('error', __('2fa.totp_invalid_remaining', ['remaining' => $result['remaining']]));
            }

            Redirect::to('/two-factor/challenge');
        }

        $rateLimiter->clear(self::TOTP_RATE_LIMIT_ACTION, $identifier, self::TOTP_RATE_LIMIT_IDENTIFIER_TYPE);

        $this->completeLogin($user);
    }

    private function verifyOtpCode(array $user, string $method, string $code): void
    {
        $row = $this->codeModel->findValid((int) $user['id'], $method);

        if (!$row) {
            Audit::log([
                'module'      => 'auth',
                'action'      => 'mfa.challenge_failed',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => "Codigo MFA expirado o inexistente ({$method})",
                'status'      => 'failed',
            ]);
            Session::flash('error', __('2fa.code_expired'));
            Redirect::to('/two-factor/challenge');
        }

        $maxAttempts = (int) ($_ENV['TWO_FACTOR_MAX_ATTEMPTS'] ?? 5);
        if ((int) $row['attempts'] >= $maxAttempts) {
            $this->codeModel->markUsed((int) $row['id']);
            $this->clearPendingState();
            Audit::log([
                'module'      => 'auth',
                'action'      => 'mfa.challenge_failed',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => "MFA bloqueado por maximo de intentos ({$method})",
                'status'      => 'denied',
            ]);
            Session::flash('error', __('2fa.max_attempts'));
            Redirect::to('/login');
        }

        if (!$this->tf->verifyCode($code, $row['code_hash'])) {
            $this->codeModel->incrementAttempts((int) $row['id']);
            Audit::log([
                'module'      => 'auth',
                'action'      => 'mfa.challenge_failed',
                'entity'      => 'user',
                'entity_id'   => $user['id'],
                'description' => "Verificacion MFA fallida ({$method})",
                'status'      => 'failed',
            ]);
            Session::flash('error', __('2fa.code_invalid'));
            Redirect::to('/two-factor/challenge');
        }

        $this->codeModel->markUsed((int) $row['id']);
        $this->completeLogin($user);
    }

    private function completeLogin(array $user): void
    {
        $method = $user['two_factor_method'] ?? 'unknown';
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';

        $this->clearPendingState();
        Auth::login($user);

        Audit::log([
            'module'      => 'auth',
            'action'      => 'mfa.challenge_success',
            'entity'      => 'user',
            'entity_id'   => $user['id'],
            'description' => "Login con 2FA completado ({$method}) desde {$ip}",
            'status'      => 'success',
        ]);

        Redirect::to('/dashboard');
    }

    private function clearPendingState(): void
    {
        Session::delete('pending_2fa_user_id');
        Session::delete('pending_2fa_method');
    }

    private function sendEmailCode(int $userId, string $email, string $nombre): bool
    {
        $expiry = (int) ($_ENV['TWO_FACTOR_CODE_EXPIRATION_MINUTES'] ?? 10);
        $code   = $this->tf->generateNumericCode();
        $hash   = $this->tf->hashCode($code);

        $this->codeModel->deleteForUser($userId, 'email');
        $this->codeModel->create($userId, $hash, 'email', $expiry);

        ob_start();
        require dirname(__DIR__) . '/Views/emails/two_factor_code.php';
        $html = (string) ob_get_clean();

        return Mailer::send($email, $nombre, __('2fa.email_subject'), $html);
    }
}
