-- ============================================================
-- Migracion 029 - Correccion de datos semilla con UTF-8 doble
-- codificado (mojibake), ej. "EspaÃ±ol" en vez de "Español"
-- Proyecto: Skeleton
--
-- Contexto:
-- Los datos semilla de tbl_languages, tbl_modules y tbl_permissions
-- quedaron con texto doblemente codificado en UTF-8 (los bytes UTF-8
-- correctos de 'ñ', 'ó', 'í', 'é' fueron reinterpretados como
-- Latin-1/Windows-1252 y vueltos a codificar como UTF-8). Se
-- confirmo, comparando contra las migraciones originales (003 y 005),
-- que esas migraciones YA contienen el texto correcto ("Español",
-- "Gestión", "Información", "Configuración"); la corrupcion aparece
-- unicamente en database/schema/skeleton_schema.sql (ya corregido en
-- esta misma etapa) y, por arrastre, en instalaciones que se
-- sembraron importando ese archivo consolidado en vez de correr las
-- migraciones 002-028 en orden.
--
-- La conexion PDO actual (config/database.php, charset=utf8mb4) esta
-- correctamente configurada; este script NO cambia collation ni
-- charset de ninguna tabla/columna, solo corrige el CONTENIDO de
-- filas semilla ya insertadas con el defecto descrito.
--
-- IMPORTANTE:
-- - Hacer un respaldo completo de la base de datos antes de ejecutar
--   este script (phpMyAdmin > Exportar, o mysqldump).
-- - Ejecutar solo si las consultas de verificacion previa (seccion A)
--   devuelven filas con "Ã" visible en name/description.
-- - No usar IF NOT EXISTS / ADD COLUMN IF NOT EXISTS / DROP COLUMN
--   IF EXISTS (no aplica; este script no modifica estructura).
--
-- Orden de ejecucion sugerido:
--   1. Ejecutar las consultas de la seccion A (verificacion previa) y
--      anotar el resultado.
--   2. Ejecutar los UPDATE de las secciones B, C y D.
--   3. Ejecutar las consultas de la seccion E (verificacion posterior);
--      deben devolver 0 filas en las tres tablas.
-- ============================================================

-- ------------------------------------------------------------
-- A. Verificacion previa (ejecutar antes de los UPDATE)
-- ------------------------------------------------------------
-- SELECT `id`, `code`, `name`, `native_name` FROM `tbl_languages`
--   WHERE HEX(`name`) LIKE '%C383%' OR HEX(`native_name`) LIKE '%C383%';
-- SELECT `id`, `name`, `description` FROM `tbl_modules`
--   WHERE HEX(`name`) LIKE '%C383%' OR HEX(`description`) LIKE '%C383%' ORDER BY `id`;
-- SELECT `id`, `name`, `description` FROM `tbl_permissions`
--   WHERE HEX(`name`) LIKE '%C383%' OR HEX(`description`) LIKE '%C383%' ORDER BY `id`;
--
-- Nota: se usa HEX(...) LIKE '%C383%' (byte exacto 0xC3 0x83 = caracter
-- 'Ã') en vez de LIKE '%Ã%' porque las collations *_ci de este
-- proyecto (utf8mb4_general_ci / utf8mb4_unicode_ci) son insensibles a
-- acentos y producen falsos positivos con LIKE '%Ã%' normal (por
-- ejemplo, coinciden con la palabra correcta "contraseña").

-- ------------------------------------------------------------
-- B. tbl_languages
-- ------------------------------------------------------------
UPDATE `tbl_languages`
SET `name` = 'Español', `native_name` = 'Español'
WHERE `code` = 'es';

-- ------------------------------------------------------------
-- C. tbl_modules
-- ------------------------------------------------------------
UPDATE `tbl_modules` SET `description` = 'Gestión de usuarios del sistema' WHERE `id` = 4;
UPDATE `tbl_modules` SET `description` = 'Gestión de idiomas del sistema' WHERE `id` = 5;
UPDATE `tbl_modules` SET `name` = 'Información del Sistema', `description` = 'Datos e información general del sistema' WHERE `id` = 6;
UPDATE `tbl_modules` SET `description` = 'Configuración de bloqueo de sesión' WHERE `id` = 8;
UPDATE `tbl_modules` SET `description` = 'Gestión de permisos por rol' WHERE `id` = 9;
UPDATE `tbl_modules` SET `name` = 'Auditoría', `description` = 'Registros de auditoría del sistema' WHERE `id` = 10;
UPDATE `tbl_modules` SET `description` = 'Configuración SMTP del sistema' WHERE `id` = 11;
UPDATE `tbl_modules` SET `description` = 'Configuración global de autenticación multifactor' WHERE `id` = 12;
UPDATE `tbl_modules` SET `description` = 'Configuración de protección contra intentos fallidos de login' WHERE `id` = 13;
UPDATE `tbl_modules` SET `name` = 'Autenticación', `description` = 'Configuración de métodos de inicio de sesión: local y proveedores externos OAuth' WHERE `id` = 14;
UPDATE `tbl_modules` SET `description` = 'Configuración visual del sistema: logo, favicon, fondo de login y colores' WHERE `id` = 15;
UPDATE `tbl_modules` SET `name` = 'Política de Contraseñas', `description` = 'Configuración de reglas de seguridad para contraseñas del sistema' WHERE `id` = 16;

