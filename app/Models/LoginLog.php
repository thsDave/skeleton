<?php

namespace App\Models;

use Core\Model;

class LoginLog extends Model
{
    private const TABLE = 'tbl_login_logs';

    public function record(?int $userId, string $email, string $status, string $message = ''): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . ' (user_id, email, ip_address, user_agent, status, message)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $email,
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $status,
            $message,
        ]);
    }
}
