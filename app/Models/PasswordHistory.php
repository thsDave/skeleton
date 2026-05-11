<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class PasswordHistory extends Model
{
    private const TABLE = 'tbl_password_histories';

    public function add(int $userId, string $hashedPassword): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . ' (user_id, password_hash) VALUES (?, ?)'
            );
            return $stmt->execute([$userId, $hashedPassword]);
        } catch (\Throwable $e) {
            Logger::error('PasswordHistory::add — ' . $e->getMessage());
            return false;
        }
    }

    public function getLastHashes(int $userId, int $count): array
    {
        if ($count <= 0) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT password_hash FROM ' . self::TABLE . '
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ' . (int) $count
            );
            $stmt->execute([$userId]);
            return array_column($stmt->fetchAll(), 'password_hash');
        } catch (\Throwable $e) {
            Logger::error('PasswordHistory::getLastHashes — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Remove old entries keeping only the $keepCount most recent for the user.
     */
    public function pruneOld(int $userId, int $keepCount): void
    {
        if ($keepCount <= 0) {
            return;
        }
        try {
            $stmt = $this->db->prepare(
                'DELETE FROM ' . self::TABLE . '
                 WHERE user_id = ?
                   AND id NOT IN (
                       SELECT id FROM (
                           SELECT id FROM ' . self::TABLE . '
                           WHERE user_id = ?
                           ORDER BY created_at DESC
                           LIMIT ' . (int) $keepCount . '
                       ) AS _keep
                   )'
            );
            $stmt->execute([$userId, $userId]);
        } catch (\Throwable $e) {
            Logger::error('PasswordHistory::pruneOld — ' . $e->getMessage());
        }
    }
}
