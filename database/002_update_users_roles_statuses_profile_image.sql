-- ============================================================
-- Migración 002: Roles, Statuses e imagen de perfil
-- Base de datos: db_skeleton
-- Importar DESPUÉS de db_skeleton.sql (001)
-- ============================================================

USE `db_skeleton`;

-- ------------------------------------------------------------
-- Tabla: tbl_statuses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_statuses` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(50)  NOT NULL,
  `slug`       VARCHAR(50)  NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_statuses_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tbl_statuses` (`id`, `name`, `slug`) VALUES
  (1, 'Active',   'active'),
  (2, 'Inactive', 'inactive'),
  (3, 'Blocked',  'blocked');

-- ------------------------------------------------------------
-- Tabla: tbl_roles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_roles` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(50)  NOT NULL,
  `slug`       VARCHAR(50)  NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tbl_roles` (`id`, `name`, `slug`) VALUES
  (1, 'Administrador', 'administrator'),
  (2, 'Usuario',       'user'),
  (3, 'Consultor',     'consultant');

-- ------------------------------------------------------------
-- Actualizar tbl_users: añadir status_id, role_id, profile_image
-- ------------------------------------------------------------

-- Paso 1: Añadir columnas nuevas (nullable para no romper registros existentes)
ALTER TABLE `tbl_users`
  ADD COLUMN IF NOT EXISTS `status_id`     INT          NULL DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `role_id`       INT          NULL DEFAULT NULL AFTER `status_id`,
  ADD COLUMN IF NOT EXISTS `profile_image` VARCHAR(255) NULL DEFAULT NULL AFTER `remember_token`;

-- Paso 2: Migrar valores del ENUM status existente → status_id
UPDATE `tbl_users` SET `status_id` = 1 WHERE `status` = 'active'    AND `status_id` IS NULL;
UPDATE `tbl_users` SET `status_id` = 2 WHERE `status` = 'inactive'  AND `status_id` IS NULL;
UPDATE `tbl_users` SET `status_id` = 3 WHERE `status` = 'blocked'   AND `status_id` IS NULL;

-- Paso 3: Asegurar que no haya NULLs en status_id
UPDATE `tbl_users` SET `status_id` = 1 WHERE `status_id` IS NULL;

-- Paso 4: Asignar rol Administrador al usuario admin@skeleton.local
UPDATE `tbl_users` SET `role_id` = 1 WHERE `email` = 'admin@skeleton.local' AND `role_id` IS NULL;

-- Paso 5: Asignar rol Usuario a todos los demás
UPDATE `tbl_users` SET `role_id` = 2 WHERE `role_id` IS NULL;

-- Paso 6: Hacer las columnas NOT NULL con valores por defecto
ALTER TABLE `tbl_users`
  MODIFY COLUMN `status_id` INT NOT NULL DEFAULT 1,
  MODIFY COLUMN `role_id`   INT NOT NULL DEFAULT 2;

-- Paso 7: Agregar llaves foráneas (solo si no existen)
ALTER TABLE `tbl_users`
  ADD CONSTRAINT `fk_users_status_id`
    FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_role_id`
    FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON UPDATE CASCADE;

-- Nota: La columna ENUM `status` se conserva por compatibilidad.
-- El sistema PHP ahora utiliza status_id. La columna ENUM puede
-- eliminarse en una migración futura cuando ya no sea necesaria.
