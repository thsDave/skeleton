<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class AuthenticationSettings extends Model
{
    private const TABLE = 'tbl_authentication_settings';

    public function get(): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = 1 LIMIT 1');
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: $this->defaults();
        } catch (\Throwable $e) {
            Logger::error('AuthenticationSettings::get — ' . $e->getMessage());
            return $this->defaults();
        }
    }

    public function save(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                 (id, local_login_enabled, external_login_enabled,
                  allow_auto_user_creation, default_role_id,
                  require_existing_user, allow_account_linking)
                 VALUES (1, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                  local_login_enabled      = VALUES(local_login_enabled),
                  external_login_enabled   = VALUES(external_login_enabled),
                  allow_auto_user_creation = VALUES(allow_auto_user_creation),
                  default_role_id          = VALUES(default_role_id),
                  require_existing_user    = VALUES(require_existing_user),
                  allow_account_linking    = VALUES(allow_account_linking)'
            );
            return $stmt->execute([
                (int) ($data['local_login_enabled']      ?? 1),
                (int) ($data['external_login_enabled']   ?? 0),
                (int) ($data['allow_auto_user_creation'] ?? 0),
                !empty($data['default_role_id']) ? (int) $data['default_role_id'] : null,
                (int) ($data['require_existing_user']    ?? 1),
                (int) ($data['allow_account_linking']    ?? 1),
            ]);
        } catch (\Throwable $e) {
            Logger::error('AuthenticationSettings::save — ' . $e->getMessage());
            return false;
        }
    }

    private function defaults(): array
    {
        return [
            'id'                       => 1,
            'local_login_enabled'      => 1,
            'external_login_enabled'   => 0,
            'allow_auto_user_creation' => 0,
            'default_role_id'          => null,
            'require_existing_user'    => 1,
            'allow_account_linking'    => 1,
        ];
    }
}
