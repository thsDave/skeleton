<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Logger;
use Core\Redirect;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;

class RolesPermissionsController extends Controller
{
    private Role           $roleModel;
    private Permission     $permModel;
    private RolePermission $rpModel;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->permModel = new Permission();
        $this->rpModel   = new RolePermission();
    }

    public function index(): void
    {
        Auth::requirePermission('roles_permissions.view');
        $authUser = Auth::user();
        $roles    = $this->roleModel->getAll();
        $this->view('roles_permissions.index', compact('authUser', 'roles'));
    }

    public function edit(string $roleId): void
    {
        Auth::requirePermission('roles_permissions.view');
        $authUser = Auth::user();
        $roleId   = (int)$roleId;
        $role     = $this->roleModel->findById($roleId);

        if (!$role) {
            Redirect::withError('/roles-permissions', __('roles_permissions.role_not_found'));
        }

        $modules     = $this->permModel->getAllGroupedByModule();
        $assigned    = $this->rpModel->getPermissionIdsByRoleId($roleId);

        $this->view('roles_permissions.edit', compact('authUser', 'role', 'modules', 'assigned'));
    }

    public function update(string $roleId): void
    {
        Auth::requirePermission('roles_permissions.edit');

        if (!$this->isPost()) {
            Redirect::to('/roles-permissions');
        }

        CSRF::validateOrFail();

        $roleId = (int)$roleId;
        $role   = $this->roleModel->findById($roleId);

        if (!$role) {
            Redirect::withError('/roles-permissions', __('roles_permissions.role_not_found'));
        }

        // Validate submitted permission IDs against the DB
        $submittedIds = array_map('intval', (array)($_POST['permissions'] ?? []));
        $validIds     = $this->permModel->filterValidIds($submittedIds);

        // Safety: the administrator role must always keep roles_permissions.edit
        if (($role['slug'] ?? '') === 'administrator') {
            $editPermId = $this->permModel->getIdBySlug('roles_permissions.edit');
            if ($editPermId && !in_array($editPermId, $validIds, true)) {
                $validIds[] = $editPermId;
            }
        }

        try {
            $this->rpModel->syncPermissions($roleId, $validIds);
        } catch (\Throwable $e) {
            Logger::error('RolesPermissionsController::update — ' . $e->getMessage());
            Redirect::withError("/roles-permissions/edit/{$roleId}", __('alerts.internal'));
        }

        // If the current user has this role, refresh their session permissions immediately
        if (Auth::user()['role_slug'] === ($role['slug'] ?? '')) {
            Auth::refreshPermissions();
        }

        Logger::security(
            "Permisos del rol '{$role['name']}' (ID {$roleId}) actualizados por usuario ID " . Auth::id()
        );

        Redirect::withSuccess('/roles-permissions', __('roles_permissions.updated'));
    }
}
