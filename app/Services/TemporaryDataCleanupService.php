<?php

namespace App\Services;

use Core\Database;
use PDO;
use Throwable;

// RateLimitService (Etapa 3.5) esta en el mismo namespace App\Services, por
// lo que no requiere "use" — se instancia directamente en cleanupRateLimits().

class TemporaryDataCleanupService
{
    private PDO $db;
    private string $rootPath;

    private const DEFAULT_RETENTION = [
        'password_reset_tokens' => 7,
        'email_change_codes' => 7,
        'revoked_sessions' => 90,
        'login_attempts' => 90,
        'password_histories' => 0,
        'read_notifications' => 90,
        'logs' => 30,
        'temp_files' => 7,
        // Retencion de 1 dia: coincide con el criterio ya implementado en
        // RateLimitService::cleanupExpired() (fallback 86400 segundos), no con
        // la retencion de login_attempts (90 dias), porque las filas de rate
        // limit solo son relevantes durante su ventana activa (minutos/horas),
        // no como historial de seguridad a largo plazo (Etapa 3.5).
        'rate_limits' => 1,
        // Misma retencion que email_change_codes (categoria analoga: codigos
        // de un solo uso de corta duracion) (Etapa 3.5).
        'two_factor_codes' => 7,
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rootPath = dirname(__DIR__, 2);
    }

    public function getSummary(array $retentionDays = []): array
    {
        $retention = $this->normalizeRetention($retentionDays);

        return [
            'password_reset_tokens' => $this->passwordResetSummary($retention['password_reset_tokens']),
            'email_change_codes' => $this->emailChangeSummary($retention['email_change_codes']),
            'revoked_sessions' => $this->revokedSessionsSummary($retention['revoked_sessions']),
            'login_attempts' => $this->loginAttemptsSummary($retention['login_attempts']),
            'password_histories' => $this->passwordHistoriesSummary(),
            'read_notifications' => $this->readNotificationsSummary($retention['read_notifications']),
            'logs' => $this->logsSummary($retention['logs']),
            'temp_files' => $this->tempFilesSummary($retention['temp_files']),
            'rate_limits' => $this->rateLimitsSummary($retention['rate_limits']),
            'two_factor_codes' => $this->twoFactorCodesSummary($retention['two_factor_codes']),
        ];
    }

    public function cleanupSelected(array $items, array $retentionDays = []): array
    {
        $allowed = array_keys(self::DEFAULT_RETENTION);
        $selected = array_values(array_intersect($allowed, array_map('strval', $items)));
        $retention = $this->normalizeRetention($retentionDays);
        $results = [];

        foreach ($selected as $item) {
            try {
                $results[$item] = match ($item) {
                    'password_reset_tokens' => $this->cleanupPasswordResetTokens($retention[$item]),
                    'email_change_codes' => $this->cleanupEmailChangeVerifications($retention[$item]),
                    'revoked_sessions' => $this->cleanupRevokedSessions($retention[$item]),
                    'login_attempts' => $this->cleanupLoginAttempts($retention[$item]),
                    'password_histories' => $this->cleanupPasswordHistories(),
                    'read_notifications' => $this->cleanupReadNotifications($retention[$item]),
                    'logs' => $this->cleanupLogs($retention[$item]),
                    'temp_files' => $this->cleanupTempFiles($retention[$item]),
                    'rate_limits' => $this->cleanupRateLimits($retention[$item]),
                    'two_factor_codes' => $this->cleanupTwoFactorCodes($retention[$item]),
                };
            } catch (Throwable $e) {
                $results[$item] = [
                    'status' => 'failed',
                    'deleted' => 0,
                    'message' => 'No se pudo completar la limpieza de esta categoria.',
                ];
            }
        }

        return $results;
    }

    public function normalizeRetention(array $input): array
    {
        $retention = self::DEFAULT_RETENTION;
        foreach ($retention as $key => $default) {
            if ($key === 'password_histories') {
                continue;
            }
            $value = isset($input[$key]) ? (int)$input[$key] : $default;
            $retention[$key] = max(1, min(3650, $value));
        }
        return $retention;
    }

