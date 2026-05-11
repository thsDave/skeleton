<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class User extends Model
{
    private const TABLE = 'tbl_users';

    // ─── Consultas con JOINs (status_slug, role_slug) ────────────────────────

    private function withDetails(): string
    {
        return 'SELECT u.*,
                       s.slug AS status_slug, s.name AS status_name,
                       r.slug AS role_slug,   r.name AS role_name,
                       COALESCE(lang.code, \'es\') AS lang_code
                FROM ' . self::TABLE . ' u
                LEFT JOIN tbl_statuses  s    ON u.status_id   = s.id
                LEFT JOIN tbl_roles     r    ON u.role_id     = r.id
                LEFT JOIN tbl_languages lang ON u.language_id = lang.id';
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            $this->withDetails() . ' WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            $this->withDetails() . ' WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            $this->withDetails() . ' ORDER BY u.created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── Auth ─────────────────────────────────────────────────────────────────

    public function updateLastLogin(int $id, string $ip): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET last_login_at = NOW(), last_login_ip = ?,
                 failed_login_attempts = 0, locked_until = NULL
             WHERE id = ?'
        );
        $stmt->execute([$ip, $id]);
    }

    public function incrementFailedAttempts(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET failed_login_attempts = failed_login_attempts + 1,
                 last_failed_login_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function unlock(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET locked_until = NULL, failed_login_attempts = 0, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    public function isLockedByAttempts(array $user): bool
    {
        return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
    }

    public function lockAccount(int $id, int $minutes): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE)
             WHERE id = ?'
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

    // ─── Perfil propio ────────────────────────────────────────────────────────

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

    public function updateProfileImage(int $id, string $filename): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET profile_image = ?, updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$filename, $id]);
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

    public function updatePreferences(int $id, string $theme, ?int $languageId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET theme_preference = ?, language_id = ?, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$theme, $languageId, $id]);
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

    // ─── CRUD admin ───────────────────────────────────────────────────────────

    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
             (nombres, apellidos, telefono, direccion, email, password,
              status_id, role_id, profile_image, force_password_change,
              password_changed_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $ok = $stmt->execute([
            $data['nombres'],
            $data['apellidos'],
            $data['telefono']             ?? null,
            $data['direccion']            ?? null,
            $data['email'],
            $data['password'],
            $data['status_id']            ?? 1,
            $data['role_id']              ?? 2,
            $data['profile_image']        ?? null,
            (int)($data['force_password_change'] ?? 0),
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function adminUpdate(int $id, array $data): bool
    {
        $fields = ['nombres=?', 'apellidos=?', 'telefono=?', 'direccion=?',
                   'email=?', 'status_id=?', 'role_id=?', 'updated_at=NOW()'];
        $params = [
            $data['nombres'],
            $data['apellidos'],
            $data['telefono']  ?? null,
            $data['direccion'] ?? null,
            $data['email'],
            $data['status_id'],
            $data['role_id'],
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password=?';
            $params[] = $data['password'];
            $fields[] = 'password_changed_at=NOW()';
        }
        if (array_key_exists('profile_image', $data) && $data['profile_image'] !== null) {
            $fields[] = 'profile_image=?';
            $params[] = $data['profile_image'];
        }
        if (array_key_exists('force_password_change', $data)) {
            $fields[] = 'force_password_change=?';
            $params[] = (int)$data['force_password_change'];
        }

        $params[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        return $stmt->execute($params);
    }

    public function updatePasswordAndClearForce(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET password = ?, password_changed_at = NOW(), force_password_change = 0, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$hashedPassword, $id]);
    }

    public function inactivate(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . ' SET status_id = 2, updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    public function countActiveAdmins(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' u
             JOIN tbl_roles    r ON u.role_id   = r.id
             JOIN tbl_statuses s ON u.status_id = s.id
             WHERE r.slug = ? AND s.slug = ?'
        );
        $stmt->execute(['administrator', 'active']);
        return (int)$stmt->fetchColumn();
    }

    public function isLastActiveAdmin(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user || ($user['role_slug'] ?? '') !== 'administrator') {
            return false;
        }
        return $this->countActiveAdmins() <= 1;
    }

    // ─── 2FA ──────────────────────────────────────────────────────────────────

    public function enableTwoFactor(int $id, string $method, ?string $secretEnc = null, ?string $phone = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET two_factor_enabled = 1, two_factor_method = ?,
                 two_factor_secret_enc = ?, two_factor_phone = ?, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$method, $secretEnc, $phone, $id]);
    }

    public function disableTwoFactor(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET two_factor_enabled = 0, two_factor_method = NULL,
                 two_factor_secret_enc = NULL, two_factor_phone = NULL, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }

    // ─── Dashboard stats ──────────────────────────────────────────────────────

    public function getDashboardStats(): array
    {
        try {
            $stmt = $this->db->query(
                'SELECT
                   COUNT(*)                                                                    AS total,
                   SUM(CASE WHEN s.slug = \'active\' THEN 1 ELSE 0 END)                       AS active,
                   SUM(CASE WHEN s.slug != \'active\' THEN 1 ELSE 0 END)                      AS inactive,
                   SUM(CASE WHEN u.two_factor_enabled = 1 THEN 1 ELSE 0 END)                  AS with_mfa,
                   SUM(CASE WHEN COALESCE(u.two_factor_enabled, 0) = 0 THEN 1 ELSE 0 END)     AS without_mfa,
                   SUM(CASE WHEN u.profile_image IS NOT NULL AND u.profile_image != \'\'
                             THEN 1 ELSE 0 END)                                               AS with_image,
                   SUM(CASE WHEN u.profile_image IS NULL OR u.profile_image = \'\'
                             THEN 1 ELSE 0 END)                                               AS without_image,
                   SUM(CASE WHEN (u.telefono IS NULL OR u.telefono = \'\')
                               OR (u.direccion IS NULL OR u.direccion = \'\')
                             THEN 1 ELSE 0 END)                                               AS incomplete
                 FROM ' . self::TABLE . ' u
                 LEFT JOIN tbl_statuses s ON u.status_id = s.id'
            );
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('User::getDashboardStats: ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsByRole(): array
    {
        try {
            $stmt = $this->db->query(
                'SELECT r.name AS role_name, r.slug AS role_slug, COUNT(u.id) AS user_count
                 FROM ' . self::TABLE . ' u
                 LEFT JOIN tbl_roles r ON u.role_id = r.id
                 GROUP BY r.id, r.name, r.slug
                 ORDER BY user_count DESC'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('User::getStatsByRole: ' . $e->getMessage());
            return [];
        }
    }

    public function getMfaMethodStats(): array
    {
        try {
            $stmt = $this->db->query(
                'SELECT two_factor_method, COUNT(*) AS count
                 FROM ' . self::TABLE . '
                 WHERE two_factor_enabled = 1 AND two_factor_method IS NOT NULL AND two_factor_method != \'sms\'
                 GROUP BY two_factor_method'
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('User::getMfaMethodStats: ' . $e->getMessage());
            return [];
        }
    }

    public function getLastRegistered(): array|false
    {
        try {
            $stmt = $this->db->query(
                'SELECT u.*, CONCAT(u.nombres, \' \', u.apellidos) AS full_name, r.name AS role_name
                 FROM ' . self::TABLE . ' u
                 LEFT JOIN tbl_roles r ON u.role_id = r.id
                 ORDER BY u.created_at DESC
                 LIMIT 1'
            );
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('User::getLastRegistered: ' . $e->getMessage());
            return false;
        }
    }
}
