<?php

namespace App\Models;

use Core\Model;

class RolePermission extends Model
{
    private const TABLE = 'tbl_role_permissions';

    /**
     * Returns all permission slugs assigned to a role identified by its slug.
     * Used to populate the session permissions cache.
     */
    public function getPermissionSlugsByRoleSlug(string $roleSlug): array
    {
        $stmt = $this->db->prepare("
            SELECT p.slug
            FROM tbl_permissions p
            JOIN tbl_role_permissions rp ON rp.permission_id = p.id
            JOIN tbl_roles r ON r.id = rp.role_id
            WHERE r.slug = ?
        ");
        $stmt->execute([$roleSlug]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Returns all permission IDs (as ints) assigned to a role ID.
     * Used to pre-check checkboxes in the edit view.
     */
    public function getPermissionIdsByRoleId(int $roleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT permission_id FROM " . self::TABLE . " WHERE role_id = ?"
        );
        $stmt->execute([$roleId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Replaces all permissions for a role with the given set.
     * Runs inside a transaction.
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare(
                "DELETE FROM " . self::TABLE . " WHERE role_id = ?"
            );
            $del->execute([$roleId]);

            if (!empty($permissionIds)) {
                $ins = $this->db->prepare(
                    "INSERT IGNORE INTO " . self::TABLE . " (role_id, permission_id) VALUES (?, ?)"
                );
                foreach ($permissionIds as $permId) {
                    $ins->execute([$roleId, (int)$permId]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
