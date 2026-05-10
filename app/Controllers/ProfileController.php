<?php

namespace App\Controllers;

use Core\Audit;
use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\User;
use App\Models\Language;
use App\Services\UploadService;

class ProfileController extends Controller
{
    private User     $userModel;
    private Language $langModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->langModel = new Language();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $authUser  = Auth::user();
        $user      = $this->userModel->findById(Auth::id());
        $languages = $this->langModel->getActive();
        $this->view('profile.index', compact('authUser', 'user', 'languages'));
    }

    public function edit(): void
    {
        Auth::requireAuth();
        $authUser  = Auth::user();
        $user      = $this->userModel->findById(Auth::id());
        $languages = $this->langModel->getActive();
        $this->view('profile.edit', compact('authUser', 'user', 'languages'));
    }

    public function updatePreferences(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            Redirect::to('/profile');
        }

        CSRF::validateOrFail();

        $theme      = $this->input('theme_preference', 'light');
        $languageId = $this->input('language_id', null);

        // Validar tema
        if (!in_array($theme, ['light', 'dark', 'default'], true)) {
            $theme = 'light';
        }

        // Validar idioma
        $langIdInt = $languageId ? (int)$languageId : null;
        if ($langIdInt && !$this->langModel->isActiveById($langIdInt)) {
            Redirect::withError('/profile', 'El idioma seleccionado no es válido.');
        }

        $id = Auth::id();

        try {
            $this->userModel->updatePreferences($id, $theme, $langIdInt);

            // Determinar código de idioma para actualizar la sesión
            $langCode = 'es';
            if ($langIdInt) {
                $lang = $this->langModel->findById($langIdInt);
                $langCode = $lang ? $lang['code'] : 'es';
            }

            Auth::updateSession([
                'theme'  => $theme,
                'lang'   => $langCode,
                'lang_id'=> $langIdInt,
            ]);

            Logger::info("Preferencias actualizadas - ID {$id} tema={$theme} lang={$langCode}");
            Audit::log(['module' => 'profile', 'action' => 'preferences_updated',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Preferencias de perfil actualizadas',
                'new_values' => ['theme_preference' => $theme, 'language_id' => $langIdInt, 'lang_code' => $langCode],
                'status' => 'success']);
            Redirect::withSuccess('/profile', __('profile.preferences_updated'));
        } catch (\PDOException $e) {
            Logger::error('ProfileController::updatePreferences PDOException: ' . $e->getMessage());
            Redirect::withError('/profile', __('alerts.internal'));
        }
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

        $id        = Auth::id();
        $uploadedImage = null;

        // Procesar imagen de perfil si se envió
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = (new UploadService())->upload(
                $_FILES['profile_image'],
                'profile_images',
                ['prefix' => 'avatar_' . $id . '_']
            );
            if (!$result['success']) {
                Redirect::withErrors('/profile/edit', ['profile_image' => $result['error']], [
                    'nombres'   => $nombres,
                    'apellidos' => $apellidos,
                    'telefono'  => $telefono,
                    'direccion' => $direccion,
                ]);
            }
            $uploadedImage = $result['filename'];
        }

        $updated = $this->userModel->updateProfile($id, [
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'telefono'  => $telefono ?: null,
            'direccion' => $direccion ?: null,
        ]);

        if ($uploadedImage) {
            $currentUser = $this->userModel->findById($id);
            (new UploadService())->delete($currentUser['profile_image'] ?? null, 'profile_images');
            $this->userModel->updateProfileImage($id, $uploadedImage);
        }

        if ($updated) {
            Auth::updateSession([
                'nombres'       => $nombres,
                'apellidos'     => $apellidos,
                'profile_image' => $uploadedImage ?? Auth::user()['profile_image'],
            ]);
            Logger::info("Perfil actualizado - ID {$id}");
            $newVals = ['nombres' => $nombres, 'apellidos' => $apellidos,
                'telefono' => $telefono ?: null, 'direccion' => $direccion ?: null];
            if ($uploadedImage) {
                $newVals['profile_image_updated'] = true;
            }
            Audit::log(['module' => 'profile', 'action' => 'profile_updated',
                'entity' => 'user', 'entity_id' => $id,
                'description' => 'Perfil de usuario actualizado',
                'new_values' => $newVals,
                'status' => 'success']);
            Redirect::withSuccess('/profile', 'Perfil actualizado correctamente.');
        } else {
            Redirect::withError('/profile/edit', 'No se pudo actualizar el perfil. Intenta de nuevo.');
        }
    }

    public function updateTheme(): void
    {
        Auth::requireAuth();

        if (!$this->isPost()) {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }

        // verify() sin rotación — seguro para AJAX (no regenera el token)
        if (!CSRF::verify()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'csrf']);
            exit;
        }

        $theme = $this->input('theme', 'light');
        if (!in_array($theme, ['light', 'dark', 'default'], true)) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'invalid']);
            exit;
        }

        $id   = Auth::id();
        $user = $this->userModel->findById($id);

        try {
            $this->userModel->updatePreferences($id, $theme, $user['language_id'] ?? null);
            Auth::updateSession(['theme' => $theme]);
            Audit::log(['module' => 'profile', 'action' => 'theme_updated',
                'entity' => 'user', 'entity_id' => $id,
                'description' => "Tema actualizado a '{$theme}'",
                'new_values' => ['theme_preference' => $theme],
                'status' => 'success']);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'theme' => $theme]);
        } catch (\PDOException $e) {
            Logger::error('ProfileController::updateTheme PDOException: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'db']);
        }
        exit;
    }

}
