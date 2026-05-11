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
                  require_existing_user, allow_account_linking,
                  restrict_external_domains, allowed_external_domains)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                  local_login_enabled        = VALUES(local_login_enabled),
                  external_login_enabled     = VALUES(external_login_enabled),
                  allow_auto_user_creation   = VALUES(allow_auto_user_creation),
                  default_role_id            = VALUES(default_role_id),
                  require_existing_user      = VALUES(require_existing_user),
                  allow_account_linking      = VALUES(allow_account_linking),
                  restrict_external_domains  = VALUES(restrict_external_domains),
                  allowed_external_domains   = VALUES(allowed_external_domains)'
            );
            return $stmt->execute([
                (int) ($data['local_login_enabled']       ?? 1),
                (int) ($data['external_login_enabled']    ?? 0),
                (int) ($data['allow_auto_user_creation']  ?? 0),
                !empty($data['default_role_id']) ? (int) $data['default_role_id'] : null,
                (int) ($data['require_existing_user']     ?? 1),
                (int) ($data['allow_account_linking']     ?? 1),
                (int) ($data['restrict_external_domains'] ?? 0),
                !empty($data['allowed_external_domains']) ? (string) $data['allowed_external_domains'] : null,
            ]);
        } catch (\Throwable $e) {
            Logger::error('AuthenticationSettings::save — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns true if $email is allowed under the current domain restriction settings.
     * - Restriction off → always allowed.
     * - Restriction on, no domains configured → always denied (misconfiguration).
     * - Otherwise → exact domain match (case-insensitive, no partial matches).
     *
     * Pass $cachedSettings to avoid a second DB query when the caller already holds them.
     */
    public function isDomainAllowed(string $email, ?array $cachedSettings = null): bool
    {
        $settings = $cachedSettings ?? $this->get();

        if (empty($settings['restrict_external_domains'])) {
            return true;
        }

        $raw = $settings['allowed_external_domains'] ?? '';
        if ($raw === '') {
            return false;
        }

        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return false;
        }

        $emailDomain = strtolower(substr($email, $atPos + 1));
        $allowed = array_filter(
            array_map('trim', preg_split('/[\n\r,]+/', strtolower($raw))),
            fn(string $d) => $d !== ''
        );

        return in_array($emailDomain, array_values($allowed), true);
    }

    private function defaults(): array
    {
        return [
            'id'                        => 1,
            'local_login_enabled'       => 1,
            'external_login_enabled'    => 0,
            'allow_auto_user_creation'  => 0,
            'default_role_id'           => null,
            'require_existing_user'     => 1,
            'allow_account_linking'     => 1,
            'restrict_external_domains' => 0,
            'allowed_external_domains'  => null,
        ];
    }
}
