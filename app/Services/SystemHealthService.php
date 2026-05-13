<?php

namespace App\Services;

use Core\Database;
use PDO;
use Throwable;

class SystemHealthService
{
    private PDO $db;
    private string $rootPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rootPath = dirname(__DIR__, 2);
    }

    public function getReport(): array
    {
        $sections = [
            'environment' => [
                'title' => __('system_health.environment'),
                'checks' => $this->environmentChecks(),
            ],
            'database' => [
                'title' => __('system_health.database'),
                'checks' => $this->databaseChecks(),
            ],
            'files' => [
                'title' => __('system_health.files_permissions'),
                'checks' => $this->fileChecks(),
            ],
            'security' => [
                'title' => __('system_health.security'),
                'checks' => $this->securityChecks(),
            ],
            'smtp' => [
                'title' => __('system_health.smtp'),
                'checks' => $this->smtpChecks(),
            ],
            'authentication' => [
                'title' => __('system_health.authentication'),
                'checks' => $this->authenticationChecks(),
            ],
            'oauth' => [
                'title' => __('system_health.oauth_providers'),
                'checks' => $this->oauthChecks(),
            ],
            'maintenance' => [
                'title' => __('system_health.maintenance'),
                'checks' => $this->maintenanceChecks(),
            ],
            'logs' => [
                'title' => __('system_health.logs'),
                'checks' => $this->logChecks(),
            ],
        ];

        return [
            'sections' => $sections,
            'summary' => $this->summarize($sections),
        ];
    }

    private function environmentChecks(): array
    {
        $checks = [];
        $phpVersion = PHP_VERSION;
        $checks[] = $this->check(
            'PHP',
            version_compare($phpVersion, '8.1.0', '>=') ? 'success' : 'danger',
            $phpVersion,
            version_compare($phpVersion, '8.1.0', '>=') ? 'Version compatible.' : 'Se recomienda PHP 8.1 o superior.'
        );

        $extensions = [
            'pdo' => true,
            'pdo_mysql' => true,
            'mbstring' => true,
            'openssl' => true,
            'fileinfo' => true,
            'json' => true,
            'curl' => true,
            'zip' => true,
            'gd' => false,
            'intl' => false,
        ];
        foreach ($extensions as $extension => $required) {
            $loaded = extension_loaded($extension);
            $checks[] = $this->check(
                'PHP ext ' . $extension,
                $loaded ? 'success' : ($required ? 'danger' : 'warning'),
                $loaded ? __('system_health.available') : __('system_health.not_available'),
                $loaded ? 'Extension cargada.' : ($required ? 'Extension requerida no disponible.' : 'Extension opcional no disponible.')
            );
        }

        $timezone = date_default_timezone_get();
        $checks[] = $this->check(
            'Timezone PHP',
            $timezone !== '' ? 'success' : 'warning',
            $timezone ?: __('system_health.not_configured'),
            $timezone !== '' ? 'Zona horaria configurada.' : 'Configura APP_TIMEZONE o date.timezone.'
        );

        foreach (['upload_max_filesize', 'post_max_size', 'memory_limit', 'max_execution_time'] as $iniKey) {
            $value = (string)ini_get($iniKey);
            $checks[] = $this->check(
                $iniKey,
                $this->isLowPhpLimit($iniKey, $value) ? 'warning' : 'success',
                $value,
                $this->isLowPhpLimit($iniKey, $value)
                    ? 'Valor bajo para cargas, exportaciones o procesos largos.'
                    : 'Valor operativo aceptable.'
            );
        }

        return $checks;
    }

    private function databaseChecks(): array
    {
        $checks = [];
        try {
            $version = (string)$this->db->query('SELECT VERSION()')->fetchColumn();
            $checks[] = $this->check('Conexion MySQL', 'success', 'Conectado', 'PDO conecto correctamente.');
            $checks[] = $this->check('Version MySQL/MariaDB', 'info', $version, 'Version reportada por el servidor.');
        } catch (Throwable $e) {
            $checks[] = $this->check('Conexion MySQL', 'danger', 'No conectado', 'No se pudo consultar la base de datos.');
            return $checks;
        }

        $criticalTables = [
            'tbl_users',
            'tbl_roles',
            'tbl_permissions',
            'tbl_role_permissions',
            'tbl_audit_logs',
            'tbl_smtp_settings',
            'tbl_mfa_settings',
            'tbl_login_security_settings',
            'tbl_authentication_settings',
            'tbl_external_auth_providers',
            'tbl_password_policies',
            'tbl_password_histories',
            'tbl_user_sessions',
            'tbl_user_manuals',
            'tbl_email_change_verifications',
        ];
        foreach ($criticalTables as $table) {
            $exists = $this->tableExists($table);
            $checks[] = $this->check(
                $table,
                $exists ? 'success' : 'danger',
                $exists ? __('system_health.available') : __('system_health.not_available'),
                $exists ? 'Tabla requerida encontrada.' : 'Tabla requerida no encontrada.'
            );
        }

        $legacyStatusExists = $this->columnExists('tbl_users', 'status');
        $checks[] = $this->check(
            'Estado de usuario normalizado',
            $legacyStatusExists ? 'warning' : 'success',
            $legacyStatusExists ? 'Campo legacy detectado' : 'status_id',
            $legacyStatusExists
                ? 'tbl_users.status aun existe; ejecutar la migracion correctiva para dejar status_id como fuente oficial.'
                : 'tbl_users.status_id es la fuente oficial del estado de usuario.'
        );

        $invalidUserStatuses = $this->scalar(
            'SELECT COUNT(*) FROM tbl_users u
             LEFT JOIN tbl_statuses s ON s.id = u.status_id
             WHERE s.id IS NULL'
        );
        $checks[] = $this->check(
            'Usuarios con status_id invalido',
            $invalidUserStatuses === 0 ? 'success' : 'danger',
            (string)$invalidUserStatuses,
            $invalidUserStatuses === 0
                ? 'Todos los usuarios apuntan a un estado valido.'
                : 'Hay usuarios con estado inexistente; corregir antes de operar autenticacion.'
        );

        $baseStatuses = $this->scalar(
            "SELECT COUNT(*) FROM tbl_statuses WHERE slug IN ('active', 'inactive', 'blocked')"
        );
        $checks[] = $this->check(
            'Estados base de usuario',
            $baseStatuses === 3 ? 'success' : 'danger',
            (string)$baseStatuses . '/3',
            $baseStatuses === 3
                ? 'Estados active, inactive y blocked disponibles.'
                : 'Faltan estados base requeridos por usuarios y autenticacion.'
        );

        $twoFactorUserFkExists = $this->foreignKeyExists('tbl_two_factor_codes', 'fk_two_factor_codes_user');
        $checks[] = $this->check(
            'FK MFA usuario',
            $twoFactorUserFkExists ? 'success' : 'warning',
            $twoFactorUserFkExists ? __('system_health.available') : __('system_health.not_available'),
            'tbl_two_factor_codes.user_id debe referenciar tbl_users.id con ON DELETE CASCADE.'
        );

        $defaultRoleFkExists = $this->foreignKeyExists('tbl_authentication_settings', 'fk_authentication_settings_default_role');
        $checks[] = $this->check(
            'FK rol por defecto OAuth',
            $defaultRoleFkExists ? 'success' : 'warning',
            $defaultRoleFkExists ? __('system_health.available') : __('system_health.not_available'),
            'tbl_authentication_settings.default_role_id debe referenciar tbl_roles.id con ON DELETE SET NULL.'
        );

        $adminCount = $this->scalar(
            "SELECT COUNT(*) FROM tbl_users u
             JOIN tbl_roles r ON r.id = u.role_id
             WHERE r.slug = 'administrator'"
        );
        $checks[] = $this->check(
            'Usuario administrador',
            $adminCount > 0 ? 'success' : 'danger',
            (string)$adminCount,
            $adminCount > 0 ? 'Existe al menos un administrador.' : 'No se encontro usuario con rol Administrador.'
        );

        foreach (['users.view', 'audit_logs.view', 'system_health.view', 'security_smtp.view', 'security_sessions.view_active', 'appearance.view'] as $permission) {
            $exists = $this->scalar('SELECT COUNT(*) FROM tbl_permissions WHERE slug = ?', [$permission]) > 0;
            $checks[] = $this->check(
                'Permiso ' . $permission,
                $exists ? 'success' : 'warning',
                $exists ? __('system_health.available') : __('system_health.not_available'),
                $exists ? 'Permiso registrado.' : 'Permiso base no encontrado.'
            );
        }

        return $checks;
    }

    private function fileChecks(): array
    {
        $checks = [];
        $envPath = $this->rootPath . '/.env';
        $checks[] = $this->check('.env', is_file($envPath) ? 'success' : 'danger', is_file($envPath) ? __('system_health.available') : __('system_health.not_available'), 'Archivo de entorno requerido para configurar la aplicacion.');

        $appKey = (string)env('APP_KEY', '');
        $checks[] = $this->check('APP_KEY', $appKey !== '' ? 'success' : 'danger', $appKey !== '' ? __('system_health.hidden_for_security') : __('system_health.not_configured'), 'Se valida presencia sin mostrar el valor.');

        $appUrl = (string)env('APP_URL', '');
        $checks[] = $this->check('APP_URL', filter_var($appUrl, FILTER_VALIDATE_URL) ? 'success' : 'warning', $appUrl !== '' ? $appUrl : __('system_health.not_configured'), 'Debe apuntar a la URL publica del sistema.');

        $debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
        $checks[] = $this->check('APP_DEBUG', $debug ? 'warning' : 'success', $debug ? 'true' : 'false', $debug ? 'No recomendado en produccion.' : 'Modo debug desactivado.');

        $paths = [
            'public/uploads' => $this->rootPath . '/public/uploads',
            'public/uploads/profiles' => $this->rootPath . '/public/uploads/profiles',
            'public/uploads/manuals' => $this->rootPath . '/public/uploads/manuals',
            'public/uploads/appearance' => $this->rootPath . '/public/uploads/appearance',
            'logs' => $this->rootPath . '/logs',
        ];
        foreach ($paths as $label => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $checks[] = $this->check(
                $label,
                $writable ? 'success' : ($exists ? 'warning' : 'danger'),
                $writable ? __('system_health.writable') : ($exists ? __('system_health.not_writable') : __('system_health.not_available')),
                $writable ? 'Carpeta disponible para escritura.' : 'Revisar permisos de carpeta.'
            );
        }

        foreach ([
            'Chart.js local' => '/public/assets/js/plugins/chart.umd.min.js',
            'Dashboard JS' => '/public/assets/js/dashboard.js',
            'Avatar fallback' => '/public/assets/images/user/avatar-1.jpg',
        ] as $label => $relative) {
            $exists = is_file($this->rootPath . $relative);
            $checks[] = $this->check($label, $exists ? 'success' : 'warning', $exists ? __('system_health.available') : __('system_health.not_available'), $exists ? 'Asset encontrado.' : 'Asset no encontrado; revisar public/assets.');
        }

        return $checks;
    }

    private function securityChecks(): array
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

        return [
            $this->check('HTTPS', $isHttps ? 'success' : 'warning', $isHttps ? 'Activo' : 'No detectado', $isHttps ? 'La solicitud actual usa HTTPS.' : 'En local puede ser normal; en produccion debe usarse HTTPS.'),
            $this->check('APP_DEBUG', $debug ? 'warning' : 'success', $debug ? 'true' : 'false', $debug ? 'No recomendado en produccion.' : 'Debug desactivado.'),
            $this->check('session.cookie_httponly', ini_get('session.cookie_httponly') ? 'success' : 'warning', (string)ini_get('session.cookie_httponly'), 'Debe estar activo para reducir acceso JS a cookies.'),
            $this->check('session.cookie_secure', $isHttps && !ini_get('session.cookie_secure') ? 'warning' : 'info', (string)ini_get('session.cookie_secure'), 'Debe estar activo cuando se usa HTTPS en produccion.'),
            $this->check('session.use_strict_mode', ini_get('session.use_strict_mode') ? 'success' : 'warning', (string)ini_get('session.use_strict_mode'), 'Ayuda a prevenir fijacion de sesion.'),
            $this->check('UploadService', class_exists(UploadService::class) ? 'success' : 'danger', class_exists(UploadService::class) ? __('system_health.available') : __('system_health.not_available'), 'Servicio central de carga de archivos.'),
            $this->check('Autorizacion backend', class_exists(\Core\Auth::class) && method_exists(\Core\Auth::class, 'requirePermission') ? 'success' : 'warning', 'requirePermission', 'Validacion de permisos disponible en backend.'),
        ];
    }

    private function smtpChecks(): array
    {
        $row = $this->row('SELECT * FROM tbl_smtp_settings WHERE id = 1 LIMIT 1');
        if (!$row) {
            return [$this->check('SMTP', 'warning', __('system_health.not_configured'), 'No hay configuracion SMTP guardada.')];
        }

        return [
            $this->check('SMTP host', !empty($row['host']) ? 'success' : 'warning', !empty($row['host']) ? __('system_health.configured') : __('system_health.not_configured'), 'Host SMTP.'),
            $this->check('SMTP puerto', !empty($row['port']) ? 'success' : 'warning', (string)($row['port'] ?? __('system_health.not_configured')), 'Puerto SMTP.'),
            $this->check('SMTP usuario', !empty($row['username']) ? 'success' : 'warning', !empty($row['username']) ? __('system_health.configured') : __('system_health.not_configured'), 'Usuario SMTP.'),
            $this->check('SMTP password', !empty($row['password_enc']) ? 'success' : 'warning', !empty($row['password_enc']) ? __('system_health.hidden_for_security') : __('system_health.not_configured'), 'Se valida presencia sin mostrar secreto.'),
            $this->check('SMTP remitente', !empty($row['from_address']) ? 'success' : 'warning', !empty($row['from_address']) ? $row['from_address'] : __('system_health.not_configured'), 'Correo remitente.'),
            $this->check('SMTP verificado', (int)($row['is_verified'] ?? 0) === 1 ? 'success' : 'warning', (int)($row['is_verified'] ?? 0) === 1 ? 'Verificado' : 'Sin verificar', 'Ejecutar prueba desde Seguridad > SMTP si esta pendiente.'),
        ];
    }

    private function authenticationChecks(): array
    {
        $auth = $this->row('SELECT * FROM tbl_authentication_settings WHERE id = 1 LIMIT 1') ?: [];
        $mfa = $this->row('SELECT * FROM tbl_mfa_settings WHERE id = 1 LIMIT 1') ?: [];
        $policy = $this->row('SELECT * FROM tbl_password_policies WHERE id = 1 LIMIT 1') ?: [];
        $loginSecurity = $this->row('SELECT * FROM tbl_login_security_settings WHERE id = 1 LIMIT 1') ?: [];
        $smtpVerified = $this->scalar('SELECT COUNT(*) FROM tbl_smtp_settings WHERE id = 1 AND is_verified = 1') > 0;
        $verifiedProviders = $this->scalar("SELECT COUNT(*) FROM tbl_external_auth_providers WHERE is_enabled = 1 AND is_verified = 1 AND client_id IS NOT NULL AND client_id <> '' AND client_secret IS NOT NULL AND client_secret <> '' AND redirect_uri IS NOT NULL AND redirect_uri <> ''");

        $externalEnabled = (int)($auth['external_login_enabled'] ?? 0) === 1;
        $domainRestriction = (int)($auth['restrict_external_domains'] ?? 0) === 1;
        $emailMfa = (int)($mfa['email_enabled'] ?? 0) === 1;

        return [
            $this->check('Login local', (int)($auth['local_login_enabled'] ?? 1) === 1 ? 'success' : 'warning', (int)($auth['local_login_enabled'] ?? 1) === 1 ? 'Activo' : 'Inactivo', 'Estado del login por correo y contrasena.'),
            $this->check('Login externo', $externalEnabled ? ($verifiedProviders > 0 ? 'success' : 'danger') : 'info', $externalEnabled ? 'Activo' : 'Inactivo', $externalEnabled && $verifiedProviders === 0 ? 'Login externo activo sin proveedores verificados.' : 'Estado de autenticacion externa.'),
            $this->check('Restriccion por dominio', $domainRestriction ? (!empty($auth['allowed_external_domains']) ? 'success' : 'warning') : 'info', $domainRestriction ? 'Activa' : 'Inactiva', $domainRestriction ? 'Requiere dominios autorizados configurados.' : 'No limita dominios externos.'),
            $this->check('MFA por correo', $emailMfa ? ($smtpVerified ? 'success' : 'danger') : 'info', $emailMfa ? 'Activo' : 'Inactivo', $emailMfa && !$smtpVerified ? 'MFA por correo requiere SMTP verificado.' : 'Estado de MFA por correo.'),
            $this->check('MFA por app', (int)($mfa['authenticator_enabled'] ?? 0) === 1 ? 'success' : 'info', (int)($mfa['authenticator_enabled'] ?? 0) === 1 ? 'Activo' : 'Inactivo', 'No requiere SMTP.'),
            $this->check('Politica de contrasenas', (int)($policy['is_enabled'] ?? 0) === 1 ? 'success' : 'warning', (int)($policy['is_enabled'] ?? 0) === 1 ? 'Activa' : 'Inactiva', 'Controla complejidad e historial.'),
            $this->check('Intentos fallidos', (int)($loginSecurity['failed_login_protection_enabled'] ?? 0) === 1 ? 'success' : 'warning', (int)($loginSecurity['failed_login_protection_enabled'] ?? 0) === 1 ? 'Activo' : 'Inactivo', 'Proteccion contra fuerza bruta.'),
            $this->check('Sesiones activas', $this->tableExists('tbl_user_sessions') ? 'success' : 'danger', $this->tableExists('tbl_user_sessions') ? __('system_health.available') : __('system_health.not_available'), 'Tabla de sesiones activas.'),
        ];
    }

    private function oauthChecks(): array
    {
        if (!$this->tableExists('tbl_external_auth_providers')) {
            return [$this->check('Proveedores externos', 'warning', __('system_health.not_available'), 'Tabla de proveedores no encontrada.')];
        }

        $providers = $this->rows('SELECT name, slug, client_id, client_secret, redirect_uri, is_enabled, is_verified FROM tbl_external_auth_providers ORDER BY id ASC');
        if (!$providers) {
            return [$this->check('Proveedores externos', 'info', '0', 'No hay proveedores registrados.')];
        }

        $checks = [];
        foreach ($providers as $provider) {
            $label = (string)($provider['name'] ?: $provider['slug']);
            $enabled = (int)($provider['is_enabled'] ?? 0) === 1;
            $ready = !empty($provider['client_id']) && !empty($provider['client_secret']) && !empty($provider['redirect_uri']);
            $verified = (int)($provider['is_verified'] ?? 0) === 1;
            $checks[] = $this->check(
                $label,
                $enabled ? ($ready && $verified ? 'success' : 'warning') : 'info',
                $enabled ? 'Activo' : 'Inactivo',
                'Client ID: ' . (!empty($provider['client_id']) ? 'Si' : 'No')
                . ' | Client Secret: ' . (!empty($provider['client_secret']) ? __('system_health.hidden_for_security') : 'No')
                . ' | Redirect URI: ' . (!empty($provider['redirect_uri']) ? 'Si' : 'No')
                . ' | Verificado: ' . ($verified ? 'Si' : 'No')
            );
        }

        return $checks;
    }

    private function maintenanceChecks(): array
    {
        return [
            $this->countCheck('Tokens de recuperacion vencidos', 'tbl_password_resets', 'expires_at < NOW()', 'warning'),
            $this->countCheck('Codigos de cambio de correo vencidos', 'tbl_email_change_verifications', 'expires_at < NOW() AND used_at IS NULL', 'warning'),
            $this->countCheck('Sesiones revocadas', 'tbl_user_sessions', 'revoked_at IS NOT NULL', 'info'),
            $this->countCheck('Intentos fallidos registrados', 'tbl_login_attempts', '1=1', 'info'),
            $this->countCheck('Registros de auditoria', 'tbl_audit_logs', '1=1', 'info', 50000),
            $this->manualsMaintenanceCheck(),
        ];
    }

    private function manualsMaintenanceCheck(): array
    {
        if (!$this->tableExists('tbl_user_manuals')) {
            return $this->check('Manuales inactivos/eliminados', 'warning', __('system_health.not_available'), 'Tabla no encontrada.');
        }

        $where = $this->columnExists('tbl_user_manuals', 'deleted_at')
            ? '(status_id <> 1 OR deleted_at IS NOT NULL)'
            : 'status_id <> 1';

        $count = $this->scalar("SELECT COUNT(*) FROM tbl_user_manuals WHERE {$where}");
        return $this->check('Manuales inactivos/eliminados', 'info', (string)$count, 'Dato informativo; el panel no realiza limpieza automatica.');
    }

    private function logChecks(): array
    {
        $checks = [];
        $logDir = $this->rootPath . '/logs';
        $checks[] = $this->check('Carpeta logs', is_dir($logDir) ? (is_writable($logDir) ? 'success' : 'warning') : 'danger', is_dir($logDir) ? (is_writable($logDir) ? __('system_health.writable') : __('system_health.not_writable')) : __('system_health.not_available'), 'Carpeta para logs tecnicos.');

        $logFiles = glob($logDir . '/*.log') ?: [];
        $latest = null;
        foreach ($logFiles as $file) {
            if ($latest === null || filemtime($file) > filemtime($latest)) {
                $latest = $file;
            }
        }
        $checks[] = $this->check('Ultimo log modificado', $latest ? 'info' : 'warning', $latest ? basename($latest) . ' - ' . date('Y-m-d H:i:s', filemtime($latest)) : __('system_health.not_available'), 'No se muestra contenido del log.');
        $size = $latest ? filesize($latest) : 0;
        $checks[] = $this->check('Tamano ultimo log', $size > 10 * 1024 * 1024 ? 'warning' : 'info', $latest ? $this->formatBytes((int)$size) : '0 B', $size > 10 * 1024 * 1024 ? 'Log grande; revisar rotacion.' : 'Tamano dentro de rango operativo.');

        return $checks;
    }

    private function countCheck(string $name, string $table, string $where, string $defaultStatus, int $warningThreshold = 0): array
    {
        if (!$this->tableExists($table)) {
            return $this->check($name, 'warning', __('system_health.not_available'), 'Tabla no encontrada.');
        }

        $count = $this->scalar("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        $status = $warningThreshold > 0 && $count > $warningThreshold ? 'warning' : $defaultStatus;
        return $this->check($name, $status, (string)$count, 'Dato informativo; el panel no realiza limpieza automatica.');
    }

    private function summarize(array $sections): array
    {
        $summary = ['success' => 0, 'warning' => 0, 'danger' => 0, 'info' => 0, 'total' => 0];
        foreach ($sections as $section) {
            foreach ($section['checks'] as $check) {
                $status = $check['status'] ?? 'info';
                $summary[$status] = ($summary[$status] ?? 0) + 1;
                $summary['total']++;
            }
        }
        return $summary;
    }

    private function check(string $name, string $status, string $value, string $message): array
    {
        return compact('name', 'status', 'value', 'message');
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                   AND CONSTRAINT_TYPE = ?'
            );
            $stmt->execute([$table, $constraint, 'FOREIGN KEY']);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function scalar(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function row(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function rows(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function isLowPhpLimit(string $key, string $value): bool
    {
        if ($key === 'max_execution_time') {
            return (int)$value > 0 && (int)$value < 30;
        }
        $bytes = $this->toBytes($value);
        return match ($key) {
            'upload_max_filesize', 'post_max_size' => $bytes > 0 && $bytes < 10 * 1024 * 1024,
            'memory_limit' => $bytes > 0 && $bytes < 128 * 1024 * 1024,
            default => false,
        };
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return -1;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        return match ($unit) {
            'g' => (int)($number * 1024 * 1024 * 1024),
            'm' => (int)($number * 1024 * 1024),
            'k' => (int)($number * 1024),
            default => (int)$number,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
