-- ============================================================
-- Migración 015 — Módulo Apariencia del sistema
-- Ejecutar en phpMyAdmin sobre la base de datos del proyecto
-- ============================================================

-- 1. Tabla de configuración de apariencia
CREATE TABLE `tbl_appearance_settings` (
  `id`                    INT            NOT NULL AUTO_INCREMENT,
  `app_display_name`      VARCHAR(150)   NULL DEFAULT NULL,
  `app_tagline`           VARCHAR(255)   NULL DEFAULT NULL,
  `logo_path`             VARCHAR(255)   NULL DEFAULT NULL,
  `favicon_path`          VARCHAR(255)   NULL DEFAULT NULL,
  `login_background_path` VARCHAR(255)   NULL DEFAULT NULL,
  `primary_color`         VARCHAR(20)    NULL DEFAULT NULL,
  `sidebar_color`         VARCHAR(20)    NULL DEFAULT NULL,
  `login_overlay_color`   VARCHAR(20)    NULL DEFAULT NULL,
  `login_overlay_opacity` DECIMAL(3,2)   NULL DEFAULT 0.40,
  `created_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP      NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Registro inicial
INSERT INTO `tbl_appearance_settings`
  (`app_display_name`, `app_tagline`, `logo_path`, `favicon_path`,
   `login_background_path`, `primary_color`, `sidebar_color`,
   `login_overlay_color`, `login_overlay_opacity`)
VALUES
  ('Skeleton', 'Sistema MVC', NULL, NULL, NULL, NULL, NULL, NULL, 0.40);

-- 3. Módulo appearance en tbl_modules
INSERT IGNORE INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
  ('Apariencia',
   'appearance',
   'Configuración visual del sistema: logo, favicon, fondo de login y colores',
   'ph-duotone ph-palette',
   '/appearance',
   12,
   1);

-- 4. Permisos del módulo
INSERT IGNORE INTO `tbl_permissions`
  (`module_id`, `name`, `slug`, `description`)
VALUES
  ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'appearance' LIMIT 1),
   'Ver Apariencia',
   'appearance.view',
   'Permite ver la configuración de apariencia del sistema'),
  ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'appearance' LIMIT 1),
   'Editar Apariencia',
   'appearance.edit',
   'Permite modificar la configuración de apariencia del sistema'),
  ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'appearance' LIMIT 1),
   'Restablecer Apariencia',
   'appearance.reset',
   'Permite restablecer elementos de apariencia a sus valores por defecto');

-- 5. Asignar permisos al Administrador (role_id = 1)
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `tbl_permissions`
WHERE `slug` IN ('appearance.view', 'appearance.edit', 'appearance.reset');
