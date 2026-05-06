<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\User;
use App\Models\Language;

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
        if (!in_array($theme, ['light', 'dark'], true)) {
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
            $result = $this->handleImageUpload($_FILES['profile_image']);
            if ($result['error']) {
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
            // Eliminar imagen anterior si existe
            $currentUser = $this->userModel->findById($id);
            $this->deleteOldImage($currentUser['profile_image'] ?? null);
            $this->userModel->updateProfileImage($id, $uploadedImage);
        }

        if ($updated) {
            Auth::updateSession([
                'nombres'      => $nombres,
                'apellidos'    => $apellidos,
                'profile_image' => $uploadedImage ?? Auth::user()['profile_image'],
            ]);
            Logger::info("Perfil actualizado - ID {$id}");
            Redirect::withSuccess('/profile', 'Perfil actualizado correctamente.');
        } else {
            Redirect::withError('/profile/edit', 'No se pudo actualizar el perfil. Intenta de nuevo.');
        }
    }

    // ─── Helpers de imagen ────────────────────────────────────────────────────

    private function handleImageUpload(array $file): array
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Error al subir el archivo.', 'filename' => null];
        }
        if ($file['size'] > $config['upload_max_size']) {
            return ['error' => 'La imagen no debe superar 2 MB.', 'filename' => null];
        }

        // Validar MIME real (no confiar en extensión)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $config['upload_allowed_mime'], true)) {
            return ['error' => 'Solo se permiten imágenes JPG, PNG o WEBP.', 'filename' => null];
        }

        $ext      = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };

        if (!$ext) {
            return ['error' => 'Formato de imagen no permitido.', 'filename' => null];
        }

        $filename = 'avatar_' . Auth::id() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $config['upload_profile_path'] . $filename;

        if (!is_dir($config['upload_profile_path'])) {
            mkdir($config['upload_profile_path'], 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['error' => 'No se pudo guardar la imagen.', 'filename' => null];
        }

        return ['error' => null, 'filename' => $filename];
    }

    private function deleteOldImage(?string $filename): void
    {
        if (!$filename) {
            return;
        }
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $path   = $config['upload_profile_path'] . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
