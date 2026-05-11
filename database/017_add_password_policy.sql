-- ============================================================
-- 017 – Módulo Política de Contraseñas
-- Importar en phpMyAdmin después de la migración 016.
-- Todos los INSERT usan IGNORE: seguro ejecutar más de una vez.
-- ============================================================

-- 1. Tabla de política de contraseñas
CREATE TABLE IF NOT EXISTS `tbl_password_policies` (
    `id`                        INT          NOT NULL AUTO_INCREMENT,
    `is_enabled`                TINYINT(1)   NOT NULL DEFAULT 1,
    `min_length`                INT          NOT NULL DEFAULT 10,
    `require_uppercase`         TINYINT(1)   NOT NULL DEFAULT 1,
    `require_lowercase`         TINYINT(1)   NOT NULL DEFAULT 1,
    `require_number`            TINYINT(1)   NOT NULL DEFAULT 1,
    `require_special`           TINYINT(1)   NOT NULL DEFAULT 1,
    `prevent_email_in_password` TINYINT(1)   NOT NULL DEFAULT 1,
    `prevent_name_in_password`  TINYINT(1)   NOT NULL DEFAULT 1,
    `prevent_common_passwords`  TINYINT(1)   NOT NULL DEFAULT 1,
    `password_history_count`    INT          NOT NULL DEFAULT 3,
    `password_expiration_days`  INT          NOT NULL DEFAULT 0,
    `created_at`                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro inicial
INSERT IGNORE INTO `tbl_password_policies`
    (`id`, `is_enabled`, `min_length`, `require_uppercase`, `require_lowercase`,
     `require_number`, `require_special`, `prevent_email_in_password`,
     `prevent_name_in_password`, `prevent_common_passwords`,
     `password_history_count`, `password_expiration_days`)
VALUES
    (1, 1, 10, 1, 1, 1, 1, 1, 1, 1, 3, 0);

-- 2. Tabla de historial de contraseñas
CREATE TABLE IF NOT EXISTS `tbl_password_histories` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `user_id`       INT          NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ph_user_id`    (`user_id`),
    INDEX `idx_ph_created_at` (`created_at`),
    CONSTRAINT `fk_ph_user`
        FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTA: tbl_users.password_changed_at YA EXISTE en db_skeleton.sql.
-- Solo se necesita agregar force_password_change.
--
-- Verificar antes con:
--   SHOW COLUMNS FROM tbl_users LIKE 'force_password_change';
-- Si retorna vacío, ejecutar:
--   ALTER TABLE `tbl_users`
--       ADD COLUMN `force_password_change` TINYINT(1) NOT NULL DEFAULT 0
--       AFTER `password_changed_at`;
-- ============================================================

-- 3. Módulo security_password_policy en tbl_modules
INSERT IGNORE INTO `tbl_modules`
    (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
    ('Política de Contraseñas',
     'security_password_policy',
     'Configuración de reglas de seguridad para contraseñas del sistema',
     'ph-duotone ph-password',
     '/security/password-policy',
     13,
     1);

-- 4. Permisos del módulo
INSERT IGNORE INTO `tbl_permissions`
    (`module_id`, `name`, `slug`, `description`)
VALUES
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_password_policy' LIMIT 1),
     'Ver Política de Contraseñas',
     'security_password_policy.view',
     'Permite ver la configuración de política de contraseñas'),
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_password_policy' LIMIT 1),
     'Editar Política de Contraseñas',
     'security_password_policy.edit',
     'Permite editar la configuración de política de contraseñas');

-- 5. Asignar permisos al Administrador (role_id = 1)
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `tbl_permissions`
WHERE `slug` IN ('security_password_policy.view', 'security_password_policy.edit');
