<?php

namespace App\Models;

use Core\Model;

class Role extends Model
{
    private const TABLE = 'tbl_roles';

    public function getAll(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return (bool)$stmt->fetch();
    }
}
