-- 018_add_force_password_change.sql
-- Agrega la columna force_password_change a tbl_users
--
-- ANTES DE EJECUTAR: verificar si la columna ya existe con:
--   SHOW COLUMNS FROM tbl_users LIKE 'force_password_change';
-- Ejecutar el ALTER TABLE SOLO si la columna NO aparece en el resultado.

ALTER TABLE `tbl_users`
    ADD COLUMN `force_password_change` TINYINT(1) NOT NULL DEFAULT 0
    AFTER `password_changed_at`;
