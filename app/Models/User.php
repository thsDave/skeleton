<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class User extends Model
{
    private const TABLE = 'tbl_users';

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . ' WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateLastLogin(int $id, string $ip): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET last_login_at = NOW(), last_login_ip = ?, failed_login_attempts = 0, locked_until = NULL
             WHERE id = ?'
        );
        $stmt->execute([$ip, $id]);
    }

    public function incrementFailedAttempts(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function lockAccount(int $id, int $minutes): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?'
        );
        $stmt->execute([$minutes, $id]);
    }

    public function isLocked(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }
        return strtotime($user['locked_until']) > time();
    }

    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET nombres = ?, apellidos = ?, telefono = ?, direccion = ?, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['nombres'],
            $data['apellidos'],
            $data['telefono'] ?? null,
            $data['direccion'] ?? null,
            $id,
        ]);
    }

    public function updateEmail(int $id, string $email): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET email = ?, updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$email, $id]);
    }

    public function emailExists(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM ' . self::TABLE . ' WHERE email = ? AND id != ? LIMIT 1'
        );
        $stmt->execute([$email, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET password = ?, password_changed_at = NOW(), updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$hashedPassword, $id]);
    }
}
