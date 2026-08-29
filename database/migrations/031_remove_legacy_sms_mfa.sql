-- ═════════════════════════════════════════════════════════════════════════════
-- Migración 031 — Eliminar referencias heredadas de MFA por SMS (Etapa 6.1)
-- ═════════════════════════════════════════════════════════════════════════════
--
-- CONTEXTO:
-- MFA por SMS fue retirado deliberadamente en la migración 011
-- (011_remove_sms_from_mfa.sql). Desde entonces, `sms_enabled` queda
-- forzado a 0 por codigo (App\Models\MfaSettings::update()), y el login
-- bloquea activamente cualquier usuario con `two_factor_method = 'sms'`.
-- El analisis completo de la Etapa 6 (ver
-- resultados/etapa-6-revision-columnas-sms-heredadas-skeleton.txt)
-- confirmo que no existe ningun uso real de SMS en el sistema: sin
-- proveedor, sin servicio de envio, sin UI, sin traducciones visibles.
--
-- Esta migracion 031 completa la limpieza: invalida cualquier dato SMS
-- heredado remanente, retira el valor 'sms' de los enum relacionados,
-- elimina las columnas de configuracion/almacenamiento SMS que ya no se
-- usan, y elimina el permiso huerfano `security_mfa.test` (no tiene
-- ningun check funcional en ningun controlador).
--
-- ⚠️  ADVERTENCIA — MIGRACIÓN DESTRUCTIVA ⚠️
-- Esta migracion ELIMINA COLUMNAS (`ALTER TABLE ... DROP COLUMN`) y
-- MODIFICA ENUMS existentes. Estas operaciones NO son reversibles sin
-- un backup previo.
--
-- ANTES DE EJECUTAR ESTA MIGRACIÓN EN CUALQUIER ENTORNO (local, staging
-- o producción):
--   1. HACER BACKUP COMPLETO de la base de datos (mysqldump o exportación
--      completa desde phpMyAdmin). Sin excepción.
--   2. Ejecutar MANUALMENTE cada una de las 6 consultas de la sección
--      "PRECHECKS" a continuación, ANTES de continuar con el resto del
--      archivo.
--   3. Si CUALQUIERA de esas consultas devuelve un conteo mayor que 0
--      (o, en el caso de `security_mfa.test`, si el resultado no es
--      exactamente lo esperado por el entorno), DETENERSE. No continuar
--      con esta migración sin revisar manualmente esos datos primero —
--      podrían representar configuración SMS real que alguien intentó
--      usar, y esta migración los limpiaría irreversiblemente.
--   4. Esta migración fue verificada y ejecutada en el entorno de
--      desarrollo local de esta etapa con los 6 conteos en 0 (ver
--      resultados/etapa-6-1-eliminacion-sms-heredado-skeleton.txt,
--      sección de prechecks). Cada entorno adicional (staging,
--      producción) DEBE repetir esta misma verificación antes de
--      aplicar esta migración — un conteo en 0 en desarrollo NO
--      garantiza lo mismo en otro entorno con datos reales distintos.
--
-- Esta migración NO usa `IF NOT EXISTS`, `DROP COLUMN IF EXISTS` ni
-- `DROP TABLE IF EXISTS` — es intencionalmente explícita: si algo ya fue
-- eliminado o no existe, la sentencia correspondiente fallará de forma
-- visible en vez de fallar en silencio, para poder identificar con
-- claridad si esta migración ya se ejecutó antes.
--
-- ─────────────────────────────────────────────────────────────────────────────
-- PRECHECKS — EJECUTAR MANUALMENTE ANTES DE CONTINUAR (solo lectura)
-- ─────────────────────────────────────────────────────────────────────────────
--
-- 1. Verificar usuarios con SMS como método 2FA activo (debe ser 0):
--
-- SELECT COUNT(*) AS users_with_sms_method
-- FROM tbl_users
-- WHERE two_factor_method = 'sms';
--
-- 2. Verificar usuarios con teléfono MFA heredado (debe ser 0):
--
-- SELECT COUNT(*) AS users_with_two_factor_phone
-- FROM tbl_users
-- WHERE two_factor_phone IS NOT NULL
--   AND two_factor_phone <> '';
--
-- 3. Verificar códigos SMS activos/no usados/no vencidos (debe ser 0):
--
-- SELECT COUNT(*) AS active_sms_codes
-- FROM tbl_two_factor_codes
-- WHERE method = 'sms'
--   AND used = 0
--   AND expires_at >= NOW();
--
-- 4. Verificar códigos SMS totales, activos o no (informativo, se
--    invalidarán en el paso 1 de limpieza si hay alguno):
--
-- SELECT COUNT(*) AS total_sms_codes
-- FROM tbl_two_factor_codes
-- WHERE method = 'sms';
--
-- 5. Verificar configuración SMS activa (debe ser 0):
--
-- SELECT COUNT(*) AS active_sms_settings
-- FROM tbl_mfa_settings
-- WHERE sms_enabled = 1
--    OR sms_provider IS NOT NULL
--    OR sms_api_key IS NOT NULL
--    OR sms_api_secret_enc IS NOT NULL
--    OR sms_from IS NOT NULL
--    OR sms_endpoint IS NOT NULL
--    OR sms_extra_config IS NOT NULL;
--
-- 6. Verificar el permiso huérfano a eliminar (debe existir exactamente
--    1 fila, id=33 en el entorno de referencia; confirmar en el entorno
--    donde se ejecute):
--
-- SELECT id, slug
-- FROM tbl_permissions
-- WHERE slug = 'security_mfa.test';
--
-- Resultado obtenido en el entorno de desarrollo de esta etapa (ver TXT
-- de resultados para el detalle completo): los 5 conteos de las
-- consultas 1-5 fueron 0, y la consulta 6 devolvió exactamente 1 fila
-- (id=33, slug='security_mfa.test') — condiciones seguras para
-- continuar. Se ejecutó la migración completa en ese entorno.
--
-- ═════════════════════════════════════════════════════════════════════════════


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 1 — Invalidar cualquier código SMS remanente en tbl_two_factor_codes
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE tbl_two_factor_codes
SET used = 1
WHERE method = 'sms';


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 2 — Limpiar usuarios que hubieran quedado con método SMS heredado
-- (defensivo; ya debería estar limpio desde la migración 011)
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE tbl_users
SET
    two_factor_enabled    = 0,
    two_factor_method     = NULL,
    two_factor_secret_enc = NULL,
    two_factor_phone      = NULL
