<?php

namespace App\Models;

use Core\Crypt;
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

    public function getDecrypted(): array
    {
        $row = $this->get();
        $row['sms_api_secret'] = ($row['sms_api_secret_enc'] !== null && $row['sms_api_secret_enc'] !== '')
            ? Crypt::decrypt($row['sms_api_secret_enc'])
            : '';
        return $row;
    }

    public function update(array $data): bool
    {
        try {
            $current = $this->get();

            // Preserve encrypted secret if no new value provided
            $secretEnc = $current['sms_api_secret_enc'];
            if (!empty($data['sms_api_secret'])) {
                $secretEnc = Crypt::encrypt($data['sms_api_secret']);
            }

            $extraConfig = null;
            if (!empty($data['sms_extra_config'])) {
                $decoded = json_decode($data['sms_extra_config'], true);
                $extraConfig = $decoded !== null ? json_encode($decoded) : null;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::TABLE . '
                    (id, email_enabled, sms_enabled, authenticator_enabled,
                     sms_provider, sms_api_key, sms_api_secret_enc,
                     sms_from, sms_endpoint, sms_extra_config)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    email_enabled         = VALUES(email_enabled),
                    sms_enabled           = VALUES(sms_enabled),
                    authenticator_enabled = VALUES(authenticator_enabled),
                    sms_provider          = VALUES(sms_provider),
                    sms_api_key           = VALUES(sms_api_key),
                    sms_api_secret_enc    = VALUES(sms_api_secret_enc),
                    sms_from              = VALUES(sms_from),
                    sms_endpoint          = VALUES(sms_endpoint),
                    sms_extra_config      = VALUES(sms_extra_config),
                    updated_at            = NOW()'
            );

            return $stmt->execute([
                (int) ($data['email_enabled']         ?? 0),
                (int) ($data['sms_enabled']           ?? 0),
                (int) ($data['authenticator_enabled'] ?? 0),
                $data['sms_provider'] ?: null,
                $data['sms_api_key']  ?: null,
                $secretEnc            ?: null,
                $data['sms_from']     ?: null,
                $data['sms_endpoint'] ?: null,
                $extraConfig,
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
            'sms_provider'          => null,
            'sms_api_key'           => null,
            'sms_api_secret_enc'    => null,
            'sms_from'              => null,
            'sms_endpoint'          => null,
            'sms_extra_config'      => null,
            'created_at'            => null,
            'updated_at'            => null,
        ];
    }
}
