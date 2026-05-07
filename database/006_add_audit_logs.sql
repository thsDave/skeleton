-- ─────────────────────────────────────────────────────────────────────────────
-- Migración 006 — Sistema de Auditoría v3.0
-- Importar desde phpMyAdmin. No ejecutar desde PowerShell.
-- Requiere: 005_add_modules_permissions_role_permissions.sql aplicado
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Tabla principal de registros de auditoría
CREATE TABLE IF NOT EXISTS `tbl_audit_logs` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `user_id`     INT          NULL,
  `module`      VARCHAR(100) NULL,
  `action`      VARCHAR(100) NOT NULL,
  `entity`      VARCHAR(100) NULL,
  `entity_id`   INT          NULL,
  `description` TEXT         NULL,
  `old_values`  JSON         NULL,
  `new_values`  JSON         NULL,
  `ip_address`  VARCHAR(45)  NULL,
  `user_agent`  VARCHAR(255) NULL,
  `route`       VARCHAR(255) NULL,
  `method`      VARCHAR(10)  NULL,
  `status`      VARCHAR(50)  NOT NULL DEFAULT 'success',
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_audit_user_id`    (`user_id`),
  INDEX `idx_audit_module`     (`module`),
  INDEX `idx_audit_action`     (`action`),
  INDEX `idx_audit_entity`     (`entity`),
  INDEX `idx_audit_entity_id`  (`entity_id`),
  INDEX `idx_audit_status`     (`status`),
  INDEX `idx_audit_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Módulo: Auditoría
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
  ('Auditoría', 'audit_logs', 'Registros de auditoría del sistema', 'ph-duotone ph-clipboard-text', '/audit-logs', 10, 1);

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. Permisos de auditoría
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`) VALUES
  ((SELECT id FROM tbl_modules WHERE slug = 'audit_logs'), 'Ver Auditoría',        'audit_logs.view', 'Ver listado de registros de auditoría'),
  ((SELECT id FROM tbl_modules WHERE slug = 'audit_logs'), 'Ver Detalle Auditoría','audit_logs.show', 'Ver detalle de un registro de auditoría');

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. Asignar permisos al rol Administrador (role_id = 1)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `tbl_permissions`
WHERE slug IN ('audit_logs.view', 'audit_logs.show');
