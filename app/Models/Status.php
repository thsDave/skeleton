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

    public function findBySlug(string $slug): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function getIdBySlug(string $slug, int $fallback = 1): int
    {
        $stmt = $this->db->prepare('SELECT id FROM ' . self::TABLE . ' WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (int)($stmt->fetchColumn() ?: $fallback);
    }

    public function getActiveId(): int
    {
        return $this->getIdBySlug('active', 1);
    }

    public function getInactiveId(): int
    {
        return $this->getIdBySlug('inactive', 2);
    }

    public function getBlockedId(): int
    {
        return $this->getIdBySlug('blocked', 3);
    }

    public function isUserActive(array $user): bool
    {
        return ($user['status_slug'] ?? '') === 'active';
    }

    public function isUserBlocked(array $user): bool
    {
        return ($user['status_slug'] ?? '') === 'blocked';
    }
}
