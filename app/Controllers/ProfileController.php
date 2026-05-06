<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\User;

class ProfileController extends Controller
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
        $this->view('profile.index', compact('authUser', 'user'));
    }

    public function edit(): void
    {
        Auth::requireAuth();
        $authUser = Auth::user();
        $user = $this->userModel->findById(Auth::id());
        $this->view('profile.edit', compact('authUser', 'user'));
    }

    public function update(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/profile');
        }

        CSRF::validateOrFail();

        $nombres   = trim($this->input('nombres', ''));
        $apellidos = trim($this->input('apellidos', ''));
        $telefono  = trim($this->input('telefono', ''));
        $direccion = trim($this->input('direccion', ''));

        $validator = new Validator();
        $validator->required('nombres', $nombres, 'Nombres')
                  ->maxLength('nombres', $nombres, 100, 'Nombres')
                  ->required('apellidos', $apellidos, 'Apellidos')
                  ->maxLength('apellidos', $apellidos, 100, 'Apellidos')
                  ->maxLength('telefono', $telefono, 25, 'Teléfono')
                  ->maxLength('direccion', $direccion, 255, 'Dirección');

        if ($validator->fails()) {
            Redirect::withErrors('/profile/edit', $validator->errors(), [
                'nombres'   => $nombres,
                'apellidos' => $apellidos,
                'telefono'  => $telefono,
                'direccion' => $direccion,
            ]);
        }

        $updated = $this->userModel->updateProfile(Auth::id(), [
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'telefono'  => $telefono ?: null,
            'direccion' => $direccion ?: null,
        ]);

        if ($updated) {
            Auth::updateSession(['nombres' => $nombres, 'apellidos' => $apellidos]);
            Logger::info("Perfil actualizado - ID " . Auth::id());
            Redirect::withSuccess('/profile', 'Perfil actualizado correctamente.');
        } else {
            Redirect::withError('/profile/edit', 'No se pudo actualizar el perfil. Intenta de nuevo.');
        }
    }
}
