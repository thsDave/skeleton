<?php

namespace App\Controllers;

use Core\Audit;
use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Session;
use Core\Validator;
use Core\Logger;
use App\Models\User;
use App\Models\AuthenticationSettings;
use App\Models\ExternalAuthProvider;
use App\Models\UserExternalAccount;
use App\Services\PasswordPolicyService;

class AccountController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $authUser         = Auth::user();
        $user             = $this->userModel->findById(Auth::id());
        $authSettings     = (new AuthenticationSettings())->get();
        $enabledProviders = (new ExternalAuthProvider())->allEnabled();
        $linkedAccounts   = (new UserExternalAccount())->allForUser(Auth::id());
        $this->view('account.index',
            compact('authUser', 'user', 'authSettings', 'enabledProviders', 'linkedAccounts'));
    }

    public function editEmail(): void
    {
        Auth::requireAuth();
        $authUser = Auth::user();
        $user = $this->userModel->findById(Auth::id());
        $this->view('account.edit_email', compact('authUser', 'user'));
    }

    public function updateEmail(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/account');
        }

        CSRF::validateOrFail();

        $email = trim($this->input('email', ''));
        $id    = Auth::id();

        $validator = new Validator();
        $validator->required('email', $email, 'Correo electrónico')
                  ->email('email', $email)
                  ->maxLength('email', $email, 150, 'Correo electrónico');

        if ($validator->fails()) {
            Redirect::withErrors('/account/edit-email', $validator->errors(), ['email' => $email]);
        }

        if ($this->userModel->emailExists($email, $id)) {
            Redirect::withErrors('/account/edit-email', ['email' => 'Este correo electrónico ya está en uso.'], ['email' => $email]);
        }

        if ($this->userModel->updateEmail($id, $email)) {
            Auth::updateSession(['email' => $email]);
            Logger::security("Email actualizado - ID {$id} nuevo: {$email}");
            Audit::log(['module' => 'account', 'action' => 'email_updated',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Correo electrónico actualizado',
                'new_values' => ['email' => $email],
                'status' => 'success']);
            Redirect::withSuccess('/account', 'Correo electrónico actualizado correctamente.');
        } else {
            Redirect::withError('/account/edit-email', 'No se pudo actualizar el correo. Intenta de nuevo.');
        }
    }

    public function editPassword(): void
    {
        Auth::requireAuth();
        $authUser   = Auth::user();
        $user       = $this->userModel->findById(Auth::id());
        $policyReqs = (new PasswordPolicyService())->getRequirements();
        $this->view('account.edit_password', compact('authUser', 'user', 'policyReqs'));
    }

    public function updatePassword(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/account');
        }

        CSRF::validateOrFail();

        $currentPassword  = $this->input('current_password', '');
        $newPassword      = $this->input('new_password', '');
        $confirmPassword  = $this->input('confirm_password', '');
        $id               = Auth::id();

        $validator = new Validator();
        $validator->required('current_password', $currentPassword, 'Contraseña actual')
                  ->required('new_password', $newPassword, 'Nueva contraseña')
                  ->required('confirm_password', $confirmPassword, 'Confirmar contraseña')
                  ->matches('confirm_password', $newPassword, $confirmPassword);

        if ($validator->fails()) {
            Redirect::withErrors('/account/edit-password', $validator->errors());
        }

        $user = $this->userModel->findById($id);

        if (!password_verify($currentPassword, $user['password'])) {
            Audit::log(['module' => 'account', 'action' => 'password_change_failed',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Intento de cambio de contraseña fallido: contraseña actual incorrecta',
                'status' => 'failed']);
            Redirect::withErrors('/account/edit-password', ['current_password' => 'La contraseña actual es incorrecta.']);
        }

        // ── Política de contraseñas ───────────────────────────────────────────
        $policySvc    = new PasswordPolicyService();
        $policyResult = $policySvc->validate($newPassword, [
            'email'     => $user['email'],
            'nombres'   => $user['nombres'],
            'apellidos' => $user['apellidos'],
        ]);
        if (!$policyResult['valid']) {
            Audit::log(['module' => 'account', 'action' => 'password.policy_validation_failed',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Cambio de contraseña rechazado por política',
                'status' => 'denied']);
            Redirect::withErrors('/account/edit-password', ['new_password' => implode(' ', $policyResult['errors'])]);
        }
        if ($policySvc->isPasswordReused($newPassword, $id)) {
            Audit::log(['module' => 'account', 'action' => 'password.history_reuse_blocked',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Cambio de contraseña rechazado por reutilización',
                'status' => 'denied']);
            Redirect::withErrors('/account/edit-password', ['new_password' => __('password_policy.error_reused', ['count' => (string)(int)($policySvc->getPolicy()['password_history_count'] ?? 3)])]);
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($this->userModel->updatePassword($id, $hashed)) {
            $policySvc->saveHistory($id, $hashed);
            Session::regenerate();
            Logger::security("Contraseña actualizada - ID {$id}");
            Audit::log(['module' => 'account', 'action' => 'password.changed',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Contraseña de cuenta actualizada',
                'status' => 'success']);
            Redirect::withSuccess('/account', 'Contraseña actualizada correctamente.');
        } else {
            Redirect::withError('/account/edit-password', 'No se pudo actualizar la contraseña. Intenta de nuevo.');
        }
    }

    public function initiateLink(string $provider): void
    {
        Auth::requireAuth();
        $allowed = \App\Models\ExternalAuthProvider::allowedSlugs();
        if (!in_array($provider, $allowed, true)) {
            Redirect::to('/account');
        }
        Redirect::to('/auth/external/' . $provider . '/redirect?action=link');
    }

    public function unlinkAccount(int $id): void
    {
        Auth::requireAuth();
        CSRF::validateOrFail();

        $userId    = Auth::id();
        $linkModel = new UserExternalAccount();
        $link      = $linkModel->findById($id);

        if (!$link || (int) $link['user_id'] !== $userId) {
            Session::flash('error', __('account.unlink_not_found'));
            Redirect::to('/account');
        }

        $settings = (new AuthenticationSettings())->get();
        if (!$settings['local_login_enabled']) {
            $count = $linkModel->countForUser($userId);
            if ($count <= 1) {
                Session::flash('error', __('account.unlink_last_method'));
                Redirect::to('/account');
            }
        }

        if ($linkModel->deleteById($id)) {
            $providerName = $link['provider_display_name'] ?? $link['provider_slug'] ?? '?';
            Audit::log([
                'module'      => 'auth',
                'action'      => 'external_login.account_unlinked',
                'entity'      => 'user',
                'entity_id'   => $userId,
                'description' => "Cuenta externa desvinculada: {$providerName}",
                'status'      => 'success',
                'user_id'     => $userId,
            ]);
            Session::flash('success', __('account.provider_unlinked'));
        } else {
            Logger::error("AccountController::unlinkAccount — failed to delete link id={$id}");
            Session::flash('error', __('account.unlink_error'));
        }

        Redirect::to('/account');
    }
}