    public function getLastCleanup(): ?string
    {
        if (!$this->tableExists('tbl_audit_logs')) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT created_at FROM tbl_audit_logs
                 WHERE action = 'maintenance.cleanup_completed'
                 ORDER BY created_at DESC
                 LIMIT 1"
            );
            $stmt->execute();
            $value = $stmt->fetchColumn();
            return $value ? (string)$value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function passwordResetSummary(int $days): array
    {
        if (!$this->tableExists('tbl_password_resets')) {
            return $this->unavailable('password_reset_tokens', $days);
        }

        $count = $this->count(
            'tbl_password_resets',
            '(expires_at < NOW() OR used_at IS NOT NULL OR created_at < DATE_SUB(NOW(), INTERVAL ? DAY))',
            [$days]
        );

        return $this->summaryItem('password_reset_tokens', $days, $count, 'Registros vencidos, usados o antiguos de recuperacion de contrasena.');
    }

    private function emailChangeSummary(int $days): array
    {
        if (!$this->tableExists('tbl_email_change_verifications')) {
            return $this->unavailable('email_change_codes', $days);
        }

        $count = $this->count(
            'tbl_email_change_verifications',
            '(expires_at < NOW() OR used_at IS NOT NULL OR created_at < DATE_SUB(NOW(), INTERVAL ? DAY))',
            [$days]
        );

        return $this->summaryItem('email_change_codes', $days, $count, 'Codigos vencidos, usados o antiguos de cambio de correo.');
    }

