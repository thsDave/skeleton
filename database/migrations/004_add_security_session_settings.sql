-- ============================================================
-- Migración 004: Configuración de seguridad — bloqueo de sesión
-- Base de datos: db_skeleton
-- Importar DESPUÉS de 003_add_preferences_languages_system_info_manuals.sql
-- ============================================================

USE `db_skeleton`;

-- ============================================================
-- 1. Tabla tbl_security_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbl_security_settings` (
  `id`                          INT           NOT NULL AUTO_INCREMENT,
  `session_lock_enabled`        TINYINT(1)    NOT NULL DEFAULT 1
                                COMMENT '1 = activado, 0 = desactivado',
  `session_inactivity_seconds`  INT           NOT NULL DEFAULT 900
                                COMMENT 'Segundos de inactividad antes de bloquear (900 = 15 min)',
  `created_at`                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                  TIMESTAMP     NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Registro inicial de configuración (solo si no existe)
-- ============================================================
INSERT INTO `tbl_security_settings` (`id`, `session_lock_enabled`, `session_inactivity_seconds`)
SELECT 1, 1, 900
WHERE NOT EXISTS (
    SELECT 1 FROM `tbl_security_settings` WHERE `id` = 1
);
