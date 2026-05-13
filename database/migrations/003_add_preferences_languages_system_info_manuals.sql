-- ============================================================
-- Migración 003: Idiomas, info del sistema, manuales y preferencias
-- Base de datos: db_skeleton
-- Importar DESPUÉS de 002_update_users_roles_statuses_profile_image.sql
-- ============================================================

USE `db_skeleton`;

-- ============================================================
-- 1. Tabla tbl_languages
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_languages` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `native_name` VARCHAR(100)  NULL DEFAULT NULL,
  `code`        VARCHAR(10)   NOT NULL,
  `is_default`  TINYINT(1)    NOT NULL DEFAULT 0,
  `status_id`   INT           NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_languages_code` (`code`),
  CONSTRAINT `fk_languages_status`
    FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registros iniciales
INSERT IGNORE INTO `tbl_languages` (`id`, `name`, `native_name`, `code`, `is_default`, `status_id`) VALUES
  (1, 'Español', 'Español', 'es', 1, 1),
  (2, 'English', 'English', 'en', 0, 1);

-- ============================================================
-- 2. Tabla tbl_system_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_system_settings` (
  `id`             INT           NOT NULL AUTO_INCREMENT,
  `release_year`   YEAR          NOT NULL,
  `project_leader` VARCHAR(150)  NOT NULL,
  `system_version` VARCHAR(20)   NOT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro inicial
INSERT IGNORE INTO `tbl_system_settings` (`id`, `release_year`, `project_leader`, `system_version`)
VALUES (1, YEAR(NOW()), 'Administrador', '1.0.0');

-- ============================================================
-- 3. Tabla tbl_user_manuals
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_user_manuals` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(150)  NOT NULL,
  `description` TEXT          NULL,
  `file_name`   VARCHAR(255)  NOT NULL,
  `file_path`   VARCHAR(255)  NOT NULL,
  `file_type`   VARCHAR(50)   NOT NULL,
  `file_size`   INT           NOT NULL,
  `uploaded_by` INT           NOT NULL,
  `status_id`   INT           NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_manuals_uploaded_by`
    FOREIGN KEY (`uploaded_by`) REFERENCES `tbl_users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_manuals_status`
    FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Agregar preferencias a tbl_users
-- ============================================================

-- theme_preference (light/dark)
ALTER TABLE `tbl_users`
  ADD `theme_preference` VARCHAR(20) NOT NULL DEFAULT 'light' AFTER `profile_image`;

-- language_id (relación con tbl_languages)
ALTER TABLE `tbl_users`
  ADD `language_id` INT NULL DEFAULT NULL AFTER `theme_preference`;

-- Llave foránea language_id → tbl_languages
ALTER TABLE `tbl_users`
  ADD CONSTRAINT `fk_users_language_id`
    FOREIGN KEY (`language_id`) REFERENCES `tbl_languages` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- Asignar idioma español por defecto al usuario administrador existente
UPDATE `tbl_users` SET `language_id` = 1 WHERE `language_id` IS NULL;

-- ============================================================
-- Nota: Si ADD no es compatible con tu versión
-- de MySQL, ejecuta manualmente:
--
--   ALTER TABLE tbl_users ADD theme_preference VARCHAR(20) NOT NULL DEFAULT 'light';
--   ALTER TABLE tbl_users ADD language_id INT NULL DEFAULT NULL;
--   ALTER TABLE tbl_users ADD FOREIGN KEY (language_id) REFERENCES tbl_languages(id) ON UPDATE CASCADE ON DELETE SET NULL;
-- ============================================================
