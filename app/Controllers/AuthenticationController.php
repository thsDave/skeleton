<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\Logger;
use Core\Redirect;
use Core\Session;
use App\Models\AuthenticationSettings;
use App\Models\ExternalAuthProvider;
use App\Models\Role;
use App\Services\ExternalAuthService;
use App\Services\RateLimitService;

class AuthenticationController extends Controller
{
    // Rate limit de la prueba administrativa de proveedor OAuth (Etapa
    // 3.7). Limite aprobado: 5 pruebas / 5 minutos, por administrador
    // (accion general, no separada por proveedor — ver TXT de la
    // etapa). Evita generar estados/callbacks repetidos hacia el
    // proveedor externo desde una cuenta admin. No afecta el login
    // OAuth normal de usuarios finales (ExternalAuthController), que
    // es un flujo y un endpoint completamente distintos.
    private const OAUTH_TEST_RATE_LIMIT_ACTION = 'admin.oauth_test';
    private const OAUTH_TEST_RATE_LIMIT_IDENTIFIER_TYPE = 'user';
    private const OAUTH_TEST_RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const OAUTH_TEST_RATE_LIMIT_WINDOW_SECONDS = 300;

    public function index(): void
    {
        Auth::requirePermission('security_authentication.view');
        $authUser                = Auth::user();
        $settings                = (new AuthenticationSettings())->get();
        $providerModel           = new ExternalAuthProvider();
        $providers               = $providerModel->all();
        $providersReadyCount     = $providerModel->countReady();
        $providersVerifiedCount  = $providerModel->countVerifiedAndReady();
        $roles                   = (new Role())->getAll();
        $errors                  = Session::getFlash('errors', []);
        $old                     = Session::getFlash('old', []);
        $this->view('security.authentication.index',
            compact('authUser', 'settings', 'providers', 'providersReadyCount', 'providersVerifiedCount', 'roles', 'errors', 'old'));
    }

