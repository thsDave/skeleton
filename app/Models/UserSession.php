<?php

namespace App\Models;

use Core\Model;

class UserSession extends Model
{
    private const TABLE = 'tbl_user_sessions';

    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
             (user_id, session_hash, ip_address, user_agent, browser, platform, device_type, last_activity_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $ok = $stmt->execute([
            $data['user_id'],
            $data['session_hash'],
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['browser'] ?? null,
            $data['platform'] ?? null,
            $data['device_type'] ?? null,
        ]);

        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function findByHash(string $hash): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE session_hash = ? LIMIT 1');
        $stmt->execute([$hash]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findActiveById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, u.email, CONCAT(u.nombres, " ", u.apellidos) AS user_name
             FROM ' . self::TABLE . ' s
             JOIN tbl_users u ON u.id = s.user_id
             WHERE s.id = ? AND s.revoked_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, u.email, CONCAT(u.nombres, " ", u.apellidos) AS user_name,
                    ru.email AS revoked_by_email, CONCAT(ru.nombres, " ", ru.apellidos) AS revoked_by_name
             FROM ' . self::TABLE . ' s
             JOIN tbl_users u ON u.id = s.user_id
             LEFT JOIN tbl_users ru ON ru.id = s.revoked_by
             WHERE s.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function touch(string $hash): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET last_activity_at = NOW() WHERE session_hash = ? AND revoked_at IS NULL'
        );
        return $stmt->execute([$hash]);
    }

    public function revokeById(int $id, ?int $revokedBy, string $reason): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET revoked_at = NOW(), revoked_by = ?, revoke_reason = ?
             WHERE id = ? AND revoked_at IS NULL'
        );
        return $stmt->execute([$revokedBy, $reason, $id]);
    }

    public function revokeByHash(string $hash, ?int $revokedBy, string $reason): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET revoked_at = NOW(), revoked_by = ?, revoke_reason = ?
             WHERE session_hash = ? AND revoked_at IS NULL'
        );
        return $stmt->execute([$revokedBy, $reason, $hash]);
    }

    public function revokeOtherSessions(int $userId, string $currentHash, ?int $revokedBy, string $reason): int
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET revoked_at = NOW(), revoked_by = ?, revoke_reason = ?
             WHERE user_id = ? AND session_hash <> ? AND revoked_at IS NULL'
        );
        $stmt->execute([$revokedBy, $reason, $userId, $currentHash]);
        return $stmt->rowCount();
    }

    public function revokeAllForUser(int $userId, ?int $revokedBy, string $reason, ?string $exceptHash = null): int
    {
        $sql = 'UPDATE ' . self::TABLE . '
                SET revoked_at = NOW(), revoked_by = ?, revoke_reason = ?
                WHERE user_id = ? AND revoked_at IS NULL';
        $params = [$revokedBy, $reason, $userId];

        if ($exceptHash !== null) {
            $sql .= ' AND session_hash <> ?';
            $params[] = $exceptHash;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function getActiveByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM ' . self::TABLE . '
             WHERE user_id = ? AND revoked_at IS NULL
             ORDER BY COALESCE(last_activity_at, created_at) DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveForAdmin(array $filters = []): array
    {
        $sql = 'SELECT s.*, u.email, CONCAT(u.nombres, " ", u.apellidos) AS user_name
                FROM ' . self::TABLE . ' s
                JOIN tbl_users u ON u.id = s.user_id
                WHERE s.revoked_at IS NULL';
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND s.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }

        $sql .= ' ORDER BY COALESCE(s.last_activity_at, s.created_at) DESC LIMIT 1000';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHistoryByUser(int $userId, array $filters = [], int $limit = 100): array
    {
        $sql = 'SELECT s.*, ru.email AS revoked_by_email,
                       CONCAT(ru.nombres, " ", ru.apellidos) AS revoked_by_name
                FROM ' . self::TABLE . ' s
                LEFT JOIN tbl_users ru ON ru.id = s.revoked_by
                WHERE s.user_id = ?';
        $params = [$userId];

        if (($filters['status'] ?? '') === 'active') {
            $sql .= ' AND s.revoked_at IS NULL';
        } elseif (($filters['status'] ?? '') === 'closed') {
            $sql .= ' AND s.revoked_at IS NOT NULL';
        }

        $sql .= ' ORDER BY s.created_at DESC, s.id DESC LIMIT ' . max(1, min(500, $limit));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
