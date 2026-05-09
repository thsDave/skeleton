<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class LoginSecuritySetting extends Model
{
    private const TABLE = 'tbl_login_security_settings';

    public function get(): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = 1 LIMIT 1');
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: $this->defaults();
        } catch (\Throwable $e) {
            Logger::error('LoginSecuritySetting::get — ' . $e->getMessage());
            return $this->defaults();
        }
    }

    public function save(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                 (id, failed_login_protection_enabled, max_failed_attempts_user,
                  user_attempt_window_minutes, user_lockout_minutes,
                  ip_protection_enabled, max_failed_attempts_ip,
                  ip_attempt_window_minutes, ip_lockout_minutes)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                  failed_login_protection_enabled = VALUES(failed_login_protection_enabled),
                  max_failed_attempts_user        = VALUES(max_failed_attempts_user),
                  user_attempt_window_minutes     = VALUES(user_attempt_window_minutes),
                  user_lockout_minutes            = VALUES(user_lockout_minutes),
                  ip_protection_enabled           = VALUES(ip_protection_enabled),
                  max_failed_attempts_ip          = VALUES(max_failed_attempts_ip),
                  ip_attempt_window_minutes       = VALUES(ip_attempt_window_minutes),
                  ip_lockout_minutes              = VALUES(ip_lockout_minutes)'
            );
            return $stmt->execute([
                (int) $data['failed_login_protection_enabled'],
                (int) $data['max_failed_attempts_user'],
                (int) $data['user_attempt_window_minutes'],
                (int) $data['user_lockout_minutes'],
                (int) $data['ip_protection_enabled'],
                (int) $data['max_failed_attempts_ip'],
                (int) $data['ip_attempt_window_minutes'],
                (int) $data['ip_lockout_minutes'],
            ]);
        } catch (\Throwable $e) {
            Logger::error('LoginSecuritySetting::save — ' . $e->getMessage());
            return false;
        }
    }

    private function defaults(): array
    {
        return [
            'id'                              => 1,
            'failed_login_protection_enabled' => 1,
            'max_failed_attempts_user'        => 5,
            'user_attempt_window_minutes'     => 15,
            'user_lockout_minutes'            => 15,
            'ip_protection_enabled'           => 1,
            'max_failed_attempts_ip'          => 20,
            'ip_attempt_window_minutes'       => 15,
            'ip_lockout_minutes'              => 30,
        ];
    }
}
