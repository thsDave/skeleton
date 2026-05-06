<?php

namespace App\Models;

use Core\Model;

class SystemSetting extends Model
{
    private const TABLE = 'tbl_system_settings';

    public function get(): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . ' ORDER BY id ASC LIMIT 1'
        );
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createOrUpdate(array $data): bool
    {
        $existing = $this->get();
        if ($existing) {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET release_year = ?, project_leader = ?, system_version = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            return $stmt->execute([
                $data['release_year'],
                $data['project_leader'],
                $data['system_version'],
                $existing['id'],
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . ' (release_year, project_leader, system_version)
             VALUES (?, ?, ?)'
        );
        return $stmt->execute([
            $data['release_year'],
            $data['project_leader'],
            $data['system_version'],
        ]);
    }
}
