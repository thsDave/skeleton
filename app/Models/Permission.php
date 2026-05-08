<?php

namespace App\Models;

use Core\Model;

class Permission extends Model
{
    private const TABLE = 'tbl_permissions';

    /**
     * Returns all permissions grouped by module for the edit-role view.
     * Structure: [ module_id => [ 'module_name' => ..., 'module_slug' => ..., 'permissions' => [...] ] ]
     */
    public function getAllGroupedByModule(): array
    {
        $stmt = $this->db->query("
            SELECT p.*, m.name AS module_name, m.slug AS module_slug, m.sort_order AS module_sort_order
            FROM tbl_permissions p
            JOIN tbl_modules m ON m.id = p.module_id
            ORDER BY m.sort_order, p.id
        ");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $mid = (int)$row['module_id'];
            if (!isset($grouped[$mid])) {
                $grouped[$mid] = [
                    'module_name' => $row['module_name'],
                    'module_slug' => $row['module_slug'],
                    'permissions' => [],
                ];
            }
            $grouped[$mid]['permissions'][] = $row;
        }
        return $grouped;
    }

    /**
     * Filters an array of IDs against the DB and returns only valid ones as ints.
     */
    public function filterValidIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $ids          = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt         = $this->db->prepare(
            "SELECT id FROM " . self::TABLE . " WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function getIdBySlug(string $slug): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM " . self::TABLE . " WHERE slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    public function getCount(): int
    {
        try {
            $stmt = $this->db->query('SELECT COUNT(*) FROM ' . self::TABLE);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Permission::getCount: ' . $e->getMessage());
            return 0;
        }
    }
}
