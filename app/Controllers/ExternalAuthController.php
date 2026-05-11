<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\Logger;
use Core\Redirect;
use Core\Session;
use App\Models\AuthenticationSettings;
use App\Models\ExternalAuthProvider;
use App\Models\UserExternalAccount;
use App\Models\User;
use App\Models\TwoFactorCode;
use App\Services\ExternalAuthService;
use App\Services\Mailer;
use App\Services\TwoFactorService;

class ExternalAuthController extends Controller
{
    private const ALLOWED = ['google', 'microsoft', 'github'];

    public function redirect(string $provider): void
    {
        if (!in_array($provider, self::ALLOWED, true)) {
            Redirect::to('/login');
        }

        $action = $_GET['action'] ?? 'login';

        if ($action === 'link' && !Auth::check()) {
            Redirect::to('/login');
        }

        $settings = (new AuthenticationSettings())->get();
        if (!$settings['external_login_enabled']) {
            Session::flash('error', __('auth.external_provider_disabled'));
            Redirect::to('/login');
        }

        $providerRow = (new ExternalAuthProvider())->findBySlug($provider);
        if (!$providerRow || !$providerRow['is_enabled']) {
            Session::flash('error', __('auth.external_provider_disabled'));
            Redirect::to('/login');
        }

        if (empty($providerRow['client_id']) || empty($providerRow['client_secret'])) {
            Logger::error("ExternalAuthController::redirect — {$provider} not configured");
            Session::flash('error', __('auth.external_login_failed'));
            Redirect::to('/login');
        }

        $secret = (new ExternalAuthProvider())->decryptSecret($providerRow['client_secret']);
        if (empty($secret)) {
            Logger::error("ExternalAuthController::redirect — cannot decrypt secret for {$provider}");
            Session::flash('error', __('auth.external_login_failed'));
            Redirect::to('/login');
        }

        $service   = new ExternalAuthService();
        $oauthProv = $service->buildProvider($providerRow, $secret);
        $state     = bin2hex(random_bytes(32));

        Session::set("oauth_state_{$provider}", $state);
        Session::set("oauth_action_{$provider}", $action);

        $scopes = $providerRow['scopes'] ? $service->normalizeScopes($providerRow['scopes']) : [];

        $authUrl = $oauthProv->getAuthorizationUrl([
            'state' => $state,
            'scope' => $scopes,
        ]);

        Audit::log([
            'module'      => 'auth',
            'action'      => 'external_login.started',
            'description' => "Inicio OAuth con proveedor: {$provider} (acción: {$action})",
            'status'      => 'pending',
            'user_id'     => Auth::check() ? Auth::id() : null,
        ]);

        header('Location: ' . $authUrl);
        exit;
    }

    public function callback(string $provider): void
    {
        if (!in_array($provider, self::ALLOWED, true)) {
            Redirect::to('/login');
        }

        $sessionState  = Session::get("oauth_state_{$provider}");
        $receivedState = $_GET['state'] ?? '';

        if (empty($sessionState) || !hash_equals($sessionState, $receivedState)) {
            Logger::security("ExternalAuthController::callback — invalid state for {$provider}");
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.invalid_state',
                'description' => "State OAuth inválido para proveedor: {$provider}",
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_login_failed')]);
        }

        Session::forget("oauth_state_{$provider}");
        $action = Session::get("oauth_action_{$provider}", 'login');
        Session::forget("oauth_action_{$provider}");

        $isAdminTest = ($action === 'admin_test');

        if (isset($_GET['error'])) {
            $errDesc = $_GET['error_description'] ?? $_GET['error'];
            Logger::error("ExternalAuthController::callback — provider error [{$provider}]: {$errDesc}");
            if ($isAdminTest) {
                $testId = Session::get('oauth_admin_test_id');
                Session::forget('oauth_admin_test_id');
                if ($testId) {
                    (new ExternalAuthProvider())->updateTestResult(
                        (int) $testId, false,
                        __('security_authentication.test_provider_denied')
                    );
                }
                Audit::log([
                    'module'      => 'security_authentication',
                    'action'      => 'external_provider.test_failed',
                    'description' => "Proveedor {$provider} rechazó la prueba OAuth",
                    'status'      => 'warning',
                    'user_id'     => Auth::check() ? Auth::id() : null,
                ]);
                Session::flash('error', __('security_authentication.provider_test_failed') . ' — ' . __('security_authentication.test_provider_denied'));
                Redirect::to('/security/authentication');
            }
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.denied',
                'description' => "Proveedor {$provider} rechazó la autorización",
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_login_denied')]);
        }

        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            Logger::error("ExternalAuthController::callback — no code from {$provider}");
            if ($isAdminTest) {
                Session::forget('oauth_admin_test_id');
                Session::flash('error', __('security_authentication.provider_test_failed'));
                Redirect::to('/security/authentication');
            }
            Redirect::withErrors('/login', ['general' => __('auth.external_login_failed')]);
        }

