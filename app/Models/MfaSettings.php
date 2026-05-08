<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class MfaSettings extends Model
{
    private const TABLE = 'tbl_mfa_settings';

    public function get(): array
    {
        try {
            $stmt = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = 1 LIMIT 1');
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (\PDOException $e) {
            Logger::error('MfaSettings::get() error: ' . $e->getMessage());
        }
        return $this->defaults();
    }

    public function update(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . ' (id, email_enabled, sms_enabled, authenticator_enabled)
                 VALUES (1, ?, 0, ?)
                 ON DUPLICATE KEY UPDATE
                    email_enabled         = VALUES(email_enabled),
                    sms_enabled           = 0,
                    authenticator_enabled = VALUES(authenticator_enabled),
                    updated_at            = NOW()'
            );
            return $stmt->execute([
                (int) ($data['email_enabled']         ?? 0),
                (int) ($data['authenticator_enabled'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            Logger::error('MfaSettings::update() error: ' . $e->getMessage());
            return false;
        }
    }

    private function defaults(): array
    {
        return [
            'id'                    => 1,
            'email_enabled'         => 0,
            'sms_enabled'           => 0,
            'authenticator_enabled' => 1,
            'created_at'            => null,
            'updated_at'            => null,
        ];
    }
}
