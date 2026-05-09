<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class UserExternalAccount extends Model
{
    private const TABLE = 'tbl_user_external_accounts';

    public function findByProviderUser(int $providerId, string $providerUserId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM ' . self::TABLE . '
                 WHERE provider_id = ? AND provider_user_id = ? LIMIT 1'
            );
            $stmt->execute([$providerId, $providerUserId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::findByProviderUser — ' . $e->getMessage());
            return null;
        }
    }

    public function findByUserAndProvider(int $userId, int $providerId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM ' . self::TABLE . '
                 WHERE user_id = ? AND provider_id = ? LIMIT 1'
            );
            $stmt->execute([$userId, $providerId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::findByUserAndProvider — ' . $e->getMessage());
            return null;
        }
    }

    public function allForUser(int $userId): array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT uea.*, p.name AS provider_display_name, p.slug AS provider_slug
                 FROM ' . self::TABLE . ' uea
                 JOIN tbl_external_auth_providers p ON p.id = uea.provider_id
                 WHERE uea.user_id = ?
                 ORDER BY uea.linked_at ASC'
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::allForUser — ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                 (user_id, provider_id, provider_user_id, provider_email, provider_name, avatar_url, linked_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            return $stmt->execute([
                (int) $data['user_id'],
                (int) $data['provider_id'],
                (string) $data['provider_user_id'],
                $data['provider_email'] ?? null,
                $data['provider_name']  ?? null,
                $data['avatar_url']     ?? null,
            ]);
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::create — ' . $e->getMessage());
            return false;
        }
    }

    public function updateLastLogin(int $userId, int $providerId): void
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET last_login_at = NOW(), updated_at = NOW()
                 WHERE user_id = ? AND provider_id = ?'
            );
            $stmt->execute([$userId, $providerId]);
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::updateLastLogin — ' . $e->getMessage());
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::findById — ' . $e->getMessage());
            return null;
        }
    }

    public function deleteById(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::deleteById — ' . $e->getMessage());
            return false;
        }
    }

    public function countForUser(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE user_id = ?'
            );
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            Logger::error('UserExternalAccount::countForUser — ' . $e->getMessage());
            return 0;
        }
    }
}
