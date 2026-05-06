<?php

namespace App\Models;

use Core\Model;

class Status extends Model
{
    private const TABLE = 'tbl_statuses';

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

    public function getActiveId(): int
    {
        $stmt = $this->db->prepare("SELECT id FROM " . self::TABLE . " WHERE slug = 'active' LIMIT 1");
        $stmt->execute();
        return (int)($stmt->fetchColumn() ?: 1);
    }
}
