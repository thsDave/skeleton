-- Ejecutar solo si no existe el modulo/permiso system_health.view.
-- No modifica tablas existentes ni elimina datos.

INSERT INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
SELECT
  'Salud del Sistema',
  'system_health',
  'Panel tecnico de verificaciones del sistema',
  'ph-duotone ph-heartbeat',
  '/system-health',
  12,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `tbl_modules` WHERE `slug` = 'system_health'
);

INSERT INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
SELECT
  m.`id`,
  'Ver Salud del Sistema',
  'system_health.view',
  'Acceso al panel de salud del sistema'
FROM `tbl_modules` m
WHERE m.`slug` = 'system_health'
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_permissions` WHERE `slug` = 'system_health.view'
  );

INSERT INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.`slug` = 'system_health.view'
WHERE r.`slug` = 'administrator'
  AND NOT EXISTS (
    SELECT 1
    FROM `tbl_role_permissions` rp
    WHERE rp.`role_id` = r.`id`
      AND rp.`permission_id` = p.`id`
  );
