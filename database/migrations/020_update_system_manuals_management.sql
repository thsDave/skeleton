-- Migracion 020: Administracion completa de manuales del sistema
-- Ejecutar solo si la columna tbl_user_manuals.deleted_at no existe.

ALTER TABLE `tbl_user_manuals`
  ADD `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`;

INSERT IGNORE INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
VALUES
  ((SELECT id FROM `tbl_modules` WHERE slug = 'manuals'), 'Eliminar Manuales', 'manuals.delete', 'Eliminar manuales mediante borrado logico');

INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id
FROM `tbl_permissions`
WHERE slug = 'manuals.delete';
