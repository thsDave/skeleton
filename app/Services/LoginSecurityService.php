<?php

namespace App\Services;

use App\Models\LoginSecuritySetting;
use App\Models\LoginAttempt;
use Core\Logger;

class LoginSecurityService
{
    private LoginSecuritySetting $settingsModel;
    private LoginAttempt         $attemptsModel;
    private array                $settings;

    public function __construct()
    {
        $this->settingsModel = new LoginSecuritySetting();
        $this->attemptsModel = new LoginAttempt();
        $this->settings      = $this->settingsModel->get();
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function isIpBlocked(string $ip): bool
    {
        if (empty($this->settings['ip_protection_enabled'])) {
            return false;
        }
        try {
            $count = $this->attemptsModel->countRecentFailedByIp(
                $ip,
                (int) $this->settings['ip_attempt_window_minutes']
            );
            return $count >= (int) $this->settings['max_failed_attempts_ip'];
        } catch (\Throwable $e) {
            Logger::error('LoginSecurityService::isIpBlocked — ' . $e->getMessage());
            return false;
        }
    }

    public function recordAttempt(array $data): void
    {
        try {
            $this->attemptsModel->record([
                'user_id'        => $data['user_id']        ?? null,
                'email'          => $data['email']          ?? null,
                'ip_address'     => $data['ip_address']     ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                'user_agent'     => $data['user_agent']     ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                'status'         => $data['status'],
                'failure_reason' => $data['failure_reason'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Logger::error('LoginSecurityService::recordAttempt — ' . $e->getMessage());
        }
    }

    public function getUserLockoutMinutes(): int
    {
        return (int) ($this->settings['user_lockout_minutes'] ?? 15);
    }

    public function getMaxUserAttempts(): int
    {
        return (int) ($this->settings['max_failed_attempts_user'] ?? 5);
    }

    public function isUserProtectionEnabled(): bool
    {
        return !empty($this->settings['failed_login_protection_enabled']);
    }
}