    public function updateSettings(): void
    {
        Auth::requirePermission('security_authentication.edit');
        CSRF::validateOrFail();

        $localEnabled    = !empty($_POST['local_login_enabled']);
        $externalEnabled = !empty($_POST['external_login_enabled']);

        if (!$localEnabled && !$externalEnabled) {
            Session::flash('error', __('security_authentication.error_no_methods'));
            Redirect::to('/security/authentication');
        }

        if ($externalEnabled) {
            $verifiedCount = (new ExternalAuthProvider())->countVerifiedAndReady();
            if ($verifiedCount === 0) {
                try {
                    Audit::log([
                        'module'      => 'security_authentication',
                        'action'      => 'authentication.settings_update_failed',
                        'description' => 'Intento de activar login externo sin proveedor verificado activo',
                        'status'      => 'warning',
                    ]);
                } catch (\Throwable) {}
                Session::flash('error', __('security_authentication.external_provider_required'));
                Redirect::to('/security/authentication');
            }
        }

        // ── Domain restriction validation and normalization ──────────────────────
        $restrictDomains   = !empty($_POST['restrict_external_domains']) ? 1 : 0;
        $allowedDomainsRaw = trim($_POST['allowed_external_domains'] ?? '');
        $normalizedDomains = '';

        if ($allowedDomainsRaw !== '') {
            $parts   = preg_split('/[\n\r,]+/', $allowedDomainsRaw);
            $domains = [];
            foreach ($parts as $part) {
                $d = strtolower(trim($part));
                if ($d === '' || str_contains($d, '@')) continue;
                if (preg_match('#^https?://#i', $d)) continue;
                // Basic domain format: labels separated by dots, no leading/trailing hyphens
                if (!preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)+$/', $d)) continue;
                $domains[] = $d;
            }
            $normalizedDomains = implode("\n", array_unique($domains));
        }

        if ($restrictDomains && $normalizedDomains === '') {
            Session::flash('error', __('security_authentication.invalid_domain_list'));
            Redirect::to('/security/authentication');
        }

        $roleModel     = new Role();
        $defaultRoleId = !empty($_POST['default_role_id']) ? (int) $_POST['default_role_id'] : null;
        $autoCreate    = !empty($_POST['allow_auto_user_creation']);

        if ($defaultRoleId !== null && !$roleModel->exists($defaultRoleId)) {
            Session::flash('error', __('security_authentication.invalid_default_role'));
            Redirect::to('/security/authentication');
        }

        if ($autoCreate && $defaultRoleId === null) {
            Session::flash('error', __('security_authentication.default_role_required_auto_create'));
            Redirect::to('/security/authentication');
        }

        $data = [
            'local_login_enabled'       => $localEnabled    ? 1 : 0,
            'external_login_enabled'    => $externalEnabled ? 1 : 0,
            'allow_auto_user_creation'  => $autoCreate ? 1 : 0,
            'default_role_id'           => $defaultRoleId,
            'require_existing_user'     => !empty($_POST['require_existing_user'])    ? 1 : 0,
            'allow_account_linking'     => !empty($_POST['allow_account_linking'])    ? 1 : 0,
            'restrict_external_domains' => $restrictDomains,
            'allowed_external_domains'  => $normalizedDomains ?: null,
        ];

        if ((new AuthenticationSettings())->save($data)) {
            Audit::log([
                'module'      => 'security_authentication',
                'action'      => 'authentication.settings_updated',
                'description' => 'Configuración de métodos de autenticación actualizada',
                'new_values'  => [
                    'local_login_enabled'       => $data['local_login_enabled'],
                    'external_login_enabled'    => $data['external_login_enabled'],
                    'restrict_external_domains' => $data['restrict_external_domains'],
                ],
                'status'      => 'success',
            ]);
            Session::flash('success', __('security_authentication.settings_updated'));
        } else {
            Logger::error('AuthenticationController::updateSettings — save failed');
            Session::flash('error', __('security_authentication.settings_update_error'));
        }

        Redirect::to('/security/authentication');
    }

    public function editProvider(int $id): void
    {
        Auth::requirePermission('security_authentication.providers_edit');
        $authUser = Auth::user();
        $provider = (new ExternalAuthProvider())->find($id);
        if (!$provider) {
            http_response_code(404);
            Session::flash('error', __('security_authentication.provider_not_found'));
            Redirect::to('/security/authentication');
        }

        $appUrl = rtrim((string) env('APP_URL', ''), '/');
        $service = new ExternalAuthService();
        $suggestedRedirectUri = $service->generateRedirectUri($appUrl, $provider['slug']);
        $errors = Session::getFlash('errors', []);
        $old    = Session::getFlash('old', []);

        $this->view('security.authentication.edit_provider',
            compact('authUser', 'provider', 'suggestedRedirectUri', 'errors', 'old'));
    }

