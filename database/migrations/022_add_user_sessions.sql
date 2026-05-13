-- Migration 022 - Active user sessions
-- Import this file in phpMyAdmin only if tbl_user_sessions does not exist.
-- Do not run this migration more than once.

CREATE TABLE `tbl_user_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `session_hash` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `browser` VARCHAR(100) NULL,
  `platform` VARCHAR(100) NULL,
  `device_type` VARCHAR(50) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity_at` DATETIME NULL,
  `revoked_at` DATETIME NULL,
  `revoked_by` INT NULL,
  `revoke_reason` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_sessions_hash` (`session_hash`),
  KEY `idx_user_sessions_user_id` (`user_id`),
  KEY `idx_user_sessions_revoked_at` (`revoked_at`),
  KEY `idx_user_sessions_last_activity` (`last_activity_at`),
  CONSTRAINT `fk_user_sessions_user`
    FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_user_sessions_revoked_by`
    FOREIGN KEY (`revoked_by`) REFERENCES `tbl_users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`) VALUES
  ((SELECT id FROM `tbl_modules` WHERE slug = 'account'), 'Ver sesiones propias', 'account.sessions.view', 'Ver sesiones activas propias'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'account'), 'Cerrar sesiones propias', 'account.sessions.revoke', 'Cerrar sesiones activas propias'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'security_sessions'), 'Ver sesiones activas', 'security_sessions.view_active', 'Ver sesiones activas de usuarios'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'security_sessions'), 'Cerrar sesion activa', 'security_sessions.revoke', 'Cerrar una sesion activa'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'security_sessions'), 'Cerrar sesiones de usuario', 'security_sessions.revoke_user_all', 'Cerrar todas las sesiones activas de un usuario');

INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.slug IN (
  'account.sessions.view',
  'account.sessions.revoke'
)
WHERE r.slug IN ('administrator', 'user', 'consultant');

INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.slug IN (
  'security_sessions.view_active',
  'security_sessions.revoke',
  'security_sessions.revoke_user_all'
)
WHERE r.slug = 'administrator';
