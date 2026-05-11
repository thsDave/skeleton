<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class PasswordPolicy extends Model
{
    private const TABLE = 'tbl_password_policies';

    public function get(): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = 1 LIMIT 1');
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: $this->defaults();
        } catch (\Throwable $e) {
            Logger::error('PasswordPolicy::get — ' . $e->getMessage());
            return $this->defaults();
        }
    }

    public function save(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                 (id, is_enabled, min_length, require_uppercase, require_lowercase,
                  require_number, require_special, prevent_email_in_password,
                  prevent_name_in_password, prevent_common_passwords,
                  password_history_count, password_expiration_days)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                  is_enabled                = VALUES(is_enabled),
                  min_length                = VALUES(min_length),
                  require_uppercase         = VALUES(require_uppercase),
                  require_lowercase         = VALUES(require_lowercase),
                  require_number            = VALUES(require_number),
                  require_special           = VALUES(require_special),
                  prevent_email_in_password = VALUES(prevent_email_in_password),
                  prevent_name_in_password  = VALUES(prevent_name_in_password),
                  prevent_common_passwords  = VALUES(prevent_common_passwords),
                  password_history_count    = VALUES(password_history_count),
                  password_expiration_days  = VALUES(password_expiration_days),
                  updated_at                = NOW()'
            );
            return $stmt->execute([
                (int) ($data['is_enabled']                ?? 1),
                (int) ($data['min_length']                ?? 10),
                (int) ($data['require_uppercase']         ?? 1),
                (int) ($data['require_lowercase']         ?? 1),
                (int) ($data['require_number']            ?? 1),
                (int) ($data['require_special']           ?? 1),
                (int) ($data['prevent_email_in_password'] ?? 1),
                (int) ($data['prevent_name_in_password']  ?? 1),
                (int) ($data['prevent_common_passwords']  ?? 1),
                (int) ($data['password_history_count']    ?? 3),
                (int) ($data['password_expiration_days']  ?? 0),
            ]);
        } catch (\Throwable $e) {
            Logger::error('PasswordPolicy::save — ' . $e->getMessage());
            return false;
        }
    }

    private function defaults(): array
    {
        return [
            'id'                        => 1,
            'is_enabled'                => 1,
            'min_length'                => 10,
            'require_uppercase'         => 1,
            'require_lowercase'         => 1,
            'require_number'            => 1,
            'require_special'           => 1,
            'prevent_email_in_password' => 1,
            'prevent_name_in_password'  => 1,
            'prevent_common_passwords'  => 1,
            'password_history_count'    => 3,
            'password_expiration_days'  => 0,
        ];
    }
}