WHERE two_factor_method = 'sms';


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 3 — Limpiar cualquier teléfono MFA heredado restante, sin importar el
-- método (la columna two_factor_phone se elimina en el PASO 6; esto es
-- limpieza de datos previa a la eliminación de la columna).
-- NO afecta tbl_users.telefono (columna de contacto general, activa, distinta).
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE tbl_users
SET two_factor_phone = NULL
WHERE two_factor_phone IS NOT NULL;


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 4 — Limpiar configuración SMS remanente en tbl_mfa_settings
-- (defensivo; ya debería estar en NULL/0 desde la migración 011)
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE tbl_mfa_settings
SET
    sms_enabled        = 0,
    sms_provider       = NULL,
    sms_api_key        = NULL,
    sms_api_secret_enc = NULL,
    sms_from           = NULL,
    sms_endpoint       = NULL,
    sms_extra_config   = NULL;


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 5 — Retirar el valor 'sms' de los enum (MySQL 8.0 admite ENUM de un
-- solo valor sin problema; se verificó en el motor usado por este proyecto).
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE tbl_users
MODIFY two_factor_method enum('email','authenticator') NULL DEFAULT NULL;

ALTER TABLE tbl_two_factor_codes
MODIFY method enum('email') NOT NULL;


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 6 — Eliminar columnas SMS heredadas de tbl_mfa_settings.
-- Sentencias separadas (una por columna) para maxima compatibilidad con
-- phpMyAdmin y para identificar con claridad cual sentencia falla si esta
-- migracion se ejecuta dos veces por error.
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_enabled;
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_provider;
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_api_key;
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_api_secret_enc;
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_from;
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_endpoint;
ALTER TABLE tbl_mfa_settings DROP COLUMN sms_extra_config;


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 7 — Eliminar columna two_factor_phone de tbl_users.
-- NO elimina tbl_users.telefono (columna de contacto general, activa).
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE tbl_users DROP COLUMN two_factor_phone;


-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 8 — Eliminar el permiso huérfano security_mfa.test (sin ningún check
-- funcional en ningún controlador, ver Etapa 6). Primero sus relaciones,
-- luego el permiso. No se elimina ningún otro permiso.
-- ─────────────────────────────────────────────────────────────────────────────
DELETE FROM tbl_role_permissions
WHERE permission_id IN (
    SELECT id FROM (
        SELECT id FROM tbl_permissions WHERE slug = 'security_mfa.test'
    ) AS sms_permission
);

DELETE FROM tbl_permissions
WHERE slug = 'security_mfa.test';


-- ═════════════════════════════════════════════════════════════════════════════
-- POSTCHECKS — EJECUTAR MANUALMENTE DESPUÉS DE APLICAR ESTA MIGRACIÓN
-- ═════════════════════════════════════════════════════════════════════════════
--
-- 1. Confirmar que los enum ya no admiten 'sms':
--
-- SHOW COLUMNS FROM tbl_users LIKE 'two_factor_method';
-- SHOW COLUMNS FROM tbl_two_factor_codes LIKE 'method';
--
-- 2. Confirmar que las columnas SMS ya no existen (cada SHOW COLUMNS debe
--    devolver 0 filas):
--
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_enabled';
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_provider';
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_api_key';
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_api_secret_enc';
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_from';
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_endpoint';
-- SHOW COLUMNS FROM tbl_mfa_settings LIKE 'sms_extra_config';
-- SHOW COLUMNS FROM tbl_users LIKE 'two_factor_phone';
--
-- 3. Confirmar que el permiso huérfano fue eliminado (debe ser 0):
--
-- SELECT COUNT(*) AS remaining_sms_permission
-- FROM tbl_permissions
-- WHERE slug = 'security_mfa.test';
--
-- 4. Confirmar que no quedan role_permissions huérfanos para ese permiso
--    (debe ser 0):
--
-- SELECT COUNT(*) AS remaining_sms_role_permissions
-- FROM tbl_role_permissions rp
-- LEFT JOIN tbl_permissions p ON p.id = rp.permission_id
-- WHERE p.slug = 'security_mfa.test';
--
-- Resultado obtenido en el entorno de desarrollo de esta etapa: ver
-- resultados/etapa-6-1-eliminacion-sms-heredado-skeleton.txt, sección de
-- postchecks — todos los resultados fueron los esperados (columnas
-- ausentes, enum sin 'sms', permiso y relación eliminados).
-- ═════════════════════════════════════════════════════════════════════════════