    public function updateProvider(int $id): void
    {
        Auth::requirePermission('security_authentication.providers_edit');
        CSRF::validateOrFail();

        $model    = new ExternalAuthProvider();
        $provider = $model->find($id);
        if (!$provider) {
            Session::flash('error', __('security_authentication.provider_not_found'));
            Redirect::to('/security/authentication');
        }

        $clientId    = trim($_POST['client_id']         ?? '');
        $clientSecret= trim($_POST['client_secret']     ?? '');
        $tenantId    = trim($_POST['tenant_id']         ?? '');
        $redirectUri = trim($_POST['redirect_uri']      ?? '');
        $scopes      = trim($_POST['scopes']            ?? '');
        $authUrl     = trim($_POST['authorization_url'] ?? '');
        $tokenUrl    = trim($_POST['token_url']         ?? '');
        $userinfoUrl = trim($_POST['userinfo_url']      ?? '');
        $isEnabled   = !empty($_POST['is_enabled']) ? 1 : 0;

        $data = [
            'client_id'         => $clientId,
            'client_secret'     => $clientSecret,
            'tenant_id'         => $tenantId,
            'redirect_uri'      => $redirectUri,
            'scopes'            => $scopes,
            'authorization_url' => $authUrl,
            'token_url'         => $tokenUrl,
            'userinfo_url'      => $userinfoUrl,
            'is_enabled'        => $isEnabled,
        ];

        if ($model->update($id, $data)) {
            Audit::log([
                'module'      => 'security_authentication',
                'action'      => 'external_provider.updated',
                'entity'      => 'external_auth_provider',
                'entity_id'   => $id,
                'description' => "Proveedor externo actualizado: {$provider['name']}",
                'new_values'  => [
                    'client_id'   => $clientId ?: null,
                    'is_enabled'  => $isEnabled,
                    'redirect_uri'=> $redirectUri ?: null,
                ],
                'status'      => 'success',
            ]);
            Session::flash('success', __('security_authentication.provider_updated'));
        } else {
            Logger::error("AuthenticationController::updateProvider — failed for id={$id}");
            Session::flash('error', __('security_authentication.provider_update_error'));
        }

        Redirect::to('/security/authentication');
    }

    public function toggleProvider(int $id): void
    {
        Auth::requirePermission('security_authentication.providers_edit');
        CSRF::validateOrFail();

        $model    = new ExternalAuthProvider();
        $provider = $model->find($id);
        if (!$provider) {
            Session::flash('error', __('security_authentication.provider_not_found'));
            Redirect::to('/security/authentication');
        }

        if ($model->toggle($id)) {
            $nowEnabled = !(bool) $provider['is_enabled'];
            $action = $nowEnabled ? 'external_provider.enabled' : 'external_provider.disabled';
            Audit::log([
                'module'      => 'security_authentication',
                'action'      => $action,
                'entity'      => 'external_auth_provider',
                'entity_id'   => $id,
                'description' => "Proveedor {$provider['name']} " . ($nowEnabled ? 'activado' : 'desactivado'),
                'status'      => 'success',
            ]);
            $msg = $nowEnabled
                ? __('security_authentication.provider_enabled')
                : __('security_authentication.provider_disabled');
            Session::flash('success', $msg);
        } else {
            Logger::error("AuthenticationController::toggleProvider — failed for id={$id}");
            Session::flash('error', __('security_authentication.provider_update_error'));
        }

        Redirect::to('/security/authentication');
    }

