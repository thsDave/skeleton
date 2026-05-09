<?php

namespace App\Models;

use Core\Model;
use Core\Logger;

class LoginAttempt extends Model
{
    private const TABLE = 'tbl_login_attempts';

    public function record(array $data): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                 (user_id, email, ip_address, user_agent, status, failure_reason, attempted_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $data['user_id']        ?? null,
                $data['email']          ?? null,
                $data['ip_address']     ?? null,
                mb_substr($data['user_agent'] ?? '', 0, 255),
                $data['status'],
                $data['failure_reason'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Logger::error('LoginAttempt::record — ' . $e->getMessage());
        }
    }

    public function countRecentFailedByIp(string $ip, int $windowMinutes): int
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . '
                 WHERE ip_address = ?
                   AND status IN (\'failed\', \'locked_user\')
                   AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
            );
            $stmt->execute([$ip, $windowMinutes]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            Logger::error('LoginAttempt::countRecentFailedByIp — ' . $e->getMessage());
            return 0;
        }
    }

    public function countRecentFailedByEmail(string $email, int $windowMinutes): int
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM ' . self::TABLE . '
                 WHERE email = ?
                   AND status = \'failed\'
                   AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
            );
            $stmt->execute([$email, $windowMinutes]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            Logger::error('LoginAttempt::countRecentFailedByEmail — ' . $e->getMessage());
            return 0;
        }
    }
}
