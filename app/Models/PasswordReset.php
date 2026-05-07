<?php

namespace App\Models;

use Core\Model;

class PasswordReset extends Model
{
    private const TABLE = 'tbl_password_resets';

    /**
     * Crea un nuevo registro de recuperación.
     */
    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
             (user_id, email, token_hash, expires_at, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ok = $stmt->execute([
            $data['user_id'],
            $data['email'],
            $data['token_hash'],
            $data['expires_at'],
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Busca un token válido (no usado, no expirado) por su hash SHA-256.
     */
    public function findValidByTokenHash(string $tokenHash): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . '
             WHERE token_hash = ?
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        return $stmt->fetch();
    }

    /**
     * Marca un token como usado (consumido).
     */
    public function markAsUsed(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET used_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    /**
     * Invalida todos los tokens no usados de un usuario (antes de crear uno nuevo).
     */
    public function invalidatePreviousTokens(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL'
        );
        $stmt->execute([$userId]);
    }

    /**
     * Cuenta solicitudes recientes para rate limiting.
     */
    public function countRecentRequests(string $email, int $minutes): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . self::TABLE . '
             WHERE email = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$email, $minutes]);
        return (int)$stmt->fetchColumn();
    }
}
