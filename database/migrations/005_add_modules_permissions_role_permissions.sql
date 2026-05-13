-- ─────────────────────────────────────────────────────────────────────────────
-- Migración 005 — Módulos, Permisos y Permisos por Rol v3.0
-- Importar desde phpMyAdmin. No ejecutar desde PowerShell.
-- Requiere: 002_update_users_roles_statuses_profile_image.sql aplicado
--           (tbl_statuses y tbl_roles deben existir)
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Tabla de módulos del sistema
CREATE TABLE IF NOT EXISTS `tbl_modules` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `slug`        VARCHAR(100)  NOT NULL,
  `description` VARCHAR(255)  NULL,
  `icon`        VARCHAR(100)  NULL,
  `route`       VARCHAR(150)  NULL,
  `sort_order`  INT           NOT NULL DEFAULT 0,
  `status_id`   INT           NOT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NULL     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_modules_slug` (`slug`),
  CONSTRAINT `fk_modules_status`
    FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de permisos (vinculados a un módulo)
CREATE TABLE IF NOT EXISTS `tbl_permissions` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `module_id`   INT           NOT NULL,
  `name`        VARCHAR(100)  NOT NULL,
  `slug`        VARCHAR(150)  NOT NULL,
  `description` VARCHAR(255)  NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NULL     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_slug` (`slug`),
  CONSTRAINT `fk_permissions_module`
    FOREIGN KEY (`module_id`) REFERENCES `tbl_modules` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla pivote: permisos por rol
