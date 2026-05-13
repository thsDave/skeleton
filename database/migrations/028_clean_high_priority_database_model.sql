-- ============================================================
-- Migracion 028 - Limpieza critica del modelo de base de datos
-- Proyecto: Skeleton
--
-- Objetivo:
-- 1. Dejar tbl_users.status_id como fuente oficial del estado.
-- 2. Eliminar el campo heredado tbl_users.status.
-- 3. Agregar FK tbl_two_factor_codes.user_id -> tbl_users.id.
-- 4. Agregar FK tbl_authentication_settings.default_role_id -> tbl_roles.id.
--
-- Importante:
-- - Ejecutar solo despues de validar que la columna tbl_users.status existe.
-- - Ejecutar solo si las FKs indicadas todavia no existen.
-- - Evita sintaxis condicional no compatible con algunas versiones de MySQL/phpMyAdmin.
-- ============================================================

-- ------------------------------------------------------------
-- A. Alinear datos de estado antes de eliminar el ENUM heredado
-- ------------------------------------------------------------
UPDATE `tbl_users` u
JOIN `tbl_statuses` s ON s.`slug` = u.`status`
SET u.`status_id` = s.`id`
WHERE u.`status` IS NOT NULL;

UPDATE `tbl_users`
SET `status_id` = (SELECT s.`id` FROM `tbl_statuses` s WHERE s.`slug` = 'active' LIMIT 1)
WHERE `status_id` IS NULL
   OR `status_id` NOT IN (SELECT s2.`id` FROM `tbl_statuses` s2);

-- ------------------------------------------------------------
-- B. Eliminar indice y columna heredada tbl_users.status
-- ------------------------------------------------------------
ALTER TABLE `tbl_users`
  DROP INDEX `idx_users_status`;

ALTER TABLE `tbl_users`
  DROP COLUMN `status`;

-- ------------------------------------------------------------
-- C. Preparar y formalizar FK de codigos MFA temporales
-- ------------------------------------------------------------
DELETE tfc
FROM `tbl_two_factor_codes` tfc
LEFT JOIN `tbl_users` u ON u.`id` = tfc.`user_id`
WHERE u.`id` IS NULL;

ALTER TABLE `tbl_two_factor_codes`
  ADD CONSTRAINT `fk_two_factor_codes_user`
  FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

-- ------------------------------------------------------------
-- D. Preparar y formalizar FK del rol por defecto de OAuth
-- ------------------------------------------------------------
UPDATE `tbl_authentication_settings` auth
LEFT JOIN `tbl_roles` r ON r.`id` = auth.`default_role_id`
SET auth.`default_role_id` = NULL
WHERE auth.`default_role_id` IS NOT NULL
  AND r.`id` IS NULL;

ALTER TABLE `tbl_authentication_settings`
  ADD INDEX `idx_authentication_settings_default_role_id` (`default_role_id`);

ALTER TABLE `tbl_authentication_settings`
  ADD CONSTRAINT `fk_authentication_settings_default_role`
  FOREIGN KEY (`default_role_id`) REFERENCES `tbl_roles` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;
