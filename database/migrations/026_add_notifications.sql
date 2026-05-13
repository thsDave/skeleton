-- Ejecutar solo si no existe la tabla tbl_notifications.
-- No modifica tablas existentes ni elimina datos.

CREATE TABLE `tbl_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `url` VARCHAR(255) NULL,
  `icon` VARCHAR(100) NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'info',
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notifications_user_id` (`user_id`),
  INDEX `idx_notifications_read_at` (`read_at`),
  INDEX `idx_notifications_created_at` (`created_at`),
  INDEX `idx_notifications_type` (`type`),
  CONSTRAINT `fk_notifications_user`
    FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejecutar los INSERT solo si los permisos no existen.

INSERT INTO `tbl_modules`
  (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
SELECT
  'Notificaciones',
  'notifications',
  'Notificaciones internas del usuario',
  'ph-duotone ph-bell',
  '/notifications',
  14,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `tbl_modules` WHERE `slug` = 'notifications'
);

INSERT INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
SELECT m.`id`, 'Ver notificaciones', 'notifications.view', 'Ver notificaciones internas propias'
FROM `tbl_modules` m
WHERE m.`slug` = 'notifications'
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_permissions` WHERE `slug` = 'notifications.view'
  );

INSERT INTO `tbl_permissions` (`module_id`, `name`, `slug`, `description`)
SELECT m.`id`, 'Marcar notificaciones como leidas', 'notifications.mark_read', 'Marcar notificaciones internas propias como leidas'
FROM `tbl_modules` m
WHERE m.`slug` = 'notifications'
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_permissions` WHERE `slug` = 'notifications.mark_read'
  );

INSERT INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `tbl_roles` r
JOIN `tbl_permissions` p ON p.`slug` IN ('notifications.view', 'notifications.mark_read')
WHERE r.`slug` IN ('administrator', 'user', 'consultant')
  AND NOT EXISTS (
    SELECT 1
    FROM `tbl_role_permissions` rp
    WHERE rp.`role_id` = r.`id`
      AND rp.`permission_id` = p.`id`
  );