    private function revokedSessionsSummary(int $days): array
    {
        if (!$this->tableExists('tbl_user_sessions')) {
            return $this->unavailable('revoked_sessions', $days);
        }

        $count = $this->count(
            'tbl_user_sessions',
            'revoked_at IS NOT NULL AND revoked_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );

        return $this->summaryItem('revoked_sessions', $days, $count, 'Solo sesiones revocadas antiguas. No elimina sesiones activas.');
    }

    private function loginAttemptsSummary(int $days): array
    {
        if (!$this->tableExists('tbl_login_attempts')) {
            return $this->unavailable('login_attempts', $days);
        }

        $column = $this->columnExists('tbl_login_attempts', 'attempted_at') ? 'attempted_at' : 'created_at';
        $count = $this->count(
            'tbl_login_attempts',
            "{$column} < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        return $this->summaryItem('login_attempts', $days, $count, 'Intentos antiguos fuera de la ventana operativa.');
    }

    private function passwordHistoriesSummary(): array
    {
        if (!$this->tableExists('tbl_password_histories')) {
            return $this->unavailable('password_histories', 0);
        }

        $keep = $this->passwordHistoryKeepCount();
        if ($keep <= 0) {
            return $this->summaryItem('password_histories', 0, 0, 'La politica no conserva historial; limpieza manual deshabilitada por seguridad.', false);
        }

        $count = 0;
        foreach ($this->passwordHistoryUserIds() as $userId) {
            $total = $this->count('tbl_password_histories', 'user_id = ?', [$userId]);
            $count += max(0, $total - $keep);
        }

        return $this->summaryItem('password_histories', 0, $count, 'Conserva las ultimas contrasenas requeridas por politica.');
    }

    private function logsSummary(int $days): array
    {
        $files = $this->oldFiles($this->logDirectories(), $days, ['log']);
        return $this->summaryItem('logs', $days, count($files), 'Archivos .log antiguos. No muestra ni registra contenido de logs.', true, 'files');
    }

    private function readNotificationsSummary(int $days): array
    {
        if (!$this->tableExists('tbl_notifications')) {
            return $this->unavailable('read_notifications', $days);
        }

        $where = $this->columnExists('tbl_notifications', 'deleted_at')
            ? 'deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
            : 'read_at IS NOT NULL AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)';
        $count = $this->count(
            'tbl_notifications',
            $where,
            [$days]
        );

        return $this->summaryItem('read_notifications', $days, $count, 'Solo notificaciones eliminadas logicamente antiguas cuando deleted_at existe. No elimina notificaciones visibles del usuario.');
    }

    private function tempFilesSummary(int $days): array
    {
        $files = $this->oldFiles($this->tempDirectories(), $days, null);
        return $this->summaryItem('temp_files', $days, count($files), 'Archivos antiguos en carpetas temporales conocidas.', true, 'files');
    }

    private function rateLimitsSummary(int $days): array
    {
        if (!$this->tableExists('tbl_rate_limits')) {
            return $this->unavailable('rate_limits', $days);
        }

        $count = $this->count(
            'tbl_rate_limits',
            'last_attempt_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND (available_at IS NULL OR available_at < NOW())',
            [$days]
        );

        return $this->summaryItem('rate_limits', $days, $count, 'Registros de control de intentos (rate limit) inactivos, sin bloqueo vigente. No incluye bloqueos activos.');
    }

    private function twoFactorCodesSummary(int $days): array
    {
        if (!$this->tableExists('tbl_two_factor_codes')) {
            return $this->unavailable('two_factor_codes', $days);
        }

        $count = $this->count(
            'tbl_two_factor_codes',
            '(used = 1 OR expires_at < NOW()) AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );

        return $this->summaryItem('two_factor_codes', $days, $count, 'Codigos MFA por correo usados o vencidos, antiguos segun la retencion configurada. No elimina codigos vigentes.');
    }

    private function cleanupPasswordResetTokens(int $days): array
    {
        return $this->deleteRows(
            'password_reset_tokens',
            'tbl_password_resets',
            '(expires_at < NOW() OR used_at IS NOT NULL OR created_at < DATE_SUB(NOW(), INTERVAL ? DAY))',
            [$days]
        );
    }

    private function cleanupEmailChangeVerifications(int $days): array
    {
        return $this->deleteRows(
            'email_change_codes',
            'tbl_email_change_verifications',
            '(expires_at < NOW() OR used_at IS NOT NULL OR created_at < DATE_SUB(NOW(), INTERVAL ? DAY))',
            [$days]
        );
    }

    private function cleanupRevokedSessions(int $days): array
    {
        return $this->deleteRows(
            'revoked_sessions',
            'tbl_user_sessions',
            'revoked_at IS NOT NULL AND revoked_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );
    }

    private function cleanupLoginAttempts(int $days): array
    {
        if (!$this->tableExists('tbl_login_attempts')) {
            return $this->skipped('login_attempts');
        }

        $column = $this->columnExists('tbl_login_attempts', 'attempted_at') ? 'attempted_at' : 'created_at';
        return $this->deleteRows('login_attempts', 'tbl_login_attempts', "{$column} < DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
    }

    private function cleanupPasswordHistories(): array
    {
        if (!$this->tableExists('tbl_password_histories')) {
            return $this->skipped('password_histories');
        }

        $keep = $this->passwordHistoryKeepCount();
        if ($keep <= 0) {
            return ['status' => 'skipped', 'deleted' => 0, 'message' => 'Limpieza omitida: politica sin historial activo.'];
        }

        $deleted = 0;
        foreach ($this->passwordHistoryUserIds() as $userId) {
            $ids = $this->passwordHistoryIdsToDelete($userId, $keep);
            if (!$ids) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("DELETE FROM tbl_password_histories WHERE id IN ({$placeholders})");
            $stmt->execute($ids);
            $deleted += $stmt->rowCount();
        }

        return ['status' => 'success', 'deleted' => $deleted, 'message' => 'Historial antiguo eliminado conservando la politica vigente.'];
    }

    private function cleanupReadNotifications(int $days): array
    {
        $where = $this->columnExists('tbl_notifications', 'deleted_at')
            ? 'deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
            : 'read_at IS NOT NULL AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)';

        return $this->deleteRows(
            'read_notifications',
            'tbl_notifications',
            $where,
            [$days]
        );
    }

    private function cleanupLogs(int $days): array
    {
        return $this->deleteFiles('logs', $this->oldFiles($this->logDirectories(), $days, ['log']));
    }

    private function cleanupTempFiles(int $days): array
    {
        return $this->deleteFiles('temp_files', $this->oldFiles($this->tempDirectories(), $days, null));
    }

    private function cleanupRateLimits(int $days): array
    {
        if (!$this->tableExists('tbl_rate_limits')) {
            return $this->skipped('rate_limits');
        }

        // Reutiliza el criterio ya implementado y probado en RateLimitService
        // (Etapa 3.1): no elimina un registro cuyo available_at siga en el
        // futuro (bloqueo activo), sin importar su antiguedad.
        $deleted = (new RateLimitService())->cleanupExpired($days * 86400);

        return ['status' => 'success', 'deleted' => $deleted, 'message' => 'Limpieza completada. No se eliminan bloqueos activos.'];
    }

    private function cleanupTwoFactorCodes(int $days): array
    {
        return $this->deleteRows(
            'two_factor_codes',
            'tbl_two_factor_codes',
            '(used = 1 OR expires_at < NOW()) AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );
    }

    private function deleteRows(string $key, string $table, string $where, array $params): array
    {
        if (!$this->tableExists($table)) {
            return $this->skipped($key);
        }

        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE {$where}");
        $stmt->execute($params);
        return ['status' => 'success', 'deleted' => $stmt->rowCount(), 'message' => 'Limpieza completada.'];
    }

    private function deleteFiles(string $key, array $files): array
    {
        $deleted = 0;
        foreach ($files as $file) {
            if (!$this->isSafeCleanupFile($file)) {
                continue;
            }
            if (@unlink($file)) {
                $deleted++;
            }
        }
        return ['status' => 'success', 'deleted' => $deleted, 'message' => 'Archivos antiguos eliminados de rutas temporales permitidas.'];
    }

    private function summaryItem(string $key, int $days, int $count, string $description, bool $available = true, string $unit = 'records'): array
    {
        return [
            'key' => $key,
            'label' => __('maintenance.' . $key),
            'available' => $available,
            'retention_days' => $days,
            'count' => $count,
            'unit' => $unit,
            'description' => $description,
            'status' => !$available ? 'info' : ($count > 0 ? 'warning' : 'success'),
        ];
    }

    private function unavailable(string $key, int $days): array
    {
        return $this->summaryItem($key, $days, 0, 'No disponible en esta instalacion.', false);
    }

    private function skipped(string $key): array
    {
        return ['status' => 'skipped', 'deleted' => 0, 'message' => 'No disponible en esta instalacion.'];
    }

    private function normalizeDays(int $days): int
    {
        return max(1, min(3650, $days));
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function count(string $table, string $where, array $params = []): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function passwordHistoryKeepCount(): int
    {
        if (!$this->tableExists('tbl_password_policies')) {
            return 0;
        }

        try {
            $stmt = $this->db->query('SELECT password_history_count FROM tbl_password_policies WHERE id = 1 LIMIT 1');
            return max(0, (int)$stmt->fetchColumn());
        } catch (Throwable) {
            return 0;
        }
    }

    private function passwordHistoryUserIds(): array
    {
        try {
            $stmt = $this->db->query('SELECT DISTINCT user_id FROM tbl_password_histories');
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable) {
            return [];
        }
    }

    private function passwordHistoryIdsToDelete(int $userId, int $keep): array
    {
        $stmt = $this->db->prepare('SELECT id FROM tbl_password_histories WHERE user_id = ? ORDER BY created_at DESC, id DESC');
        $stmt->execute([$userId]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        return array_slice($ids, $keep);
    }

    private function logDirectories(): array
    {
        return [$this->rootPath . '/logs'];
    }

    private function tempDirectories(): array
    {
        return array_values(array_filter([
            $this->rootPath . '/storage/temp',
            $this->rootPath . '/storage/tmp',
            $this->rootPath . '/public/uploads/tmp',
            $this->rootPath . '/public/uploads/temp',
        ], static fn(string $path): bool => is_dir($path)));
    }

    private function oldFiles(array $directories, int $days, ?array $extensions): array
    {
        $threshold = time() - ($this->normalizeDays($days) * 86400);
        $files = [];

        foreach ($directories as $directory) {
            $realDirectory = realpath($directory);
            if (!$realDirectory || !is_dir($realDirectory)) {
                continue;
            }

            foreach (glob($realDirectory . '/*') ?: [] as $file) {
                if (!is_file($file) || filemtime($file) >= $threshold) {
                    continue;
                }

                if (date('Y-m-d', filemtime($file)) === date('Y-m-d')) {
                    continue;
                }

                if ($extensions !== null) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (!in_array($extension, $extensions, true)) {
                        continue;
                    }
                }

                if ($this->isSafeCleanupFile($file)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function isSafeCleanupFile(string $file): bool
    {
        $realFile = realpath($file);
        if (!$realFile || !is_file($realFile)) {
            return false;
        }

        $allowedRoots = array_filter(array_map('realpath', array_merge($this->logDirectories(), $this->tempDirectories())));
        foreach ($allowedRoots as $root) {
            if (str_starts_with($realFile, $root . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
