<?php

namespace App\Models;

use Core\Model;

class SecuritySetting extends Model
{
    private const TABLE = 'tbl_security_settings';

    public function getSettings(): array
    {
        try {
            $stmt = $this->db->query(
                'SELECT * FROM ' . self::TABLE . ' WHERE id = 1 LIMIT 1'
            );
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (\PDOException $e) {
            error_log('SecuritySetting::getSettings() error: ' . $e->getMessage());
        }
        return [
            'id'                         => 1,
            'session_lock_enabled'       => 1,
            'session_inactivity_seconds' => 900,
        ];
    }

    public function updateSettings(bool $enabled, int $seconds): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                    (id, session_lock_enabled, session_inactivity_seconds)
                 VALUES (1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    session_lock_enabled       = VALUES(session_lock_enabled),
                    session_inactivity_seconds = VALUES(session_inactivity_seconds)'
            );
            return $stmt->execute([(int)$enabled, $seconds]);
        } catch (\PDOException $e) {
            error_log('SecuritySetting::updateSettings() error: ' . $e->getMessage());
            return false;
        }
    }
}
