-- ─────────────────────────────────────────────────────────────────────────────
-- Migración 008 — Configuración SMTP administrable
-- Importar desde phpMyAdmin. No ejecutar desde PowerShell.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Tabla de configuración SMTP (registro único, id = 1)
CREATE TABLE IF NOT EXISTS `tbl_smtp_settings` (
  `id`               INT           NOT NULL AUTO_INCREMENT,
  `host`             VARCHAR(255)  NOT NULL DEFAULT '',
  `port`             SMALLINT      NOT NULL DEFAULT 587,
  `username`         VARCHAR(255)  NOT NULL DEFAULT '',
  `password_enc`     TEXT          NOT NULL DEFAULT '',
  `encryption`       ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
  `from_address`     VARCHAR(255)  NOT NULL DEFAULT '',
  `from_name`        VARCHAR(150)  NOT NULL DEFAULT '',
  `is_verified`      TINYINT(1)    NOT NULL DEFAULT 0,
  `last_tested_at`   DATETIME      NULL,
  `last_test_status` VARCHAR(500)  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NULL     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro inicial vacío (id = 1)
INSERT IGNORE INTO `tbl_smtp_settings` (`id`) VALUES (1);

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Módulo SMTP en tbl_modules
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
  ('Seguridad / SMTP', 'security_smtp', 'Configuración SMTP del sistema', 'ph-duotone ph-envelope', '/security/smtp', 10, 1);

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. Permisos del módulo SMTP
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`) VALUES
  ((SELECT id FROM tbl_modules WHERE slug = 'security_smtp'), 'Ver SMTP',     'security_smtp.view', 'Ver configuración SMTP'),
  ((SELECT id FROM tbl_modules WHERE slug = 'security_smtp'), 'Editar SMTP',  'security_smtp.edit', 'Editar configuración SMTP'),
  ((SELECT id FROM tbl_modules WHERE slug = 'security_smtp'), 'Probar SMTP',  'security_smtp.test', 'Enviar correo de prueba SMTP');

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. Asignar todos los permisos SMTP al rol Administrador (role_id = 1)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `tbl_permissions`
WHERE slug IN ('security_smtp.view', 'security_smtp.edit', 'security_smtp.test');
