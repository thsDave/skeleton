-- ============================================================
-- MIGRACIÓN: Crear tabla tbl_rate_limits
-- Proyecto: Skeleton
--
-- Objetivo:
-- Crear la infraestructura de almacenamiento del servicio generico
-- App\Services\RateLimitService (Etapa 3.1). Esta migracion SOLO crea
-- una tabla nueva; no modifica ninguna tabla existente y no toca datos
-- de usuarios, login, MFA, recuperacion de contrasena ni auditoria.
--
-- IMPORTANTE — LEER ANTES DE EJECUTAR:
-- 1. Ejecutar primero un respaldo completo de la base de datos
--    "skeleton" (phpMyAdmin > Exportar, o mysqldump).
-- 2. Verificar manualmente que la tabla `tbl_rate_limits` NO exista
--    todavia (por ejemplo: SHOW TABLES LIKE 'tbl_rate_limits';).
--    Esta migracion NO usa `CREATE TABLE IF NOT EXISTS` a proposito
--    (convencion del proyecto) — si la tabla ya existe, esta
--    migracion fallara con un error de MySQL en vez de sobrescribir
--    nada; eso es el comportamiento esperado y seguro.
-- 3. Ejecutar esta migracion UNA SOLA VEZ.
-- 4. No usar `IF NOT EXISTS`, `DROP TABLE IF EXISTS` ni
--    `ALTER TABLE ... IF NOT EXISTS` (no se usan aqui).
-- 5. No se crean datos semilla (seed) en esta migracion.
-- ============================================================

CREATE TABLE `tbl_rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: auth.login, mfa.totp_challenge, password_reset.request',
  `identifier_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'HMAC-SHA256 hex del identificador (IP/email/user_id); nunca se guarda el identificador crudo',
  `identifier_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: ip, email, user, session, custom',
  `attempts` int NOT NULL DEFAULT '0',
  `max_attempts` int NOT NULL,
  `window_seconds` int NOT NULL,
  `first_attempt_at` datetime NOT NULL,
  `available_at` datetime DEFAULT NULL COMMENT 'NULL si no esta bloqueado; fecha/hora a partir de la cual vuelve a estar disponible si esta bloqueado',
  `last_attempt_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate_limits_action_identifier` (`action`,`identifier_hash`),
  KEY `idx_rate_limits_available_at` (`available_at`),
  KEY `idx_rate_limits_last_attempt_at` (`last_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Almacenamiento generico de rate limit (Etapa 3.1). Tabla nueva, aun no conectada a ningun flujo funcional.';