    public function testProvider(int $id): void
    {
        Auth::requirePermission('security_authentication.providers_test');
        CSRF::validateOrFail();

        $model    = new ExternalAuthProvider();
        $provider = $model->find($id);
        if (!$provider) {
            Session::flash('error', __('security_authentication.provider_not_found'));
            Redirect::to('/security/authentication');
        }

        $missing = [];
        if (empty($provider['client_id']))        $missing[] = 'Client ID';
        if (empty($provider['client_secret']))     $missing[] = 'Client Secret';
        if (empty($provider['redirect_uri']))      $missing[] = 'Redirect URI';
        if (empty($provider['authorization_url'])) $missing[] = 'Authorization URL';
        if (empty($provider['token_url']))         $missing[] = 'Token URL';

        if (!empty($missing)) {
            $msg = __('security_authentication.test_missing_fields') . ': ' . implode(', ', $missing);
            $model->updateTestResult($id, false, $msg);
            Audit::log([
                'module'      => 'security_authentication',
                'action'      => 'external_provider.test_failed',
                'entity'      => 'external_auth_provider',
                'entity_id'   => $id,
                'description' => "Prueba fallida para {$provider['name']}: configuración incompleta",
                'status'      => 'warning',
            ]);
            Session::flash('error', __('security_authentication.provider_test_failed') . ' — ' . $msg);
            Redirect::to('/security/authentication');
        }

        $secret = $model->decryptSecret($provider['client_secret'] ?? '');
        if (empty($secret)) {
            $msg = __('security_authentication.test_secret_decrypt_failed');
            $model->updateTestResult($id, false, $msg);
            Session::flash('error', __('security_authentication.provider_test_failed') . ' — ' . $msg);
            Redirect::to('/security/authentication');
        }

        // ── Rate limit de la prueba OAuth administrativa (Etapa 3.7) ────────────
        // Se evalua justo antes de iniciar el flujo externo real (generar state y
        // redirigir al proveedor). Las validaciones previas (campos faltantes,
        // secret sin descifrar) no cuentan como intento, porque nunca llegan a
        // contactar al proveedor externo.
        $rateLimiter = new RateLimitService();
        $adminId     = (string) Auth::id();

        if ($rateLimiter->tooManyAttempts(
            self::OAUTH_TEST_RATE_LIMIT_ACTION,
            $adminId,
            self::OAUTH_TEST_RATE_LIMIT_MAX_ATTEMPTS,
            self::OAUTH_TEST_RATE_LIMIT_WINDOW_SECONDS,
            self::OAUTH_TEST_RATE_LIMIT_IDENTIFIER_TYPE
        )) {
            $availableIn = $rateLimiter->availableIn(
                self::OAUTH_TEST_RATE_LIMIT_ACTION,
                $adminId,
                self::OAUTH_TEST_RATE_LIMIT_IDENTIFIER_TYPE
            );

            Audit::log([
                'module'      => 'security_authentication',
                'action'      => 'external_provider.test_rate_limited',
                'entity'      => 'external_auth_provider',
                'entity_id'   => $id,
                'description' => "Prueba OAuth bloqueada temporalmente por exceso de intentos para {$provider['name']}",
                'status'      => 'denied',
                'new_values'  => [
                    'action'          => self::OAUTH_TEST_RATE_LIMIT_ACTION,
                    'identifier_type' => self::OAUTH_TEST_RATE_LIMIT_IDENTIFIER_TYPE,
                    'available_in'    => $availableIn,
                    'provider'        => $provider['slug'] ?? null,
                ],
            ]);

            Session::flash('error', __('security_authentication.oauth_test_rate_limited', ['seconds' => $availableIn]));
            Redirect::to('/security/authentication');
        }

        // La solicitud cuenta aunque el intento #5 SI se procesa normalmente
        // (redirige al proveedor) — igual criterio que la prueba SMTP: es una
        // accion administrativa de diagnostico, no un intento adversario. El
        // bloqueo aplica desde la siguiente solicitud (#6).
        $rateLimiter->hit(
            self::OAUTH_TEST_RATE_LIMIT_ACTION,
            $adminId,
            self::OAUTH_TEST_RATE_LIMIT_IDENTIFIER_TYPE,
            self::OAUTH_TEST_RATE_LIMIT_MAX_ATTEMPTS,
            self::OAUTH_TEST_RATE_LIMIT_WINDOW_SECONDS
        );

        $slug    = $provider['slug'] ?? '';
        $service = new ExternalAuthService();
        $oauth   = $service->buildProvider($provider, $secret);
        $state   = bin2hex(random_bytes(32));

        Session::set("oauth_state_{$slug}", $state);
        Session::set("oauth_action_{$slug}", 'admin_test');
        Session::set('oauth_admin_test_id', $id);

        $scopes  = $provider['scopes'] ? $service->normalizeScopes($provider['scopes']) : [];
        $authUrl = $oauth->getAuthorizationUrl(['state' => $state, 'scope' => $scopes]);

        Audit::log([
            'module'      => 'security_authentication',
            'action'      => 'external_provider.test_started',
            'entity'      => 'external_auth_provider',
            'entity_id'   => $id,
            'description' => "Prueba OAuth iniciada para {$provider['name']}",
            'status'      => 'info',
            'user_id'     => Auth::id(),
        ]);

        header('Location: ' . $authUrl);
        exit;
    }
}
