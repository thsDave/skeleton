<?php

namespace App\Models;

use Core\Model;

class UserManual extends Model
{
    private const TABLE = 'tbl_user_manuals';

    private function withDetails(): string
    {
        return 'SELECT m.*,
                       s.slug AS status_slug, s.name AS status_name,
                       CONCAT(u.nombres, \' \', u.apellidos) AS uploaded_by_name
                FROM ' . self::TABLE . ' m
                LEFT JOIN tbl_statuses s ON m.status_id = s.id
                LEFT JOIN tbl_users    u ON m.uploaded_by = u.id';
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            $this->withDetails() . ' WHERE m.deleted_at IS NULL ORDER BY m.created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $stmt = $this->db->prepare(
            $this->withDetails() . ' WHERE s.slug = ? AND m.deleted_at IS NULL ORDER BY m.created_at DESC'
        );
        $stmt->execute(['active']);
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            $this->withDetails() . ' WHERE m.id = ? AND m.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
             (title, description, file_name, file_path, file_type, file_size, uploaded_by, status_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ok = $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['file_name'],
            $data['file_path'],
            $data['file_type'],
            $data['file_size'],
            $data['uploaded_by'],
            $data['status_id'] ?? 1,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function setStatus(int $id, int $statusId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET status_id = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL'
        );
        return $stmt->execute([$statusId, $id]);
    }

    public function replaceFile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET file_name = ?, file_path = ?, file_type = ?, file_size = ?, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL'
        );
        return $stmt->execute([
            $data['file_name'],
            $data['file_path'],
            $data['file_type'],
            $data['file_size'],
            $id,
        ]);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL'
        );
        return $stmt->execute([$id]);
    }
}