        // Admin test bypasses external_login_enabled and is_enabled checks
        if ($isAdminTest) {
            if (!Auth::check()) {
                Session::forget('oauth_admin_test_id');
                Redirect::withErrors('/login', ['general' => __('auth.external_login_failed')]);
            }
            $settings = (new AuthenticationSettings())->get();
        } else {
            $settings = (new AuthenticationSettings())->get();
            if (!$settings['external_login_enabled']) {
                Logger::security("ExternalAuthController::callback — external login disabled, provider: {$provider}");
                Audit::log([
                    'module'      => 'auth',
                    'action'      => 'external_login.provider_disabled',
                    'description' => "Login externo deshabilitado, proveedor: {$provider}",
                    'status'      => 'denied',
                    'user_id'     => null,
                ]);
                Redirect::withErrors('/login', ['general' => __('auth.external_provider_disabled')]);
            }
        }

        $providerModel = new ExternalAuthProvider();
        $providerRow   = $providerModel->findBySlug($provider);
        if (!$providerRow || (!$isAdminTest && !$providerRow['is_enabled'])) {
            if ($isAdminTest) {
                Session::forget('oauth_admin_test_id');
                Session::flash('error', __('security_authentication.provider_not_found'));
                Redirect::to('/security/authentication');
            }
            Logger::security("ExternalAuthController::callback — provider disabled or not found: {$provider}");
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.provider_disabled',
                'description' => "Proveedor deshabilitado en callback: {$provider}",
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_provider_disabled')]);
        }

        try {
            $secret = $providerModel->decryptSecret($providerRow['client_secret'] ?? '');
            if (empty($secret)) {
                throw new \RuntimeException('Cannot decrypt provider secret');
            }

            $service    = new ExternalAuthService();
            $oauthProv  = $service->buildProvider($providerRow, $secret);
            $tokenObj   = $oauthProv->getAccessToken('authorization_code', ['code' => $code]);
            $accessToken = $tokenObj->getToken();

            $userInfo = $service->fetchUserInfo(
                $provider,
                $accessToken,
                $providerRow['userinfo_url'] ?? '',
                $providerRow['tenant_id'] ?? null
            );

            if (empty($userInfo)) {
                throw new \RuntimeException('Empty user info from provider');
            }

            $providerUserId = $service->extractUserId($provider, $userInfo);
            $providerEmail  = $service->extractEmail($provider, $userInfo, $accessToken);
            $providerName   = $service->extractName($provider, $userInfo);
            $avatarUrl      = $service->extractAvatar($provider, $userInfo);

            if (empty($providerUserId)) {
                throw new \RuntimeException('No user ID returned by provider');
            }

            if (empty($providerEmail)) {
                Logger::security("ExternalAuthController::callback — no email from provider [{$provider}]");
                Audit::log([
                    'module'      => 'auth',
                    'action'      => 'external_login.email_not_verified',
                    'description' => "Sin correo verificado del proveedor: {$provider}",
                    'status'      => 'denied',
                    'user_id'     => null,
                ]);
                Redirect::withErrors('/login', ['general' => __('auth.external_account_not_authorized')]);
            }

        } catch (\Throwable $e) {
            Logger::error("ExternalAuthController::callback exception [{$provider}]: " . $e->getMessage());
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.failed',
                'description' => "Error en callback OAuth [{$provider}]",
                'status'      => 'failed',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_login_failed')]);
        }

        // Domain restriction — applies to login and account linking, not admin test
        if (!$isAdminTest && !(new AuthenticationSettings())->isDomainAllowed($providerEmail, $settings)) {
            $emailDomain = strtolower(substr($providerEmail, (int) strrpos($providerEmail, '@') + 1));
            Logger::security("ExternalAuthController::callback — domain not allowed [{$provider}] {$emailDomain}");
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.domain_denied',
                'description' => "Login externo rechazado por dominio no autorizado: {$emailDomain} [{$provider}]",
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_account_not_authorized')]);
        }

        if ($isAdminTest) {
            $this->handleAdminTest($provider, $providerRow, $providerUserId, $providerEmail);
            return;
        }

        if ($action === 'link') {
            $this->handleLinking(
                $settings, $providerRow, $providerUserId,
                $providerEmail, $providerName, $avatarUrl, $provider
            );
            return;
        }

