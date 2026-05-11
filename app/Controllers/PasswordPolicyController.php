<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\Logger;
use Core\Redirect;
use Core\Session;
use App\Models\PasswordPolicy;

class PasswordPolicyController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('security_password_policy.view');
        $policy = (new PasswordPolicy())->get();
        $this->view('security.password_policy.index', compact('policy'));
    }

    public function update(): void
    {
        Auth::requirePermission('security_password_policy.edit');

        if (!$this->isPost()) {
            Redirect::to('/security/password-policy');
        }

        CSRF::validateOrFail();

        $isEnabled      = !empty($_POST['is_enabled'])                ? 1 : 0;
        $minLength      = min(128, max(6, (int) ($_POST['min_length'] ?? 10)));
        $reqUppercase   = !empty($_POST['require_uppercase'])         ? 1 : 0;
        $reqLowercase   = !empty($_POST['require_lowercase'])         ? 1 : 0;
        $reqNumber      = !empty($_POST['require_number'])            ? 1 : 0;
        $reqSpecial     = !empty($_POST['require_special'])           ? 1 : 0;
        $prevEmail      = !empty($_POST['prevent_email_in_password']) ? 1 : 0;
        $prevName       = !empty($_POST['prevent_name_in_password'])  ? 1 : 0;
        $prevCommon     = !empty($_POST['prevent_common_passwords'])  ? 1 : 0;
        $historyCount   = min(10, max(0, (int) ($_POST['password_history_count']   ?? 3)));
        $expirationDays = min(365, max(0, (int) ($_POST['password_expiration_days'] ?? 0)));

        if ($isEnabled && $minLength < 8) {
            Session::flash('error', __('password_policy.error_weak_policy'));
            Redirect::to('/security/password-policy');
        }

        $saveData = [
            'is_enabled'                => $isEnabled,
            'min_length'                => $minLength,
            'require_uppercase'         => $reqUppercase,
            'require_lowercase'         => $reqLowercase,
            'require_number'            => $reqNumber,
            'require_special'           => $reqSpecial,
            'prevent_email_in_password' => $prevEmail,
            'prevent_name_in_password'  => $prevName,
            'prevent_common_passwords'  => $prevCommon,
            'password_history_count'    => $historyCount,
            'password_expiration_days'  => $expirationDays,
        ];

        if ((new PasswordPolicy())->save($saveData)) {
            Logger::security('PasswordPolicyController::update — policy updated by admin ID ' . Auth::id());
            Audit::log([
                'module'      => 'security_password_policy',
                'action'      => 'password_policy.updated',
                'description' => 'Política de contraseñas actualizada',
                'new_values'  => $saveData,
                'status'      => 'success',
                'user_id'     => Auth::id(),
            ]);
            Session::flash('success', __('password_policy.updated'));
        } else {
            Session::flash('error', __('password_policy.update_error'));
        }

        Redirect::to('/security/password-policy');
    }
}
