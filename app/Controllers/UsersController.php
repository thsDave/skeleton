<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Validator;
use Core\Logger;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;

class UsersController extends Controller
{
    private User   $userModel;
    private Role   $roleModel;
    private Status $statusModel;

    public function __construct()
    {
        $this->userModel   = new User();
        $this->roleModel   = new Role();
        $this->statusModel = new Status();
    }

    public function index(): void
    {
        Auth::requireAdmin();
        $authUser = Auth::user();
        $users    = $this->userModel->getAll();
        $this->view('users.index', compact('authUser', 'users'));
    }

    public function create(): void
    {
        Auth::requireAdmin();
        $authUser = Auth::user();
        $roles    = $this->roleModel->getAll();
        $statuses = $this->statusModel->getAll();
        $this->view('users.create', compact('authUser', 'roles', 'statuses'));
    }

    public function store(): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            Redirect::to('/users');
        }

        CSRF::validateOrFail();

        $nombres   = trim($this->input('nombres', ''));
        $apellidos = trim($this->input('apellidos', ''));
        $telefono  = trim($this->input('telefono', ''));
        $direccion = trim($this->input('direccion', ''));
        $email     = trim($this->input('email', ''));
        $password  = $this->input('password', '');
        $confirm   = $this->input('confirm_password', '');
        $roleId    = (int)$this->input('role_id', 0);
        $statusId  = (int)$this->input('status_id', 0);

        $validator = new Validator();
        $validator->required('nombres', $nombres, 'Nombres')
                  ->maxLength('nombres', $nombres, 100, 'Nombres')
                  ->required('apellidos', $apellidos, 'Apellidos')
                  ->maxLength('apellidos', $apellidos, 100, 'Apellidos')
                  ->maxLength('telefono', $telefono, 25, 'Teléfono')
                  ->maxLength('direccion', $direccion, 255, 'Dirección')
                  ->required('email', $email, 'Correo electrónico')
                  ->email('email', $email)
                  ->maxLength('email', $email, 150, 'Correo electrónico')
                  ->required('password', $password, 'Contraseña')
                  ->strongPassword('password', $password)
                  ->matches('confirm_password', $password, $confirm)
                  ->required('role_id', $roleId ?: '', 'Rol')
                  ->required('status_id', $statusId ?: '', 'Estado');

        if ($validator->fails()) {
            Redirect::withErrors('/users/create', $validator->errors(), compact(
                'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
            ));
        }

        if ($this->userModel->emailExists($email, 0)) {
            Redirect::withErrors('/users/create', ['email' => 'Este correo electrónico ya está en uso.'], compact(
                'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
            ));
        }

        if (!$this->roleModel->exists($roleId)) {
            Redirect::withErrors('/users/create', ['role_id' => 'El rol seleccionado no es válido.']);
        }

        if (!$this->statusModel->exists($statusId)) {
            Redirect::withErrors('/users/create', ['status_id' => 'El estado seleccionado no es válido.']);
        }

        // Procesar imagen
        $uploadedImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = $this->handleImageUpload($_FILES['profile_image'], 0);
            if ($result['error']) {
                Redirect::withErrors('/users/create', ['profile_image' => $result['error']]);
            }
            $uploadedImage = $result['filename'];
        }

        $newId = $this->userModel->create([
            'nombres'       => $nombres,
            'apellidos'     => $apellidos,
            'telefono'      => $telefono ?: null,
            'direccion'     => $direccion ?: null,
            'email'         => $email,
            'password'      => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'status_id'     => $statusId,
            'role_id'       => $roleId,
            'profile_image' => $uploadedImage,
        ]);

        if ($newId) {
            // Renombrar imagen con el ID real si se subió
            if ($uploadedImage) {
                $config  = require dirname(__DIR__, 2) . '/config/app.php';
                $newName = 'avatar_' . $newId . '_' . pathinfo($uploadedImage, PATHINFO_FILENAME) . '.' . pathinfo($uploadedImage, PATHINFO_EXTENSION);
                $oldPath = $config['upload_profile_path'] . $uploadedImage;
                $newPath = $config['upload_profile_path'] . $newName;
                if (rename($oldPath, $newPath)) {
                    $this->userModel->updateProfileImage($newId, $newName);
                }
            }
            Logger::security("Usuario creado ID {$newId} por admin ID " . Auth::id());
            Redirect::withSuccess('/users', 'Usuario creado correctamente.');
        } else {
            Redirect::withError('/users/create', 'No se pudo crear el usuario. Intenta de nuevo.');
        }
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();
        $authUser = Auth::user();
        $userId   = (int)$id;
        $user     = $this->userModel->findById($userId);

        if (!$user) {
            Redirect::withError('/users', 'Usuario no encontrado.');
        }

        $roles    = $this->roleModel->getAll();
        $statuses = $this->statusModel->getAll();
        $this->view('users.edit', compact('authUser', 'user', 'roles', 'statuses'));
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            Redirect::to('/users');
        }

        CSRF::validateOrFail();

        $userId    = (int)$id;
        $user      = $this->userModel->findById($userId);

        if (!$user) {
            Redirect::withError('/users', 'Usuario no encontrado.');
        }

        $nombres   = trim($this->input('nombres', ''));
        $apellidos = trim($this->input('apellidos', ''));
        $telefono  = trim($this->input('telefono', ''));
        $direccion = trim($this->input('direccion', ''));
        $email     = trim($this->input('email', ''));
        $password  = $this->input('password', '');
        $confirm   = $this->input('confirm_password', '');
        $roleId    = (int)$this->input('role_id', 0);
        $statusId  = (int)$this->input('status_id', 0);

        $validator = new Validator();
        $validator->required('nombres', $nombres, 'Nombres')
                  ->maxLength('nombres', $nombres, 100, 'Nombres')
                  ->required('apellidos', $apellidos, 'Apellidos')
                  ->maxLength('apellidos', $apellidos, 100, 'Apellidos')
                  ->maxLength('telefono', $telefono, 25, 'Teléfono')
                  ->maxLength('direccion', $direccion, 255, 'Dirección')
                  ->required('email', $email, 'Correo electrónico')
                  ->email('email', $email)
                  ->maxLength('email', $email, 150, 'Correo electrónico')
                  ->required('role_id', $roleId ?: '', 'Rol')
                  ->required('status_id', $statusId ?: '', 'Estado');

        if ($password !== '') {
            $validator->strongPassword('password', $password)
                      ->matches('confirm_password', $password, $confirm);
        }

        if ($validator->fails()) {
            Redirect::withErrors("/users/edit/{$userId}", $validator->errors(), compact(
                'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
            ));
        }

        if ($this->userModel->emailExists($email, $userId)) {
            Redirect::withErrors("/users/edit/{$userId}", ['email' => 'Este correo ya está en uso por otro usuario.']);
        }

        if (!$this->roleModel->exists($roleId) || !$this->statusModel->exists($statusId)) {
            Redirect::withError("/users/edit/{$userId}", 'Rol o estado no válido.');
        }

        // Evitar inactivar al último administrador activo
        if ($statusId !== 1 && $user['role_slug'] === 'administrator') {
            if ($this->userModel->isLastActiveAdmin($userId)) {
                Redirect::withError("/users/edit/{$userId}", 'No se puede inactivar al último administrador activo.');
            }
        }

        // Procesar imagen
        $newImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = $this->handleImageUpload($_FILES['profile_image'], $userId);
            if ($result['error']) {
                Redirect::withErrors("/users/edit/{$userId}", ['profile_image' => $result['error']]);
            }
            $newImage = $result['filename'];
            $this->deleteOldImage($user['profile_image'] ?? null);
        }

        $data = [
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'telefono'  => $telefono ?: null,
            'direccion' => $direccion ?: null,
            'email'     => $email,
            'status_id' => $statusId,
            'role_id'   => $roleId,
        ];

        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        if ($newImage) {
            $data['profile_image'] = $newImage;
        }

        if ($this->userModel->adminUpdate($userId, $data)) {
            Logger::security("Usuario ID {$userId} actualizado por admin ID " . Auth::id());
            Redirect::withSuccess('/users', 'Usuario actualizado correctamente.');
        } else {
            Redirect::withError("/users/edit/{$userId}", 'No se pudo actualizar el usuario.');
        }
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            Redirect::to('/users');
        }

        CSRF::validateOrFail();

        $userId = (int)$id;

        if ($userId === Auth::id()) {
            Redirect::withError('/users', 'No puedes inactivar tu propia cuenta.');
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            Redirect::withError('/users', 'Usuario no encontrado.');
        }

        if ($this->userModel->isLastActiveAdmin($userId)) {
            Redirect::withError('/users', 'No se puede inactivar al último administrador activo.');
        }

        if ($this->userModel->inactivate($userId)) {
            Logger::security("Usuario ID {$userId} inactivado por admin ID " . Auth::id());
            Redirect::withSuccess('/users', 'Usuario inactivado correctamente.');
        } else {
            Redirect::withError('/users', 'No se pudo inactivar el usuario.');
        }
    }

    // ─── Helpers de imagen ────────────────────────────────────────────────────

    private function handleImageUpload(array $file, int $userId): array
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Error al subir el archivo.', 'filename' => null];
        }
        if ($file['size'] > $config['upload_max_size']) {
            return ['error' => 'La imagen no debe superar 2 MB.', 'filename' => null];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $config['upload_allowed_mime'], true)) {
            return ['error' => 'Solo se permiten imágenes JPG, PNG o WEBP.', 'filename' => null];
        }

        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };

        if (!$ext) {
            return ['error' => 'Formato de imagen no permitido.', 'filename' => null];
        }

        $prefix   = $userId > 0 ? "avatar_{$userId}_" : 'avatar_new_';
        $filename = $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
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
