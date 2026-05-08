<?php

namespace App\Models;

use Core\Model;

class Language extends Model
{
    private const TABLE = 'tbl_languages';

    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, s.slug AS status_slug, s.name AS status_name
             FROM ' . self::TABLE . ' l
             LEFT JOIN tbl_statuses s ON l.status_id = s.id
             ORDER BY l.is_default DESC, l.name ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*
             FROM ' . self::TABLE . ' l
             JOIN tbl_statuses s ON l.status_id = s.id
             WHERE s.slug = ?
             ORDER BY l.is_default DESC, l.name ASC'
        );
        $stmt->execute(['active']);
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, s.slug AS status_slug, s.name AS status_name
             FROM ' . self::TABLE . ' l
             LEFT JOIN tbl_statuses s ON l.status_id = s.id
             WHERE l.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByCode(string $code): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . ' WHERE code = ? LIMIT 1'
        );
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function codeExists(string $code, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM ' . self::TABLE . ' WHERE code = ? AND id != ? LIMIT 1'
        );
        $stmt->execute([$code, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return (bool)$stmt->fetch();
    }

    public function isActiveById(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT l.id FROM ' . self::TABLE . ' l
             JOIN tbl_statuses s ON l.status_id = s.id
             WHERE l.id = ? AND s.slug = ? LIMIT 1'
        );
        $stmt->execute([$id, 'active']);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . ' (name, native_name, code, is_default, status_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ok = $stmt->execute([
            $data['name'],
            $data['native_name'] ?? null,
            $data['code'],
            $data['is_default'] ?? 0,
            $data['status_id'],
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET name = ?, native_name = ?, code = ?, status_id = ?, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['name'],
            $data['native_name'] ?? null,
            $data['code'],
            $data['status_id'],
            $id,
        ]);
    }

    public function setStatus(int $id, int $statusId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET status_id = ?, updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$statusId, $id]);
    }

    public function countUsersWithLanguage(int $id): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM tbl_users WHERE language_id = ?'
        );
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }

    public function getActiveCount(): int
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . ' l
                 JOIN tbl_statuses s ON l.status_id = s.id
                 WHERE s.slug = ?'
            );
            $stmt->execute(['active']);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Language::getActiveCount: ' . $e->getMessage());
            return 0;
        }
    }

    public function getDefaultLanguage(): array|false
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT l.* FROM ' . self::TABLE . ' l
                 JOIN tbl_statuses s ON l.status_id = s.id
                 WHERE s.slug = ?
                 ORDER BY l.is_default DESC, l.id ASC
                 LIMIT 1'
            );
            $stmt->execute(['active']);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Language::getDefaultLanguage: ' . $e->getMessage());
            return false;
        }
    }
}