        $this->handleLogin(
            $settings, $providerRow, $providerUserId,
            $providerEmail, $providerName, $avatarUrl, $provider
        );
    }

    private function handleAdminTest(
        string $provider,
        array  $providerRow,
        string $providerUserId,
        string $providerEmail
    ): void {
        $adminUserId = Auth::id();
        $testId      = (int) (Session::get('oauth_admin_test_id') ?? $providerRow['id']);
        Session::forget('oauth_admin_test_id');

        $providerModel = new ExternalAuthProvider();
        $msg = __('security_authentication.test_oauth_success_msg') . ' — ' . $providerEmail;
        $providerModel->markVerified($testId);
        $providerModel->updateTestResult($testId, true, $msg);

        Audit::log([
            'module'      => 'security_authentication',
            'action'      => 'external_provider.test_success',
            'entity'      => 'external_auth_provider',
            'entity_id'   => $testId,
            'description' => "Prueba OAuth real exitosa para {$providerRow['name']}",
            'status'      => 'success',
            'user_id'     => $adminUserId,
        ]);
        Audit::log([
            'module'      => 'security_authentication',
            'action'      => 'external_provider.verified',
            'entity'      => 'external_auth_provider',
            'entity_id'   => $testId,
            'description' => "Proveedor {$providerRow['name']} verificado mediante flujo OAuth real",
            'status'      => 'success',
            'user_id'     => $adminUserId,
        ]);

        Session::flash('success', __('security_authentication.provider_test_success'));
        Redirect::to('/security/authentication');
    }

    private function handleLogin(
        array $settings,
        array $providerRow,
        string $providerUserId,
        string $providerEmail,
        string $providerName,
        ?string $avatarUrl,
        string $provider
    ): void {
        $linkModel    = new UserExternalAccount();
        $userModel    = new User();

        $linked = $linkModel->findByProviderUser((int) $providerRow['id'], $providerUserId);

        if ($linked) {
            $user = $userModel->findById((int) $linked['user_id']);
            if (!$user || ($user['status_slug'] ?? '') !== 'active') {
                Logger::security("ExternalAuthController::handleLogin — inactive or missing user [{$provider}] uid={$linked['user_id']}");
                Audit::log([
                    'module'      => 'auth',
                    'action'      => 'external_login.failed',
                    'description' => "Usuario inactivo o no encontrado en login externo [{$provider}]",
                    'status'      => 'denied',
                    'user_id'     => $linked['user_id'] ?? null,
                ]);
                Redirect::withErrors('/login', ['general' => __('auth.login_invalid_credentials')]);
            }
            $linkModel->updateLastLogin((int) $linked['user_id'], (int) $providerRow['id']);
            (new ExternalAuthProvider())->markVerified((int) $providerRow['id']);
            $this->completeLogin($user, $provider, $providerEmail);
            return;
        }

        $userByEmail = $userModel->findByEmail($providerEmail);

        if ($userByEmail && $settings['allow_account_linking']) {
            if (($userByEmail['status_slug'] ?? '') !== 'active') {
                Logger::security("ExternalAuthController::handleLogin — inactive user by email [{$provider}] {$providerEmail}");
                Redirect::withErrors('/login', ['general' => __('auth.login_invalid_credentials')]);
            }
            $linkModel->create([
                'user_id'          => $userByEmail['id'],
                'provider_id'      => $providerRow['id'],
                'provider_user_id' => $providerUserId,
                'provider_email'   => $providerEmail,
                'provider_name'    => $providerName,
                'avatar_url'       => $avatarUrl,
            ]);
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.account_linked',
                'entity'      => 'user',
                'entity_id'   => $userByEmail['id'],
                'description' => "Cuenta vinculada automáticamente con {$provider} en el login",
                'status'      => 'success',
                'user_id'     => $userByEmail['id'],
            ]);
            (new ExternalAuthProvider())->markVerified((int) $providerRow['id']);
            $this->completeLogin($userByEmail, $provider, $providerEmail);
            return;
        }

        if ($settings['require_existing_user'] || !$settings['allow_auto_user_creation']) {
            Logger::security("ExternalAuthController::handleLogin — user not found [{$provider}] {$providerEmail}");
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.user_not_found',
                'description' => "Usuario no encontrado para {$providerEmail} desde {$provider}",
                'status'      => 'denied',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_account_not_authorized')]);
        }

        $defaultRoleId = (int) ($settings['default_role_id'] ?? 2);
        if (!$defaultRoleId) {
            Logger::error("ExternalAuthController::handleLogin — no default role configured for auto-create [{$provider}]");
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.failed',
                'description' => "Sin rol por defecto para crear usuario desde {$provider}",
                'status'      => 'failed',
                'user_id'     => null,
            ]);
            Redirect::withErrors('/login', ['general' => __('auth.external_login_failed')]);
        }

        $nameParts = explode(' ', $providerName, 2);
        $nombres   = $nameParts[0] ?? $providerEmail;
        $apellidos = $nameParts[1] ?? '';

        $newUserId = $userModel->create([
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'email'     => $providerEmail,
            'password'  => password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]),
            'role_id'   => $defaultRoleId,
            'status_id' => 1,
        ]);

        if (!$newUserId) {
            Logger::error("ExternalAuthController::handleLogin — could not create user for {$providerEmail}");
            Redirect::withErrors('/login', ['general' => __('auth.external_login_failed')]);
        }

        $linkModel->create([
            'user_id'          => $newUserId,
            'provider_id'      => $providerRow['id'],
            'provider_user_id' => $providerUserId,
            'provider_email'   => $providerEmail,
            'provider_name'    => $providerName,
            'avatar_url'       => $avatarUrl,
        ]);

        Audit::log([
            'module'      => 'auth',
            'action'      => 'external_login.auto_user_created',
            'entity'      => 'user',
            'entity_id'   => $newUserId,
            'description' => "Usuario creado automáticamente desde {$provider}: {$providerEmail}",
            'status'      => 'success',
            'user_id'     => $newUserId,
        ]);

        (new ExternalAuthProvider())->markVerified((int) $providerRow['id']);
        $newUser = $userModel->findById($newUserId);
        $this->completeLogin($newUser, $provider, $providerEmail);
    }

    private function completeLogin(array $user, string $provider, string $email): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        (new User())->updateLastLogin($user['id'], $ip);

        Audit::log([
            'module'      => 'auth',
            'action'      => 'external_login.success',
            'entity'      => 'user',
            'entity_id'   => $user['id'],
            'description' => "Login exitoso via {$provider} ({$email}) desde {$ip}",
            'status'      => 'success',
            'user_id'     => $user['id'],
        ]);

        if (!empty($user['two_factor_enabled']) && !empty($user['two_factor_method'])) {
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
                    $tf    = new TwoFactorService();
                    $codes = new TwoFactorCode();
                    $expiry = (int) env('TWO_FACTOR_CODE_EXPIRATION_MINUTES', 10);
                    $code  = $tf->generateNumericCode();
                    $codes->deleteForUser($user['id'], 'email');
                    $codes->create($user['id'], $tf->hashCode($code), 'email', $expiry);

                    $html = '<div style="font-family:sans-serif;max-width:520px;margin:auto;padding:24px;">'
                          . '<h2>' . __('2fa.email_subject') . '</h2>'
                          . '<p>Hola <strong>' . htmlspecialchars($user['nombres'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                          . '<p>' . __('2fa.email_intro') . '</p>'
                          . '<div style="text-align:center;margin:24px 0;padding:20px;background:#f0f4ff;border-radius:10px;border:2px dashed #0d6efd;">'
                          . '<span style="font-size:40px;font-weight:800;letter-spacing:12px;color:#0d6efd;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</span>'
                          . '</div>'
                          . '<p style="color:#666;font-size:13px;">' . __('2fa.email_expiry', ['minutes' => $expiry]) . '</p>'
                          . '</div>';
                    Mailer::send($user['email'], $user['nombres'], __('2fa.email_subject'), $html);
                }

                Redirect::to('/two-factor/challenge');
            }
        }

        Auth::login($user);
        Redirect::to('/dashboard');
    }

    private function handleLinking(
        array $settings,
        array $providerRow,
        string $providerUserId,
        string $providerEmail,
        string $providerName,
        ?string $avatarUrl,
        string $provider
    ): void {
        if (!Auth::check()) {
            Redirect::to('/login');
        }

        $userId    = Auth::id();
        $linkModel = new UserExternalAccount();

        $existing = $linkModel->findByProviderUser((int) $providerRow['id'], $providerUserId);
        if ($existing) {
            if ((int) $existing['user_id'] === $userId) {
                Session::flash('error', __('account.provider_already_linked'));
            } else {
                Session::flash('error', __('account.provider_linked_other_account'));
            }
            Redirect::to('/account');
        }

        $alreadyLinked = $linkModel->findByUserAndProvider($userId, (int) $providerRow['id']);
        if ($alreadyLinked) {
            Session::flash('error', __('account.provider_already_linked'));
            Redirect::to('/account');
        }

        $linkModel->create([
            'user_id'          => $userId,
            'provider_id'      => $providerRow['id'],
            'provider_user_id' => $providerUserId,
            'provider_email'   => $providerEmail,
            'provider_name'    => $providerName,
            'avatar_url'       => $avatarUrl,
        ]);

        (new ExternalAuthProvider())->markVerified((int) $providerRow['id']);

        Audit::log([
            'module'      => 'auth',
            'action'      => 'external_login.account_linked',
            'entity'      => 'user',
            'entity_id'   => $userId,
            'description' => "Cuenta vinculada manualmente con {$provider} ({$providerEmail})",
            'status'      => 'success',
            'user_id'     => $userId,
        ]);

        Session::flash('success', __('account.provider_linked'));
        Redirect::to('/account');
    }

}
