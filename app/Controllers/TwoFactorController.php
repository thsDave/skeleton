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
use App\Models\MfaSettings;
use App\Services\TwoFactorService;
use App\Services\Mailer;
use App\Services\SmsService;

class TwoFactorController extends Controller
{
    private User $userModel;
    private TwoFactorCode $codeModel;
    private TwoFactorService $tf;
    private MfaSettings $mfaModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->codeModel = new TwoFactorCode();
        $this->tf        = new TwoFactorService();
        $this->mfaModel  = new MfaSettings();
    }

    // GET /profile/two-factor
    public function show(): void
    {
        Auth::requireAuth();
        $authUser    = Auth::user();
        $user        = $this->userModel->findById($authUser['id']);
        $mfaSettings = $this->mfaModel->get();
        $pageTitle   = __('2fa.title');
        $activeMenu  = 'profile';
        require dirname(__DIR__) . '/Views/profile/two_factor.php';
    }

    // POST /profile/two-factor/enable-email
    public function enableEmail(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $mfaSettings = $this->mfaModel->get();
        if (empty($mfaSettings['email_enabled'])) {
            Session::flash('error', __('2fa.method_not_available'));
            Redirect::to('/profile/two-factor');
        }

        $authUser = Auth::user();
        $user     = $this->userModel->findById($authUser['id']);

        if (!$this->sendEmailCode($authUser['id'], $user['email'], $user['nombres'])) {
            Session::flash('error', __('2fa.code_send_failed'));
            Redirect::to('/profile/two-factor');
        }

        Session::set('tf_pending_method', 'email');
        Session::set('tf_pending_action', 'enable');
        Session::flash('success', __('2fa.code_sent_email'));
        Redirect::to('/profile/two-factor/confirm');
    }

    // POST /profile/two-factor/enable-sms
    public function enableSms(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $mfaSettings = $this->mfaModel->get();
        if (empty($mfaSettings['sms_enabled'])) {
            Session::flash('error', __('2fa.method_not_available'));
            Redirect::to('/profile/two-factor');
        }

        $phone = trim($this->input('phone', ''));
        if ($phone === '' || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
            Session::flash('error', __('2fa.phone_invalid'));
            Redirect::to('/profile/two-factor');
        }

        $authUser = Auth::user();

        if (!$this->sendSmsCode($authUser['id'], $phone)) {
            Session::flash('error', __('2fa.code_send_failed'));
            Redirect::to('/profile/two-factor');
        }

        Session::set('tf_pending_method', 'sms');
        Session::set('tf_pending_action', 'enable');
        Session::set('tf_pending_phone', $phone);
        Session::flash('success', __('2fa.code_sent_sms'));
        Redirect::to('/profile/two-factor/confirm');
    }

    // GET /profile/two-factor/confirm
    public function confirmForm(): void
    {
        Auth::requireAuth();
        $method = Session::get('tf_pending_method');
        if (!$method) {
            Redirect::to('/profile/two-factor');
        }
        $authUser   = Auth::user();
        $pageTitle  = __('2fa.confirm_title');
        $activeMenu = 'profile';
        require dirname(__DIR__) . '/Views/profile/two_factor_confirm.php';
    }

    // POST /profile/two-factor/confirm
    public function confirm(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $authUser = Auth::user();
        $code     = trim($this->input('code', ''));
        $method   = Session::get('tf_pending_method');

        if (!$method || !in_array($method, ['email', 'sms'], true)) {
            Session::flash('error', __('2fa.session_expired'));
            Redirect::to('/profile/two-factor');
        }

        $row = $this->codeModel->findValid($authUser['id'], $method);
        if (!$row) {
            Session::delete('tf_pending_method');
            Session::delete('tf_pending_action');
            Session::flash('error', __('2fa.code_expired'));
            Redirect::to('/profile/two-factor');
        }

        $maxAttempts = (int) ($_ENV['TWO_FACTOR_MAX_ATTEMPTS'] ?? 5);
        if ((int) $row['attempts'] >= $maxAttempts) {
            $this->codeModel->markUsed((int) $row['id']);
            Session::delete('tf_pending_method');
            Session::delete('tf_pending_action');
            Session::flash('error', __('2fa.max_attempts'));
            Redirect::to('/profile/two-factor');
        }

        if (!$this->tf->verifyCode($code, $row['code_hash'])) {
            $this->codeModel->incrementAttempts((int) $row['id']);
            Session::flash('error', __('2fa.code_invalid'));
            Redirect::to('/profile/two-factor/confirm');
        }

        $this->codeModel->markUsed((int) $row['id']);

        $phone = $method === 'sms' ? Session::get('tf_pending_phone') : null;
        $this->userModel->enableTwoFactor($authUser['id'], $method, null, $phone);

        Session::delete('tf_pending_method');
        Session::delete('tf_pending_action');
        Session::delete('tf_pending_phone');

        Audit::log([
            'module'      => 'profile',
            'action'      => '2fa_enabled',
            'entity'      => 'user',
            'entity_id'   => $authUser['id'],
            'description' => "2FA habilitado ({$method})",
            'status'      => 'success',
        ]);

        Session::flash('success', __('2fa.enabled_success'));
        Redirect::to('/profile/two-factor');
    }

    // POST /profile/two-factor/resend
    public function resend(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $authUser = Auth::user();
        $method   = Session::get('tf_pending_method');

        if (!$method || !in_array($method, ['email', 'sms'], true)) {
            Session::flash('error', __('2fa.session_expired'));
            Redirect::to('/profile/two-factor');
        }

        $lastSent   = $this->codeModel->getLastCreatedAt($authUser['id'], $method);
        $resendSecs = (int) ($_ENV['TWO_FACTOR_RESEND_SECONDS'] ?? 60);
        if ($lastSent && (time() - strtotime($lastSent)) < $resendSecs) {
            Session::flash('error', __('2fa.resend_too_soon', ['seconds' => $resendSecs]));
            Redirect::to('/profile/two-factor/confirm');
        }

        $user = $this->userModel->findById($authUser['id']);
        $sent = false;

        if ($method === 'email') {
            $sent = $this->sendEmailCode($authUser['id'], $user['email'], $user['nombres']);
        } elseif ($method === 'sms') {
            $phone = Session::get('tf_pending_phone') ?? $user['two_factor_phone'] ?? '';
            $sent  = $phone !== '' && $this->sendSmsCode($authUser['id'], $phone);
        }

        Session::flash($sent ? 'success' : 'error', $sent ? __('2fa.code_resent') : __('2fa.code_send_failed'));
        Redirect::to('/profile/two-factor/confirm');
    }

    // GET /profile/two-factor/setup-authenticator
    public function setupAuthenticator(): void
    {
        Auth::requireAuth();

        $mfaSettings = $this->mfaModel->get();
        if (empty($mfaSettings['authenticator_enabled'])) {
            Session::flash('error', __('2fa.method_not_available'));
            Redirect::to('/profile/two-factor');
        }

        $authUser = Auth::user();
        $user     = $this->userModel->findById($authUser['id']);

        $secret  = $this->tf->generateSecret();
        Session::set('tf_totp_secret_pending', $secret);

        $qrSvg      = $this->tf->generateQrSvg($user['email'], $secret);
        $pageTitle  = __('2fa.setup_authenticator');
        $activeMenu = 'profile';
        require dirname(__DIR__) . '/Views/profile/two_factor_setup_authenticator.php';
    }

    // POST /profile/two-factor/confirm-authenticator
    public function confirmAuthenticator(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $authUser = Auth::user();
        $code     = preg_replace('/\s+/', '', $this->input('code', ''));
        $secret   = Session::get('tf_totp_secret_pending');

        if (!$secret) {
            Session::flash('error', __('2fa.setup_expired'));
            Redirect::to('/profile/two-factor');
        }

        if (!$this->tf->verifyTotp($secret, $code)) {
            Session::flash('error', __('2fa.code_invalid'));
            Redirect::to('/profile/two-factor/setup-authenticator');
        }

        $secretEnc = Crypt::encrypt($secret);
        $this->userModel->enableTwoFactor($authUser['id'], 'authenticator', $secretEnc);
        Session::delete('tf_totp_secret_pending');

        Audit::log([
            'module'      => 'profile',
            'action'      => '2fa_enabled',
            'entity'      => 'user',
            'entity_id'   => $authUser['id'],
            'description' => '2FA habilitado (authenticator)',
            'status'      => 'success',
        ]);

        Session::flash('success', __('2fa.enabled_success'));
        Redirect::to('/profile/two-factor');
    }

    // POST /profile/two-factor/disable
    public function disable(): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $authUser = Auth::user();
        $this->userModel->disableTwoFactor($authUser['id']);

        Audit::log([
            'module'      => 'profile',
            'action'      => '2fa_disabled',
            'entity'      => 'user',
            'entity_id'   => $authUser['id'],
            'description' => '2FA deshabilitado',
            'status'      => 'success',
        ]);

        Session::flash('success', __('2fa.disabled_success'));
        Redirect::to('/profile/two-factor');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

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
