<?php

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\PasswordPolicy;
use Core\Logger;

class PasswordPolicyService
{
    private ?array $cachedPolicy = null;

    // ─── Policy access ────────────────────────────────────────────────────────

    public function getPolicy(): array
    {
        if ($this->cachedPolicy !== null) {
            return $this->cachedPolicy;
        }
        $this->cachedPolicy = (new PasswordPolicy())->get();
        return $this->cachedPolicy;
    }

    /**
     * Returns the active requirements array for use in views.
     * Falls back to defaults if the table is not yet available.
     */
    public function getRequirements(): array
    {
        try {
            return $this->getPolicy();
        } catch (\Throwable $e) {
            return $this->defaults();
        }
    }

    // ─── Password validation ──────────────────────────────────────────────────

    /**
     * Validate $password against the current policy.
     *
     * $user: optional array with 'email', 'nombres', 'apellidos'
     *        used for contextual checks (prevent email/name in password).
     *
     * Returns ['valid' => bool, 'errors' => string[]]
     */
    public function validate(string $password, ?array $user = null): array
    {
        $errors = [];

        try {
            $policy = $this->getPolicy();
        } catch (\Throwable $e) {
            Logger::error('PasswordPolicyService::validate — could not load policy: ' . $e->getMessage());
            if (strlen($password) < 6) {
                $errors[] = __('password_policy.error_min_length', ['min' => 6]);
            }
            return ['valid' => empty($errors), 'errors' => $errors];
        }

        if (empty($policy['is_enabled'])) {
            if (strlen($password) < 6) {
                $errors[] = __('password_policy.error_min_length', ['min' => 6]);
            }
            return ['valid' => empty($errors), 'errors' => $errors];
        }

        $minLen = (int) ($policy['min_length'] ?? 10);

        if (strlen($password) < $minLen) {
            $errors[] = __('password_policy.error_min_length', ['min' => $minLen]);
        }
        if (!empty($policy['require_uppercase']) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = __('password_policy.error_uppercase');
        }
        if (!empty($policy['require_lowercase']) && !preg_match('/[a-z]/', $password)) {
            $errors[] = __('password_policy.error_lowercase');
        }
        if (!empty($policy['require_number']) && !preg_match('/[0-9]/', $password)) {
            $errors[] = __('password_policy.error_number');
        }
        if (!empty($policy['require_special']) && !preg_match('/[\W_]/', $password)) {
            $errors[] = __('password_policy.error_special');
        }

        if ($user !== null) {
            $lowerPwd = strtolower($password);

            if (!empty($policy['prevent_email_in_password']) && !empty($user['email'])) {
                $localPart = strtolower(explode('@', $user['email'])[0] ?? '');
                if ($localPart !== '' && strlen($localPart) >= 3 && str_contains($lowerPwd, $localPart)) {
                    $errors[] = __('password_policy.error_email');
                }
            }

            if (!empty($policy['prevent_name_in_password'])) {
                $nombres   = strtolower(trim($user['nombres']   ?? ''));
                $apellidos = strtolower(trim($user['apellidos'] ?? ''));
                $nameError = false;
                if (!$nameError && $nombres !== '' && strlen($nombres) >= 3 && str_contains($lowerPwd, $nombres)) {
                    $nameError = true;
                }
                if (!$nameError && $apellidos !== '' && strlen($apellidos) >= 3 && str_contains($lowerPwd, $apellidos)) {
                    $nameError = true;
                }
                if ($nameError) {
                    $errors[] = __('password_policy.error_name');
                }
            }
        }

        if (!empty($policy['prevent_common_passwords'])) {
            $common = $this->loadCommonPasswords();
            if (in_array(strtolower($password), $common, true)) {
                $errors[] = __('password_policy.error_common');
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    // ─── Password history ─────────────────────────────────────────────────────

    /**
     * Returns true if $newPassword matches any of the user's recent password hashes.
     */
    public function isPasswordReused(string $newPassword, int $userId): bool
    {
        try {
            $policy = $this->getPolicy();
            $count  = (int) ($policy['password_history_count'] ?? 0);
            if ($count <= 0) {
                return false;
            }
            $hashes = (new PasswordHistory())->getLastHashes($userId, $count);
            foreach ($hashes as $hash) {
                if (password_verify($newPassword, $hash)) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            Logger::error('PasswordPolicyService::isPasswordReused — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save a hashed password to history, then prune old entries.
     */
    public function saveHistory(int $userId, string $hashedPassword): void
    {
        try {
            $model      = new PasswordHistory();
            $model->add($userId, $hashedPassword);
            $policy     = $this->getPolicy();
            $keepCount  = (int) ($policy['password_history_count'] ?? 3);
            if ($keepCount > 0) {
                $model->pruneOld($userId, $keepCount);
            }
        } catch (\Throwable $e) {
            Logger::error('PasswordPolicyService::saveHistory — ' . $e->getMessage());
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function loadCommonPasswords(): array
    {
        $file = dirname(__DIR__, 2) . '/config/common_passwords.php';
        if (!file_exists($file)) {
            return [];
        }
        $list = require $file;
        return is_array($list) ? array_map('strtolower', $list) : [];
    }

    private function defaults(): array
    {
        return [
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
