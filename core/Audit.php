<?php

namespace Core;

/**
 * Servicio centralizado de auditoría.
 * Registra acciones importantes en tbl_audit_logs.
 * Nunca rompe el flujo del sistema si falla.
 */
class Audit
{
    private static array $sensitiveKeys = [
        'password', 'password_confirmation', 'current_password',
        'new_password', 'confirm_password', '_csrf_token', 'csrf_token',
        'password_hash', 'hash', 'token', 'token_hash', 'remember_token',
        'session_id', 'client_secret', 'access_token', 'refresh_token',
        'id_token', 'authorization_code', 'code_hash', 'verification_code',
        'recovery_code', 'mfa_code', 'two_factor_code', 'totp_secret',
        'two_factor_secret', 'two_factor_secret_enc', 'qr_secret',
        'smtp_password', 'mail_password',
    ];

    private static array $allowedStatuses = [
        'success', 'failed', 'denied', 'warning', 'info',
    ];

    private static array $sensitiveKeyPatterns = [
        'secret', 'token', 'password_hash', 'password_enc', 'code_hash',
        'verification_code', 'recovery_code', 'mfa_code', 'two_factor_code',
        'totp_secret', 'two_factor_secret', 'qr_secret',
    ];

    /**
     * Registra una acción en la tabla de auditoría.
     *
     * @param array $data {
     *   module?:      string,
     *   action:       string,
     *   entity?:      string,
     *   entity_id?:   int,
     *   description?: string,
     *   old_values?:  array,
     *   new_values?:  array,
     *   status?:      'success'|'failed'|'denied'|'warning',
     *   user_id?:     int|null   (sobreescribe el de sesión)
     * }
     */
    public static function log(array $data): void
    {
        try {
            $model = new \App\Models\AuditLog();
            $model->create([
                'user_id'     => $data['user_id'] ?? Auth::id(),
                'module'      => $data['module']      ?? null,
                'action'      => $data['action']      ?? 'unknown',
                'entity'      => $data['entity']      ?? null,
                'entity_id'   => isset($data['entity_id']) ? (int)$data['entity_id'] : null,
                'description' => $data['description'] ?? null,
                'old_values'  => isset($data['old_values'])  ? self::encode($data['old_values'])  : null,
                'new_values'  => isset($data['new_values'])  ? self::encode($data['new_values'])  : null,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'  => isset($_SERVER['HTTP_USER_AGENT'])
                                    ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
                                    : null,
                'route'       => self::currentRoute(),
                'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
                'status'      => self::normalizeStatus($data['status'] ?? 'success'),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Audit::log failed: ' . $e->getMessage());
        }
    }

    /**
     * Filtra claves sensibles de un array antes de codificarlo como JSON.
     */
    public static function sanitize(array $data): array
    {
        return self::sanitizeArray($data);
    }

    // ── Privados ────────────────────────────────────────────────────────────

    private static function encode(array $data): string
    {
        $clean = self::sanitize($data);
        return json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function currentRoute(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return self::redactRoute($path);
    }

    private static function sanitizeArray(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return ['_truncated' => true];
        }

        $result = [];
        foreach ($data as $key => $value) {
            $keyString = (string)$key;
            if (self::isSensitiveKey($keyString)) {
                $result[$key] = '[REDACTED]';
                continue;
            }

            if (strtolower($keyString) === 'route' && is_string($value)) {
                $result[$key] = self::redactRoute($value);
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::sanitizeArray($value, $depth + 1);
                continue;
            }

            if (is_object($value)) {
                $result[$key] = '[OBJECT]';
                continue;
            }

            if (is_string($value) && strlen($value) > 1000) {
                $result[$key] = substr($value, 0, 1000) . '... [TRUNCATED]';
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::$sensitiveKeys as $sensitive) {
            if ($key === $sensitive) {
                return true;
            }
        }
        foreach (self::$sensitiveKeyPatterns as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private static function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $status = match ($status) {
            'error', 'fail' => 'failed',
            'pending' => 'info',
            default => $status,
        };

        return in_array($status, self::$allowedStatuses, true) ? $status : 'info';
    }

    private static function redactRoute(string $route): string
    {
        $route = preg_replace('#(/reset-password/)[a-f0-9]{64}#i', '$1[REDACTED]', $route) ?? $route;
        $route = preg_replace('#([?&](?:token|code|state)=)[^&]+#i', '$1[REDACTED]', $route) ?? $route;
        return $route;
    }
}
