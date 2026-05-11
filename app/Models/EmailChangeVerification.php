<?php

namespace App\Models;

use Core\Model;

class EmailChangeVerification extends Model
{
    private const TABLE = 'tbl_email_change_verifications';
    private const MAX_ATTEMPTS = 5;

    public function invalidatePendingForUser(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL'
        );
        return $stmt->execute([$userId]);
    }

    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
                (user_id, current_email, new_email, code_hash, expires_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ok = $stmt->execute([
            (int) $data['user_id'],
            $data['current_email'],
            $data['new_email'],
            $data['code_hash'],
            $data['expires_at'],
        ]);

        return $ok ? (int) $this->db->lastInsertId() : false;
    }

    public function findPendingForUser(int $userId): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM ' . self::TABLE . '
             WHERE user_id = ? AND used_at IS NULL
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function incrementAttempts(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET attempts = attempts + 1
             WHERE id = ? AND used_at IS NULL'
        );
        return $stmt->execute([$id]);
    }

    public function markUsed(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET used_at = NOW()
             WHERE id = ? AND used_at IS NULL'
        );
        return $stmt->execute([$id]);
    }

    public function countRecentRequests(int $userId, string $newEmail, int $minutes): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM ' . self::TABLE . '
             WHERE user_id = ?
               AND new_email = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$userId, $newEmail, $minutes]);
        return (int) $stmt->fetchColumn();
    }

    public function completeEmailChange(int $verificationId, int $userId, string $newEmail): bool
    {
        $this->db->beginTransaction();

        try {
            $userStmt = $this->db->prepare(
                'UPDATE tbl_users
                 SET email = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $userStmt->execute([$newEmail, $userId]);

            $verifyStmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET used_at = NOW()
                 WHERE id = ? AND user_id = ? AND used_at IS NULL'
            );
            $verifyStmt->execute([$verificationId, $userId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function isExpired(array $record): bool
    {
        return strtotime((string) $record['expires_at']) <= time();
    }

    public function hasTooManyAttempts(array $record): bool
    {
        return (int) ($record['attempts'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    public function maxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }
}
