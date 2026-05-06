<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Session;
use Core\Validator;
use Core\Logger;
use App\Models\User;

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
        $authUser = Auth::user();
        $user = $this->userModel->findById(Auth::id());
        $this->view('account.index', compact('authUser', 'user'));
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
            Redirect::withSuccess('/account', 'Correo electrónico actualizado correctamente.');
        } else {
            Redirect::withError('/account/edit-email', 'No se pudo actualizar el correo. Intenta de nuevo.');
        }
    }

    public function editPassword(): void
    {
        Auth::requireAuth();
        $authUser = Auth::user();
        $user = $this->userModel->findById(Auth::id());
        $this->view('account.edit_password', compact('authUser', 'user'));
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
                  ->strongPassword('new_password', $newPassword)
                  ->matches('confirm_password', $newPassword, $confirmPassword);

        if ($validator->fails()) {
            Redirect::withErrors('/account/edit-password', $validator->errors());
        }

        $user = $this->userModel->findById($id);

        if (!password_verify($currentPassword, $user['password'])) {
            Redirect::withErrors('/account/edit-password', ['current_password' => 'La contraseña actual es incorrecta.']);
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($this->userModel->updatePassword($id, $hashed)) {
            Session::regenerate();
            Logger::security("Contraseña actualizada - ID {$id}");
            Redirect::withSuccess('/account', 'Contraseña actualizada correctamente.');
        } else {
            Redirect::withError('/account/edit-password', 'No se pudo actualizar la contraseña. Intenta de nuevo.');
        }
    }
}
