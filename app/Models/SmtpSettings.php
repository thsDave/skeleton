<?php

namespace App\Models;

use Core\Crypt;
use Core\Model;

class SmtpSettings extends Model
{
    private const TABLE = 'tbl_smtp_settings';

    public function get(): array
    {
        try {
            $stmt = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = 1 LIMIT 1');
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (\PDOException $e) {
            error_log('SmtpSettings::get() error: ' . $e->getMessage());
        }
        return $this->defaults();
    }

    public function getDecrypted(): array
    {
        $row = $this->get();
        $row['password'] = ($row['password_enc'] !== '') ? Crypt::decrypt($row['password_enc']) : '';
        return $row;
    }

    public function update(array $data): bool
    {
        try {
            $enc = isset($data['password']) && $data['password'] !== ''
                ? Crypt::encrypt($data['password'])
                : $this->get()['password_enc'];

            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                    (id, host, port, username, password_enc, encryption, from_address, from_name)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    host         = VALUES(host),
                    port         = VALUES(port),
                    username     = VALUES(username),
                    password_enc = VALUES(password_enc),
                    encryption   = VALUES(encryption),
                    from_address = VALUES(from_address),
                    from_name    = VALUES(from_name),
                    is_verified  = 0'
            );
            return $stmt->execute([
                $data['host'],
                (int) $data['port'],
                $data['username'],
                $enc,
                $data['encryption'],
                $data['from_address'],
                $data['from_name'],
            ]);
        } catch (\PDOException $e) {
            error_log('SmtpSettings::update() error: ' . $e->getMessage());
            return false;
        }
    }

    public function markTested(bool $success, string $message): bool
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE ' . self::TABLE . '
                 SET is_verified = ?, last_tested_at = NOW(), last_test_status = ?
                 WHERE id = 1'
            );
            return $stmt->execute([(int) $success, $message]);
        } catch (\PDOException $e) {
            error_log('SmtpSettings::markTested() error: ' . $e->getMessage());
            return false;
        }
    }

    private function defaults(): array
    {
        return [
            'id'               => 1,
            'host'             => '',
            'port'             => 587,
            'username'         => '',
            'password_enc'     => '',
            'encryption'       => 'tls',
            'from_address'     => '',
            'from_name'        => '',
            'is_verified'      => 0,
            'last_tested_at'   => null,
            'last_test_status' => null,
        ];
    }
}
