-- ============================================================
-- 009 – Módulo Seguridad > MFA (configuración global)
-- Importar en phpMyAdmin antes de usar el módulo.
-- Todos los INSERT usan IGNORE: seguro ejecutar más de una vez.
-- ============================================================

-- 1. Tabla de configuración global MFA
CREATE TABLE IF NOT EXISTS `tbl_mfa_settings` (
    `id`                    INT           NOT NULL AUTO_INCREMENT,
    `email_enabled`         TINYINT(1)    NOT NULL DEFAULT 0,
    `sms_enabled`           TINYINT(1)    NOT NULL DEFAULT 0,
    `authenticator_enabled` TINYINT(1)    NOT NULL DEFAULT 1,
    `sms_provider`          VARCHAR(100)  NULL DEFAULT NULL,
    `sms_api_key`           VARCHAR(255)  NULL DEFAULT NULL,
    `sms_api_secret_enc`    TEXT          NULL DEFAULT NULL,
    `sms_from`              VARCHAR(100)  NULL DEFAULT NULL,
    `sms_endpoint`          VARCHAR(255)  NULL DEFAULT NULL,
    `sms_extra_config`      JSON          NULL DEFAULT NULL,
    `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Registro inicial (single-row, id=1)
INSERT IGNORE INTO `tbl_mfa_settings`
    (`id`, `email_enabled`, `sms_enabled`, `authenticator_enabled`)
VALUES (1, 0, 0, 1);

-- 3. Módulo en tbl_modules
INSERT IGNORE INTO `tbl_modules`
    (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
    ('MFA', 'security_mfa',
     'Configuración global de autenticación multifactor',
     'ph-duotone ph-shield-plus',
     '/security/mfa', 11, 1);

-- 4. Permisos
INSERT IGNORE INTO `tbl_permissions`
    (`module_id`, `name`, `slug`, `description`)
VALUES
    ((SELECT id FROM tbl_modules WHERE slug = 'security_mfa'),
     'Ver Configuración MFA', 'security_mfa.view',
     'Acceso de lectura al módulo MFA'),
    ((SELECT id FROM tbl_modules WHERE slug = 'security_mfa'),
     'Editar Configuración MFA', 'security_mfa.edit',
     'Guardar configuración global MFA'),
    ((SELECT id FROM tbl_modules WHERE slug = 'security_mfa'),
     'Probar SMS MFA', 'security_mfa.test',
     'Enviar SMS de prueba desde el módulo MFA');

-- 5. Asignar permisos al rol Administrador (role_id = 1)
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `tbl_permissions`
WHERE slug IN ('security_mfa.view', 'security_mfa.edit', 'security_mfa.test');
