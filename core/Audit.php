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
        'token', 'remember_token', 'session_id',
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
                'status'      => $data['status'] ?? 'success',
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
        $lower = array_map('strtolower', self::$sensitiveKeys);
        $result = [];
        foreach ($data as $k => $v) {
            if (!in_array(strtolower((string)$k), $lower, true)) {
                $result[$k] = $v;
            }
        }
        return $result;
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
        return parse_url($uri, PHP_URL_PATH) ?: '/';
    }
}
