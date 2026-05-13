-- Migracion 023: permisos para historial de sesiones
-- Ejecutar solo si no existen estos permisos en tbl_permissions.

INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
VALUES
  ((SELECT id FROM `tbl_modules` WHERE slug = 'account'), 'Ver historial de sesiones propias', 'account.sessions.history', 'Consultar historial propio de sesiones'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'users'), 'Ver historial de sesiones de usuarios', 'users.sessions.view', 'Consultar historial de sesiones de usuarios'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'users'), 'Cerrar sesion desde historial', 'users.sessions.revoke', 'Cerrar una sesion activa desde historial de usuarios'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'users'), 'Cerrar todas las sesiones desde historial', 'users.sessions.revoke_all', 'Cerrar sesiones activas de un usuario desde historial');

INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.slug = 'account.sessions.history'
WHERE r.slug IN ('administrator', 'user', 'consultant');

INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.slug IN (
  'users.sessions.view',
  'users.sessions.revoke',
  'users.sessions.revoke_all'
)
WHERE r.slug = 'administrator';
