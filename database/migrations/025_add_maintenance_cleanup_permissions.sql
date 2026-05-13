-- Ejecutar solo si no existe el modulo/permiso de limpieza de mantenimiento.
-- No modifica tablas existentes ni elimina datos.

INSERT INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
SELECT
  'Mantenimiento',
  'maintenance',
  'Herramientas administrativas de mantenimiento',
  'ph-duotone ph-broom',
  '/maintenance/cleanup',
  13,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `tbl_modules` WHERE `slug` = 'maintenance'
);

INSERT INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
SELECT m.`id`, 'Ver limpieza de datos temporales', 'maintenance.cleanup.view', 'Ver resumen de datos temporales limpiables'
FROM `tbl_modules` m
WHERE m.`slug` = 'maintenance'
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_permissions` WHERE `slug` = 'maintenance.cleanup.view'
  );

INSERT INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
SELECT m.`id`, 'Ejecutar limpieza de datos temporales', 'maintenance.cleanup.run', 'Ejecutar limpieza controlada de datos temporales'
FROM `tbl_modules` m
WHERE m.`slug` = 'maintenance'
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_permissions` WHERE `slug` = 'maintenance.cleanup.run'
  );

INSERT INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.`slug` IN ('maintenance.cleanup.view', 'maintenance.cleanup.run')
WHERE r.`slug` = 'administrator'
  AND NOT EXISTS (
    SELECT 1
    FROM `tbl_role_permissions` rp
    WHERE rp.`role_id` = r.`id`
      AND rp.`permission_id` = p.`id`
  );
