<?php

namespace App\Models;

use Core\Model;

class TwoFactorCode extends Model
{
    private const TABLE = 'tbl_two_factor_codes';

    public function create(int $userId, string $codeHash, string $method, int $expiresMinutes): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . ' (user_id, code_hash, method, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
        );
        $stmt->execute([$userId, $codeHash, $method, $expiresMinutes]);
        return (int) $this->db->lastInsertId();
    }

    public function findValid(int $userId, string $method): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . '
             WHERE user_id = ? AND method = ? AND used = 0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId, $method]);
        return $stmt->fetch();
    }

    public function incrementAttempts(int $id): void
    {
        $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET attempts = attempts + 1 WHERE id = ?'
        )->execute([$id]);
    }

    public function markUsed(int $id): void
    {
        $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET used = 1 WHERE id = ?'
        )->execute([$id]);
    }

    public function deleteForUser(int $userId, string $method): void
    {
        $this->db->prepare(
            'DELETE FROM ' . self::TABLE . ' WHERE user_id = ? AND method = ?'
        )->execute([$userId, $method]);
    }

    public function getLastCreatedAt(int $userId, string $method): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT created_at FROM ' . self::TABLE . '
             WHERE user_id = ? AND method = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId, $method]);
        $row = $stmt->fetch();
        return $row ? $row['created_at'] : null;
    }
}