CREATE TABLE IF NOT EXISTS `tbl_role_permissions` (
  `id`            INT       NOT NULL AUTO_INCREMENT,
  `role_id`       INT       NOT NULL,
  `permission_id` INT       NOT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role`
    FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission`
    FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. Módulos iniciales (status_id = 1 = Activo)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
  ('Dashboard',               'dashboard',          'Panel principal del sistema',              'ph-duotone ph-gauge',         '/dashboard',          1, 1),
  ('Mi Perfil',               'profile',            'Perfil personal del usuario',              'ph-duotone ph-user-circle',   '/profile',            2, 1),
  ('Mi Cuenta',               'account',            'Credenciales de acceso',                   'ph-duotone ph-gear',          '/account',            3, 1),
  ('Usuarios',                'users',              'Gestión de usuarios del sistema',          'ph-duotone ph-users-three',   '/users',              4, 1),
  ('Idiomas',                 'languages',          'Gestión de idiomas del sistema',           'ph-duotone ph-translate',     '/languages',          5, 1),
  ('Información del Sistema', 'system_information', 'Datos e información general del sistema', 'ph-duotone ph-info',          '/system-information', 6, 1),
  ('Manuales',                'manuals',            'Manuales de usuario',                      'ph-duotone ph-file-text',     '/system-information', 7, 1),
  ('Seguridad / Sesiones',    'security_sessions',  'Configuración de bloqueo de sesión',       'ph-duotone ph-lock-key',      '/security/sessions',  8, 1),
  ('Roles y Permisos',        'roles_permissions',  'Gestión de permisos por rol',              'ph-duotone ph-shield-check',  '/roles-permissions',  9, 1);

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. Permisos iniciales por módulo
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`) VALUES
  -- Dashboard
  ((SELECT id FROM tbl_modules WHERE slug = 'dashboard'),          'Ver Dashboard',                  'dashboard.view',              'Acceso al panel principal'),
  -- Mi Perfil
  ((SELECT id FROM tbl_modules WHERE slug = 'profile'),            'Ver Perfil',                     'profile.view',                'Ver información del perfil'),
  ((SELECT id FROM tbl_modules WHERE slug = 'profile'),            'Editar Perfil',                  'profile.edit',                'Editar información del perfil'),
  -- Mi Cuenta
  ((SELECT id FROM tbl_modules WHERE slug = 'account'),            'Ver Cuenta',                     'account.view',                'Ver configuración de cuenta'),
  ((SELECT id FROM tbl_modules WHERE slug = 'account'),            'Editar Cuenta',                  'account.edit',                'Editar correo y contraseña'),
  -- Usuarios
  ((SELECT id FROM tbl_modules WHERE slug = 'users'),              'Ver Usuarios',                   'users.view',                  'Ver lista de usuarios'),
  ((SELECT id FROM tbl_modules WHERE slug = 'users'),              'Crear Usuarios',                 'users.create',                'Crear nuevos usuarios'),
  ((SELECT id FROM tbl_modules WHERE slug = 'users'),              'Editar Usuarios',                'users.edit',                  'Editar usuarios existentes'),
  ((SELECT id FROM tbl_modules WHERE slug = 'users'),              'Eliminar Usuarios',              'users.delete',                'Inactivar usuarios'),
  -- Idiomas
  ((SELECT id FROM tbl_modules WHERE slug = 'languages'),          'Ver Idiomas',                    'languages.view',              'Ver lista de idiomas'),
  ((SELECT id FROM tbl_modules WHERE slug = 'languages'),          'Crear Idiomas',                  'languages.create',            'Crear nuevos idiomas'),
  ((SELECT id FROM tbl_modules WHERE slug = 'languages'),          'Editar Idiomas',                 'languages.edit',              'Editar idiomas existentes'),
  ((SELECT id FROM tbl_modules WHERE slug = 'languages'),          'Activar Idiomas',                'languages.activate',          'Activar idiomas del sistema'),
  ((SELECT id FROM tbl_modules WHERE slug = 'languages'),          'Desactivar Idiomas',             'languages.deactivate',        'Desactivar idiomas del sistema'),
  -- Información del Sistema
  ((SELECT id FROM tbl_modules WHERE slug = 'system_information'), 'Ver Información del Sistema',    'system_information.view',     'Ver datos del sistema'),
  ((SELECT id FROM tbl_modules WHERE slug = 'system_information'), 'Editar Información del Sistema', 'system_information.edit',     'Editar datos del sistema'),
  -- Manuales
  ((SELECT id FROM tbl_modules WHERE slug = 'manuals'),            'Ver Manuales',                   'manuals.view',                'Ver y descargar manuales'),
  ((SELECT id FROM tbl_modules WHERE slug = 'manuals'),            'Subir Manuales',                 'manuals.upload',              'Subir nuevos manuales'),
  ((SELECT id FROM tbl_modules WHERE slug = 'manuals'),            'Editar Manuales',                'manuals.edit',                'Editar datos de manuales'),
  ((SELECT id FROM tbl_modules WHERE slug = 'manuals'),            'Activar Manuales',               'manuals.activate',            'Activar manuales'),
  ((SELECT id FROM tbl_modules WHERE slug = 'manuals'),            'Desactivar Manuales',            'manuals.deactivate',          'Desactivar manuales'),
  -- Seguridad / Sesiones
  ((SELECT id FROM tbl_modules WHERE slug = 'security_sessions'),  'Ver Seguridad / Sesiones',       'security_sessions.view',      'Ver configuración de sesión'),
  ((SELECT id FROM tbl_modules WHERE slug = 'security_sessions'),  'Editar Seguridad / Sesiones',    'security_sessions.edit',      'Editar configuración de sesión'),
  -- Roles y Permisos
  ((SELECT id FROM tbl_modules WHERE slug = 'roles_permissions'),  'Ver Roles y Permisos',           'roles_permissions.view',      'Ver módulo de roles y permisos'),
  ((SELECT id FROM tbl_modules WHERE slug = 'roles_permissions'),  'Editar Roles y Permisos',        'roles_permissions.edit',      'Editar permisos de roles');

-- ─────────────────────────────────────────────────────────────────────────────
-- 6. Permisos por rol: Administrador (role_id = 1) → TODOS
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `tbl_permissions`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 7. Permisos por rol: Usuario (role_id = 2) → permisos básicos
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `tbl_permissions`
WHERE slug IN (
  'dashboard.view',
  'profile.view',
  'profile.edit',
  'account.view',
  'account.edit',
  'system_information.view',
  'manuals.view'
);

-- ─────────────────────────────────────────────────────────────────────────────
-- 8. Permisos por rol: Consultor (role_id = 3) → permisos básicos
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `tbl_permissions`
WHERE slug IN (
  'dashboard.view',
  'profile.view',
  'profile.edit',
  'account.view',
  'account.edit',
  'system_information.view',
  'manuals.view'
);