-- ------------------------------------------------------------
-- D. tbl_permissions
-- ------------------------------------------------------------
UPDATE `tbl_permissions` SET `description` = 'Ver información del perfil' WHERE `id` = 2;
UPDATE `tbl_permissions` SET `description` = 'Editar información del perfil' WHERE `id` = 3;
UPDATE `tbl_permissions` SET `description` = 'Ver configuración de cuenta' WHERE `id` = 4;
UPDATE `tbl_permissions` SET `description` = 'Editar correo y contraseña' WHERE `id` = 5;
UPDATE `tbl_permissions` SET `name` = 'Ver Información del Sistema' WHERE `id` = 15;
UPDATE `tbl_permissions` SET `name` = 'Editar Información del Sistema' WHERE `id` = 16;
UPDATE `tbl_permissions` SET `description` = 'Ver configuración de sesión' WHERE `id` = 22;
UPDATE `tbl_permissions` SET `description` = 'Editar configuración de sesión' WHERE `id` = 23;
UPDATE `tbl_permissions` SET `description` = 'Ver módulo de roles y permisos' WHERE `id` = 24;
UPDATE `tbl_permissions` SET `name` = 'Ver Auditoría', `description` = 'Ver listado de registros de auditoría' WHERE `id` = 26;
UPDATE `tbl_permissions` SET `name` = 'Ver Detalle Auditoría', `description` = 'Ver detalle de un registro de auditoría' WHERE `id` = 27;
UPDATE `tbl_permissions` SET `description` = 'Ver configuración SMTP' WHERE `id` = 28;
UPDATE `tbl_permissions` SET `description` = 'Editar configuración SMTP' WHERE `id` = 29;
UPDATE `tbl_permissions` SET `name` = 'Ver Configuración MFA', `description` = 'Acceso de lectura al módulo MFA' WHERE `id` = 31;
UPDATE `tbl_permissions` SET `name` = 'Editar Configuración MFA', `description` = 'Guardar configuración global MFA' WHERE `id` = 32;
UPDATE `tbl_permissions` SET `description` = 'Enviar SMS de prueba desde el módulo MFA' WHERE `id` = 33;
UPDATE `tbl_permissions` SET `description` = 'Permite ver la configuración de intentos fallidos de login' WHERE `id` = 35;
UPDATE `tbl_permissions` SET `description` = 'Permite editar la configuración de intentos fallidos de login' WHERE `id` = 36;
UPDATE `tbl_permissions` SET `name` = 'Ver Autenticación', `description` = 'Permite ver la configuración de métodos de autenticación' WHERE `id` = 37;
UPDATE `tbl_permissions` SET `name` = 'Editar configuración general', `description` = 'Permite editar la configuración general de autenticación' WHERE `id` = 38;
UPDATE `tbl_permissions` SET `description` = 'Permite configurar proveedores de autenticación externos' WHERE `id` = 39;
UPDATE `tbl_permissions` SET `description` = 'Permite editar la configuración de proveedores externos' WHERE `id` = 40;
UPDATE `tbl_permissions` SET `description` = 'Permite probar la configuración de proveedores externos' WHERE `id` = 42;
UPDATE `tbl_permissions` SET `description` = 'Permite ver la configuración de apariencia del sistema' WHERE `id` = 43;
UPDATE `tbl_permissions` SET `description` = 'Permite modificar la configuración de apariencia del sistema' WHERE `id` = 44;
UPDATE `tbl_permissions` SET `name` = 'Ver Política de Contraseñas', `description` = 'Permite ver la configuración de política de contraseñas' WHERE `id` = 46;
UPDATE `tbl_permissions` SET `name` = 'Editar Política de Contraseñas', `description` = 'Permite editar la configuración de política de contraseñas' WHERE `id` = 47;

-- ------------------------------------------------------------
-- E. Verificacion posterior (ejecutar despues de los UPDATE;
--    las tres consultas deben devolver 0 filas)
-- ------------------------------------------------------------
-- SELECT COUNT(*) FROM `tbl_languages`  WHERE HEX(`name`) LIKE '%C383%' OR HEX(`native_name`) LIKE '%C383%';
-- SELECT COUNT(*) FROM `tbl_modules`    WHERE HEX(`name`) LIKE '%C383%' OR HEX(`description`) LIKE '%C383%';
-- SELECT COUNT(*) FROM `tbl_permissions` WHERE HEX(`name`) LIKE '%C383%' OR HEX(`description`) LIKE '%C383%';
