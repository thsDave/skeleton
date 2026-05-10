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

class AuthenticationController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('security_authentication.view');
        $authUser           = Auth::user();
        $settings           = (new AuthenticationSettings())->get();
        $providerModel      = new ExternalAuthProvider();
        $providers          = $providerModel->all();
        $providersReadyCount = $providerModel->countReady();
        $roles              = (new Role())->getAll();
        $errors             = Session::getFlash('errors', []);
        $old                = Session::getFlash('old', []);
        $this->view('security.authentication.index',
            compact('authUser', 'settings', 'providers', 'providersReadyCount', 'roles', 'errors', 'old'));
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
            $readyCount = (new ExternalAuthProvider())->countReady();
            if ($readyCount === 0) {
                try {
                    Audit::log([
                        'module'      => 'security_authentication',
                        'action'      => 'authentication_settings.update_failed',
                        'description' => 'Intento de activar login externo sin proveedor externo configurado',
                        'status'      => 'warning',
                    ]);
                } catch (\Throwable) {}
                Session::flash('error', __('security_authentication.external_provider_required'));
                Redirect::to('/security/authentication');
            }
        }

        $data = [
            'local_login_enabled'      => $localEnabled    ? 1 : 0,
            'external_login_enabled'   => $externalEnabled ? 1 : 0,
            'allow_auto_user_creation' => !empty($_POST['allow_auto_user_creation']) ? 1 : 0,
            'default_role_id'          => !empty($_POST['default_role_id']) ? (int) $_POST['default_role_id'] : null,
            'require_existing_user'    => !empty($_POST['require_existing_user'])    ? 1 : 0,
            'allow_account_linking'    => !empty($_POST['allow_account_linking'])    ? 1 : 0,
        ];

        if ((new AuthenticationSettings())->save($data)) {
            Audit::log([
                'module'      => 'security_authentication',
                'action'      => 'authentication_settings.updated',
                'description' => 'Configuración de métodos de autenticación actualizada',
                'new_values'  => [
                    'local_login_enabled'    => $data['local_login_enabled'],
                    'external_login_enabled' => $data['external_login_enabled'],
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
        if (empty($provider['client_id'])) {
            $missing[] = 'Client ID';
        }
        if (empty($provider['client_secret'])) {
            $missing[] = 'Client Secret';
        }
        if (empty($provider['redirect_uri'])) {
            $missing[] = 'Redirect URI';
        }
        if (empty($provider['authorization_url'])) {
            $missing[] = 'Authorization URL';
        }
        if (empty($provider['token_url'])) {
            $missing[] = 'Token URL';
        }

        if (!empty($missing)) {
            $msg = __('security_authentication.test_missing_fields') . ': ' . implode(', ', $missing);
            $model->updateTestResult($id, false, $msg);
            Audit::log([
                'module'      => 'security_authentication',
                'action'      => 'external_provider.test_failed',
                'entity'      => 'external_auth_provider',
                'entity_id'   => $id,
                'description' => "Prueba fallida para {$provider['name']}: {$msg}",
                'status'      => 'warning',
            ]);
            Session::flash('error', __('security_authentication.provider_test_failed') . ' — ' . $msg);
            Redirect::to('/security/authentication');
        }

        $authUrl = $provider['authorization_url'] ?? '';
        if (!empty($provider['tenant_id'])) {
            $authUrl = str_replace('{tenant_id}', $provider['tenant_id'], $authUrl);
        }

        $reachable = false;
        if (!empty($authUrl)) {
            $ch = curl_init($authUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            curl_exec($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $reachable = ($httpCode >= 200 && $httpCode < 500);
            curl_close($ch);
        }

        $success = $reachable;
        $message = $success
            ? __('security_authentication.test_config_ok')
            : __('security_authentication.test_unreachable');

        $model->updateTestResult($id, $success, $message);

        $action = $success ? 'external_provider.test_success' : 'external_provider.test_failed';
        Audit::log([
            'module'      => 'security_authentication',
            'action'      => $action,
            'entity'      => 'external_auth_provider',
            'entity_id'   => $id,
            'description' => "Prueba de proveedor {$provider['name']}: {$message}",
            'status'      => $success ? 'success' : 'warning',
        ]);

        if ($success) {
            Session::flash('success', __('security_authentication.provider_test_success'));
        } else {
            Session::flash('error', __('security_authentication.provider_test_failed') . ' — ' . $message);
        }

        Redirect::to('/security/authentication');
    }
}
