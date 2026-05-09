<?php

namespace App\Models;

use Core\Model;
use Core\Logger;
use Core\Crypt;

class ExternalAuthProvider extends Model
{
    private const TABLE = 'tbl_external_auth_providers';
    private const ALLOWED_SLUGS = ['google', 'microsoft', 'github'];

    public function all(): array
    {
        try {
            $stmt = $this->db->query('SELECT * FROM ' . self::TABLE . ' ORDER BY id ASC');
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::all — ' . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::find — ' . $e->getMessage());
            return null;
        }
    }

    public function findBySlug(string $slug): ?array
    {
        if (!in_array($slug, self::ALLOWED_SLUGS, true)) {
            return null;
        }
        try {
            $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::findBySlug — ' . $e->getMessage());
            return null;
        }
    }

    public function allEnabled(): array
    {
        try {
            $stmt = $this->db->query(
                'SELECT * FROM ' . self::TABLE . ' WHERE is_enabled = 1 ORDER BY id ASC'
            );
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::allEnabled — ' . $e->getMessage());
            return [];
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            if (!empty($data['client_secret'])) {
                $encryptedSecret = Crypt::encrypt($data['client_secret']);
                $stmt = $this->db->prepare(
                    'UPDATE ' . self::TABLE . ' SET
                     client_id = ?, client_secret = ?, tenant_id = ?, redirect_uri = ?,
                     scopes = ?, authorization_url = ?, token_url = ?, userinfo_url = ?,
                     is_enabled = ?, is_verified = 0, updated_at = NOW()
                     WHERE id = ?'
                );
                return $stmt->execute([
                    $data['client_id']         ?: null,
                    $encryptedSecret,
                    $data['tenant_id']         ?: null,
                    $data['redirect_uri']      ?: null,
                    $data['scopes']            ?: null,
                    $data['authorization_url'] ?: null,
                    $data['token_url']         ?: null,
                    $data['userinfo_url']      ?: null,
                    (int) ($data['is_enabled'] ?? 0),
                    $id,
                ]);
            }

            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . ' SET
                 client_id = ?, tenant_id = ?, redirect_uri = ?,
                 scopes = ?, authorization_url = ?, token_url = ?, userinfo_url = ?,
                 is_enabled = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            return $stmt->execute([
                $data['client_id']         ?: null,
                $data['tenant_id']         ?: null,
                $data['redirect_uri']      ?: null,
                $data['scopes']            ?: null,
                $data['authorization_url'] ?: null,
                $data['token_url']         ?: null,
                $data['userinfo_url']      ?: null,
                (int) ($data['is_enabled'] ?? 0),
                $id,
            ]);
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::update — ' . $e->getMessage());
            return false;
        }
    }

    public function toggle(int $id): bool
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET is_enabled = 1 - is_enabled, updated_at = NOW()
                 WHERE id = ?'
            );
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::toggle — ' . $e->getMessage());
            return false;
        }
    }

    public function updateTestResult(int $id, bool $success, string $message): bool
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . ' SET
                 last_tested_at = NOW(), last_test_status = ?, last_test_message = ?,
                 updated_at = NOW()
                 WHERE id = ?'
            );
            return $stmt->execute([$success ? 'success' : 'failed', $message, $id]);
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::updateTestResult — ' . $e->getMessage());
            return false;
        }
    }

    public function markVerified(int $id): void
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . ' SET is_verified = 1, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$id]);
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::markVerified — ' . $e->getMessage());
        }
    }

    public function decryptSecret(?string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        try {
            return Crypt::decrypt($encrypted);
        } catch (\Throwable $e) {
            Logger::error('ExternalAuthProvider::decryptSecret — ' . $e->getMessage());
            return '';
        }
    }

    public static function allowedSlugs(): array
    {
        return self::ALLOWED_SLUGS;
    }
}
