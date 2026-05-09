-- ============================================================
-- 012 – Autenticación: intentos fallidos y desbloqueo de usuarios
-- Importar en phpMyAdmin después de la migración 011.
-- Todos los CREATE/INSERT usan IF NOT EXISTS / IGNORE: seguro ejecutar más de una vez.
-- ============================================================

-- 1. Tabla de configuración de seguridad en login
CREATE TABLE IF NOT EXISTS `tbl_login_security_settings` (
    `id`                              INT         NOT NULL AUTO_INCREMENT,
    `failed_login_protection_enabled` TINYINT(1)  NOT NULL DEFAULT 1,
    `max_failed_attempts_user`        INT         NOT NULL DEFAULT 5,
    `user_attempt_window_minutes`     INT         NOT NULL DEFAULT 15,
    `user_lockout_minutes`            INT         NOT NULL DEFAULT 15,
    `ip_protection_enabled`           TINYINT(1)  NOT NULL DEFAULT 1,
    `max_failed_attempts_ip`          INT         NOT NULL DEFAULT 20,
    `ip_attempt_window_minutes`       INT         NOT NULL DEFAULT 15,
    `ip_lockout_minutes`              INT         NOT NULL DEFAULT 30,
    `created_at`                      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                      TIMESTAMP   NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Registro inicial con valores por defecto (id = 1)
INSERT IGNORE INTO `tbl_login_security_settings`
    (`id`, `failed_login_protection_enabled`, `max_failed_attempts_user`,
     `user_attempt_window_minutes`, `user_lockout_minutes`,
     `ip_protection_enabled`, `max_failed_attempts_ip`,
     `ip_attempt_window_minutes`, `ip_lockout_minutes`)
VALUES (1, 1, 5, 15, 15, 1, 20, 15, 30);

-- 3. Tabla de registro de intentos de inicio de sesión
CREATE TABLE IF NOT EXISTS `tbl_login_attempts` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `user_id`        INT          NULL DEFAULT NULL,
    `email`          VARCHAR(150) NULL DEFAULT NULL,
    `ip_address`     VARCHAR(45)  NULL DEFAULT NULL,
    `user_agent`     VARCHAR(255) NULL DEFAULT NULL,
    `status`         VARCHAR(50)  NOT NULL,
    `failure_reason` VARCHAR(100) NULL DEFAULT NULL,
    `attempted_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_la_user_id`     (`user_id`),
    INDEX `idx_la_email`       (`email`),
    INDEX `idx_la_ip_address`  (`ip_address`),
    INDEX `idx_la_status`      (`status`),
    INDEX `idx_la_attempted_at`(`attempted_at`),
    CONSTRAINT `fk_la_user`
        FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Agregar last_failed_login_at a tbl_users si no existe
ALTER TABLE `tbl_users`
    ADD COLUMN IF NOT EXISTS `last_failed_login_at` DATETIME NULL DEFAULT NULL;

-- 5. Permiso users.unlock
INSERT IGNORE INTO `tbl_permissions`
    (`module_id`, `name`, `slug`, `description`)
VALUES
    ((SELECT id FROM `tbl_modules` WHERE slug = 'users' LIMIT 1),
     'Desbloquear Usuario',
     'users.unlock',
     'Permite desbloquear cuentas de usuario bloqueadas por intentos fallidos');

-- 6. Asignar permiso users.unlock al rol Administrador (role_id = 1)
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `tbl_permissions` WHERE slug = 'users.unlock';
