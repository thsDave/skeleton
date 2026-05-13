-- Migracion 021: permisos para exportacion Excel
-- Ejecutar solo si no existen los permisos users.export y audit_logs.export.

INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
VALUES
  ((SELECT id FROM `tbl_modules` WHERE slug = 'users'), 'Exportar Usuarios', 'users.export', 'Exportar listado de usuarios en formato Excel'),
  ((SELECT id FROM `tbl_modules` WHERE slug = 'audit_logs'), 'Exportar Auditoria', 'audit_logs.export', 'Exportar registros de auditoria en formato Excel');

INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id
FROM `tbl_permissions`
WHERE slug IN ('users.export', 'audit_logs.export');
