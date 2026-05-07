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
use App\Services\SmsService;

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

        if (!$userId || !in_array($method, ['email', 'sms'], true)) {
            Redirect::to('/login');
        }

        CSRF::validateOrFail();

        $lastSent   = $this->codeModel->getLastCreatedAt((int) $userId, $method);
        $resendSecs = (int) ($_ENV['TWO_FACTOR_RESEND_SECONDS'] ?? 60);
        if ($lastSent && (time() - strtotime($lastSent)) < $resendSecs) {
            Session::flash('error', __('2fa.resend_too_soon', ['seconds' => $resendSecs]));
            Redirect::to('/two-factor/challenge');
        }

        $user = $this->userModel->findById((int) $userId);
        $sent = false;

        if ($method === 'email') {
            $sent = $this->sendEmailCode((int) $userId, $user['email'], $user['nombres']);
        } elseif ($method === 'sms') {
            $phone = $user['two_factor_phone'] ?? '';
            $sent  = $phone !== '' && $this->sendSmsCode((int) $userId, $phone);
        }

        Session::flash($sent ? 'success' : 'error', $sent ? __('2fa.code_resent') : __('2fa.code_send_failed'));
        Redirect::to('/two-factor/challenge');
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    private function verifyTotp(array $user, string $code): void
    {
        $secretEnc = $user['two_factor_secret_enc'] ?? '';
        $secret    = $secretEnc !== '' ? Crypt::decrypt($secretEnc) : '';

        if ($secret === '' || !$this->tf->verifyTotp($secret, $code)) {
            Session::flash('error', __('2fa.code_invalid'));
            Redirect::to('/two-factor/challenge');
        }

        $this->completeLogin($user);
    }

    private function verifyOtpCode(array $user, string $method, string $code): void
    {
        $row = $this->codeModel->findValid((int) $user['id'], $method);

        if (!$row) {
            Session::flash('error', __('2fa.code_expired'));
            Redirect::to('/two-factor/challenge');
        }

        $maxAttempts = (int) ($_ENV['TWO_FACTOR_MAX_ATTEMPTS'] ?? 5);
        if ((int) $row['attempts'] >= $maxAttempts) {
            $this->codeModel->markUsed((int) $row['id']);
            $this->clearPendingState();
            Session::flash('error', __('2fa.max_attempts'));
            Redirect::to('/login');
        }

        if (!$this->tf->verifyCode($code, $row['code_hash'])) {
            $this->codeModel->incrementAttempts((int) $row['id']);
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
            'action'      => 'login_2fa_success',
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

    private function sendSmsCode(int $userId, string $phone): bool
    {
        $expiry  = (int) ($_ENV['TWO_FACTOR_CODE_EXPIRATION_MINUTES'] ?? 10);
        $code    = $this->tf->generateNumericCode();
        $hash    = $this->tf->hashCode($code);

        $this->codeModel->deleteForUser($userId, 'sms');
        $this->codeModel->create($userId, $hash, 'sms', $expiry);

        $message = __('2fa.sms_body', ['code' => $code, 'minutes' => $expiry]);
        return SmsService::send($phone, $message);
    }
}
