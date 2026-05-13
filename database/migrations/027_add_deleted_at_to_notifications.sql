-- Ejecutar solo si tbl_notifications existe y la columna deleted_at no existe.
-- Verificar primero en phpMyAdmin:
--   SHOW COLUMNS FROM tbl_notifications LIKE 'deleted_at';
-- Si la consulta no devuelve filas, ejecutar este archivo.
-- No ejecutar dos veces para evitar columnas o indices duplicados.

ALTER TABLE `tbl_notifications`
  ADD COLUMN `deleted_at` DATETIME NULL AFTER `read_at`;

CREATE INDEX `idx_notifications_deleted_at` ON `tbl_notifications` (`deleted_at`);
CREATE INDEX `idx_notifications_user_deleted_read` ON `tbl_notifications` (`user_id`, `deleted_at`, `read_at`);
