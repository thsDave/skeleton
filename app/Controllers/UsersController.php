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
use App\Models\Role;
use App\Models\Status;
use App\Models\LoginAttempt;
use App\Services\PasswordPolicyService;
use App\Services\UploadService;
use App\Services\ExcelExportService;
use App\Services\UserSessionService;

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
        Auth::requirePermission('users.view');
        $authUser = Auth::user();
        $users    = $this->userModel->getAll();
        $this->view('users.index', compact('authUser', 'users'));
    }

    public function exportExcel(): void
    {
        Auth::requirePermission('users.export');

        try {
            $users = $this->userModel->getForExport();
            if (empty($users)) {
                Redirect::withError('/users', __('export.no_records'));
            }

            $rows = array_map(function (array $user): array {
                $locked = !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
                return [
                    'id' => (int)$user['id'],
                    'nombres' => $user['nombres'] ?? '',
                    'apellidos' => $user['apellidos'] ?? '',
                    'email' => $user['email'] ?? '',
                    'telefono' => $user['telefono'] ?? '',
                    'rol' => $user['role_name'] ?? '',
                    'estado' => $user['status_name'] ?? '',
                    'mfa_activo' => !empty($user['two_factor_enabled']) ? __('common.yes') : __('common.no'),
                    'bloqueado' => $locked ? __('common.yes') : __('common.no'),
                    'creado' => $user['created_at'] ?? '',
                    'actualizado' => $user['updated_at'] ?? '',
                    'ultimo_login' => $user['last_login_at'] ?? '',
                ];
            }, $users);

            Audit::log(['module' => 'users', 'action' => 'users.exported',
                'entity' => 'user',
                'description' => 'Exportacion Excel de usuarios',
                'new_values' => ['records_count' => count($rows)],
                'status' => 'success']);

            (new ExcelExportService())->download(
                'usuarios_' . date('Ymd_His') . '.xlsx',
                [
                    'id' => 'ID',
                    'nombres' => __('users.col_name'),
                    'apellidos' => __('users.last_name'),
                    'email' => __('users.col_email'),
                    'telefono' => __('users.col_phone'),
                    'rol' => __('users.col_role'),
                    'estado' => __('users.col_status'),
                    'mfa_activo' => __('users.mfa_enabled'),
                    'bloqueado' => __('users.locked'),
                    'creado' => __('users.col_registered'),
                    'actualizado' => __('export.updated_at'),
                    'ultimo_login' => __('users.last_login'),
                ],
                $rows,
                [
                    __('export.generated_at') => date('Y-m-d H:i:s'),
                    __('export.generated_by') => Auth::user()['email'] ?? '',
                    __('export.records_count') => (string)count($rows),
                ]
            );
        } catch (\Throwable $e) {
            Logger::error('UsersController::exportExcel: ' . $e->getMessage());
            Audit::log(['module' => 'users', 'action' => 'users.export_failed',
                'description' => 'Error al exportar usuarios',
                'status' => 'failed']);
            Redirect::withError('/users', __('export.error'));
        }
    }

    public function sessionHistory(string $id): void
    {
        Auth::requirePermission('users.sessions.view');

        $userId = (int)$id;
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Redirect::withError('/users', __('users.not_found'));
        }

        $authUser = Auth::user();
        $status = in_array($_GET['status'] ?? '', ['active', 'closed'], true) ? $_GET['status'] : '';
        $sessions = (new UserSessionService())->getUserSessionHistoryForAdmin($userId, ['status' => $status]);

        Audit::log([
            'module' => 'users',
            'action' => 'users.sessions_history_viewed',
            'entity' => 'user',
            'entity_id' => $userId,
            'description' => 'Historial de sesiones de usuario consultado',
            'new_values' => ['status_filter' => $status ?: 'all'],
            'status' => 'info',
        ]);

        $this->view('users.sessions_history', compact('authUser', 'user', 'sessions', 'status'));
    }

    public function revokeSessionFromHistory(string $id): void
    {
        Auth::requirePermission('users.sessions.revoke');
        CSRF::validateOrFail();

        $service = new UserSessionService();
        $session = $service->findSession((int)$id);
        if (!$session || !empty($session['revoked_at'])) {
            Redirect::withError('/users', __('sessions.closed_error'));
        }

        if (hash_equals((string)$session['session_hash'], $service->getCurrentSessionHash())) {
            Redirect::withError('/users/' . (int)$session['user_id'] . '/sessions', __('sessions.closed_error'));
        }

        $ok = $service->revokeSession((int)$id, Auth::id(), 'admin_revoke');
        Audit::log([
            'module' => 'sessions',
            'action' => 'sessions.revoked_from_history',
            'entity' => 'user_session',
            'entity_id' => (int)$id,
            'description' => 'Sesion revocada desde historial administrativo',
            'new_values' => ['target_user_id' => (int)$session['user_id']],
            'status' => $ok ? 'success' : 'failed',
        ]);

        Session::flash($ok ? 'success' : 'error', $ok ? __('sessions.closed_success') : __('sessions.closed_error'));
        Redirect::to('/users/' . (int)$session['user_id'] . '/sessions');
    }

    public function revokeAllSessionsFromHistory(string $id): void
    {
        Auth::requirePermission('users.sessions.revoke_all');
        CSRF::validateOrFail();

        $userId = (int)$id;
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Redirect::withError('/users', __('users.not_found'));
        }

        $keepCurrent = $userId === (int)Auth::id();
        $count = (new UserSessionService())->revokeAllUserSessions($userId, Auth::id(), 'admin_revoke_user_all', $keepCurrent);
        Audit::log([
            'module' => 'sessions',
            'action' => 'sessions.revoked_all_from_user_history',
            'entity' => 'user',
            'entity_id' => $userId,
            'description' => 'Sesiones activas revocadas desde historial de usuario',
            'new_values' => ['revoked_count' => $count],
            'status' => 'success',
        ]);

        Session::flash('success', __('sessions.closed_all_success', ['count' => (string)$count]));
        Redirect::to('/users/' . $userId . '/sessions');
    }

    public function create(): void
    {
        Auth::requirePermission('users.create');
        $authUser    = Auth::user();
        $roles       = $this->roleModel->getAll();
        $statuses    = $this->statusModel->getAll();
        $policyReqs  = (new PasswordPolicyService())->getRequirements();
        $this->view('users.create', compact('authUser', 'roles', 'statuses', 'policyReqs'));
    }

    public function store(): void
    {
        Auth::requirePermission('users.create');

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
        $confirm   = $this->input('password_confirmation', '');
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
                  ->matches('password_confirmation', $password, $confirm)
                  ->required('role_id', $roleId ?: '', 'Rol')
                  ->required('status_id', $statusId ?: '', 'Estado');

        if ($validator->fails()) {
            Redirect::withErrors('/users/create', $validator->errors(), compact(
                'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
            ));
        }

        // ── Política de contraseñas ───────────────────────────────────────────
        $policySvc    = new PasswordPolicyService();
        $policyResult = $policySvc->validate($password, [
            'email'     => $email,
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
        ]);
        if (!$policyResult['valid']) {
            Redirect::withErrors('/users/create', ['password' => implode(' ', $policyResult['errors'])], compact(
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
        $uploadSvc     = new UploadService();
        $uploadedImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = $uploadSvc->upload($_FILES['profile_image'], 'profile_images', ['prefix' => 'avatar_new_']);
            if (!$result['success']) {
                Redirect::withErrors('/users/create', ['profile_image' => $result['error']]);
            }
            $uploadedImage = $result['filename'];
        }

        $forcePasswordChange = !empty($_POST['force_password_change']) ? 1 : 0;
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $newId = $this->userModel->create([
            'nombres'               => $nombres,
            'apellidos'             => $apellidos,
            'telefono'              => $telefono ?: null,
            'direccion'             => $direccion ?: null,
            'email'                 => $email,
            'password'              => $hashedPassword,
            'status_id'             => $statusId,
            'role_id'               => $roleId,
            'profile_image'         => $uploadedImage,
            'force_password_change' => $forcePasswordChange,
        ]);

        if ($newId) {
            // Guardar contraseña inicial en historial
            $policySvc->saveHistory($newId, $hashedPassword);

            // Renombrar imagen con el ID real si se subió
            if ($uploadedImage) {
                $diskPath = $uploadSvc->getDiskPath('profile_images');
                $newName  = 'avatar_' . $newId . '_' . pathinfo($uploadedImage, PATHINFO_FILENAME) . '.' . pathinfo($uploadedImage, PATHINFO_EXTENSION);
                if (rename($diskPath . $uploadedImage, $diskPath . $newName)) {
                    $this->userModel->updateProfileImage($newId, $newName);
                }
            }
            Logger::security("Usuario creado ID {$newId} por admin ID " . Auth::id());
            if ($forcePasswordChange) {
                Audit::log(['module' => 'users', 'action' => 'users.force_password_change_enabled',
                    'entity' => 'user', 'entity_id' => $newId,
                    'description' => "Cambio obligatorio de contraseña activado al crear usuario ID {$newId}",
                    'status' => 'info', 'user_id' => Auth::id()]);
            }
            Audit::log(['module' => 'users', 'action' => 'users.created',
                'entity' => 'user', 'entity_id' => $newId,
                'description' => "Usuario creado: {$email}",
                'new_values' => ['nombres' => $nombres, 'apellidos' => $apellidos,
                    'email' => $email, 'role_id' => $roleId, 'status_id' => $statusId,
                    'force_password_change' => $forcePasswordChange],
                'status' => 'success']);
            Redirect::withSuccess('/users', 'Usuario creado correctamente.');
        } else {
            Audit::log(['module' => 'users', 'action' => 'users.create_failed',
                'description' => "Error al crear usuario: {$email}", 'status' => 'failed']);
            Redirect::withError('/users/create', 'No se pudo crear el usuario. Intenta de nuevo.');
        }
    }

    public function edit(string $id): void
    {
        Auth::requirePermission('users.edit');
        $authUser   = Auth::user();
        $userId     = (int)$id;
        $user       = $this->userModel->findById($userId);

        if (!$user) {
            Redirect::withError('/users', 'Usuario no encontrado.');
        }

        $roles      = $this->roleModel->getAll();
        $statuses   = $this->statusModel->getAll();
        $policyReqs = (new PasswordPolicyService())->getRequirements();
        $this->view('users.edit', compact('authUser', 'user', 'roles', 'statuses', 'policyReqs'));
    }

    public function update(string $id): void
    {
        Auth::requirePermission('users.edit');

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
        $confirm   = $this->input('password_confirmation', '');
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
            $validator->matches('password_confirmation', $password, $confirm);
        }

        if ($validator->fails()) {
            Redirect::withErrors("/users/edit/{$userId}", $validator->errors(), compact(
                'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
            ));
        }

        // ── Política de contraseñas (solo si cambia contraseña) ───────────────
        $policySvc = new PasswordPolicyService();
        if ($password !== '') {
            $policyResult = $policySvc->validate($password, [
                'email'     => $user['email'],
                'nombres'   => $user['nombres'],
                'apellidos' => $user['apellidos'],
            ]);
            if (!$policyResult['valid']) {
                Redirect::withErrors("/users/edit/{$userId}", ['password' => implode(' ', $policyResult['errors'])], compact(
                    'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
                ));
            }
            if ($policySvc->isPasswordReused($password, $userId)) {
                Redirect::withErrors("/users/edit/{$userId}", ['password' => __('password_policy.error_reused', ['count' => (string)(int)($policySvc->getPolicy()['password_history_count'] ?? 3)])], compact(
                    'nombres', 'apellidos', 'telefono', 'direccion', 'email', 'roleId', 'statusId'
                ));
            }
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
            $uploadSvc = new UploadService();
            $result    = $uploadSvc->upload(
                $_FILES['profile_image'],
                'profile_images',
                ['prefix' => 'avatar_' . $userId . '_']
            );
            if (!$result['success']) {
                Redirect::withErrors("/users/edit/{$userId}", ['profile_image' => $result['error']]);
            }
            $newImage = $result['filename'];
            $uploadSvc->delete($user['profile_image'] ?? null, 'profile_images');
        }

        $forcePasswordChange = !empty($_POST['force_password_change']) ? 1 : 0;
        $data = [
            'nombres'               => $nombres,
            'apellidos'             => $apellidos,
            'telefono'              => $telefono ?: null,
            'direccion'             => $direccion ?: null,
            'email'                 => $email,
            'status_id'             => $statusId,
            'role_id'               => $roleId,
            'force_password_change' => $forcePasswordChange,
        ];

        $newHash = null;
        if ($password !== '') {
            $newHash          = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $data['password'] = $newHash;
        }
        if ($newImage) {
            $data['profile_image'] = $newImage;
        }

        if ($this->userModel->adminUpdate($userId, $data)) {
            // Guardar en historial si hubo cambio de contraseña
            if ($newHash !== null) {
                $policySvc->saveHistory($userId, $newHash);
                (new UserSessionService())->revokeAllUserSessions(
                    $userId,
                    Auth::id(),
                    'admin_password_changed',
                    $userId === (int)Auth::id()
                );
                Audit::log(['module' => 'users', 'action' => 'users.password_changed_by_admin',
                    'entity' => 'user', 'entity_id' => $userId,
                    'description' => "Contraseña de usuario ID {$userId} cambiada por admin ID " . Auth::id(),
                    'status' => 'success']);
            }
            $prevForce = (int)($user['force_password_change'] ?? 0);
            if ($forcePasswordChange !== $prevForce) {
                $forceAction = $forcePasswordChange ? 'users.force_password_change_enabled' : 'users.force_password_change_disabled';
                if ($forcePasswordChange) {
                    (new UserSessionService())->revokeAllUserSessions(
                        $userId,
                        Auth::id(),
                        'force_password_change',
                        $userId === (int)Auth::id()
                    );
                }
                Audit::log(['module' => 'users', 'action' => $forceAction,
                    'entity' => 'user', 'entity_id' => $userId,
                    'description' => ($forcePasswordChange ? 'Activado' : 'Desactivado') . " cambio obligatorio de contraseña para usuario ID {$userId}",
                    'status' => 'info', 'user_id' => Auth::id()]);
            }
            if ((int)$user['role_id'] !== $roleId) {
                Audit::log(['module' => 'users', 'action' => 'users.role_changed',
                    'entity' => 'user', 'entity_id' => $userId,
                    'description' => "Rol de usuario ID {$userId} actualizado",
                    'old_values' => ['role_id' => (int)$user['role_id']],
                    'new_values' => ['role_id' => $roleId],
                    'status' => 'success']);
            }
            if ((int)$user['status_id'] !== $statusId) {
                $statusAction = $statusId === 1 ? 'users.activated' : 'users.deactivated';
                Audit::log(['module' => 'users', 'action' => $statusAction,
                    'entity' => 'user', 'entity_id' => $userId,
                    'description' => "Estado de usuario ID {$userId} actualizado",
                    'old_values' => ['status_id' => (int)$user['status_id']],
                    'new_values' => ['status_id' => $statusId],
                    'status' => 'success']);
            }
            Logger::security("Usuario ID {$userId} actualizado por admin ID " . Auth::id());
            $oldAudit = ['nombres' => $user['nombres'], 'apellidos' => $user['apellidos'],
                'email' => $user['email'], 'role_id' => $user['role_id'], 'status_id' => $user['status_id']];
            $newAudit = ['nombres' => $nombres, 'apellidos' => $apellidos,
                'email' => $email, 'role_id' => $roleId, 'status_id' => $statusId,
                'force_password_change' => $forcePasswordChange];
            if ($password !== '') {
                $newAudit['password_changed'] = true;
            }
            Audit::log(['module' => 'users', 'action' => 'users.updated',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => "Usuario ID {$userId} actualizado",
                'old_values' => $oldAudit, 'new_values' => $newAudit, 'status' => 'success']);
            Redirect::withSuccess('/users', 'Usuario actualizado correctamente.');
        } else {
            Audit::log(['module' => 'users', 'action' => 'users.update_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => "Error al actualizar usuario ID {$userId}", 'status' => 'failed']);
            Redirect::withError("/users/edit/{$userId}", 'No se pudo actualizar el usuario.');
        }
    }

    public function delete(string $id): void
    {
        Auth::requirePermission('users.delete');

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
            Audit::log(['module' => 'users', 'action' => 'users.deactivated',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => "Usuario ID {$userId} inactivado", 'status' => 'success']);
            Redirect::withSuccess('/users', 'Usuario inactivado correctamente.');
        } else {
            Redirect::withError('/users', 'No se pudo inactivar el usuario.');
        }
    }

    public function unlock(string $id): void
    {
        Auth::requirePermission('users.unlock');

        if (!$this->isPost()) {
            Redirect::to('/users');
        }

        CSRF::validateOrFail();

        $userId = (int) $id;
        $user   = $this->userModel->findById($userId);

        if (!$user) {
            Session::flash('error', __('users.not_found'));
            Redirect::to('/users');
            exit;
        }

        if (!$this->userModel->isLockedByAttempts($user)) {
            Session::flash('error', __('users.not_locked'));
            Redirect::to('/users');
            exit;
        }

        $unlocked = $this->userModel->unlock($userId);

        if ($unlocked) {
            try {
                $attemptModel = new LoginAttempt();
                $attemptModel->record([
                    'user_id'        => $userId,
                    'email'          => $user['email'],
                    'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'status'         => 'user_unlocked',
                    'failure_reason' => null,
                ]);
            } catch (\Throwable $e) {
                Logger::error('UsersController::unlock attempt record — ' . $e->getMessage());
            }
            try {
                Audit::log([
                    'module'      => 'users',
                    'action'      => 'users.unlocked',
                    'entity'      => 'user',
                    'entity_id'   => $userId,
                    'description' => "Usuario ID {$userId} desbloqueado manualmente por admin ID " . Auth::id(),
                    'status'      => 'success',
                ]);
            } catch (\Throwable $e) {
                Logger::error('UsersController::unlock audit — ' . $e->getMessage());
            }
            Logger::security("Usuario ID {$userId} desbloqueado por admin ID " . Auth::id());
            Session::flash('success', __('users.unlock_success'));
        } else {
            Session::flash('error', __('users.unlock_error'));
        }

        Redirect::to('/users');
        exit;
    }

}
