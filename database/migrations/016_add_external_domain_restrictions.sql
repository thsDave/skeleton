-- =============================================================================
-- 016 — Restricción de login externo por dominio institucional
-- =============================================================================
-- IMPORTANTE: Ejecuta estas dos sentencias SOLO si las columnas
-- restrict_external_domains y allowed_external_domains NO existen aún en
-- tbl_authentication_settings. Verifica en phpMyAdmin > Estructura de tabla
-- antes de ejecutar.
-- =============================================================================

ALTER TABLE tbl_authentication_settings
    ADD COLUMN restrict_external_domains TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE tbl_authentication_settings
    ADD COLUMN allowed_external_domains TEXT NULL;
