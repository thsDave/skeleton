-- ─────────────────────────────────────────────────────────────────────────────
-- Migración 011 — Eliminar SMS de MFA
-- SMS ya no es un método soportado. Se limpian datos sensibles y se
-- deshabilita sms_enabled. Las columnas se conservan para no romper
-- migraciones anteriores, pero el sistema las ignora completamente.
-- ─────────────────────────────────────────────────────────────────────────────

-- Forzar sms_enabled = 0 y limpiar datos sensibles SMS
UPDATE `tbl_mfa_settings` SET
    `sms_enabled`        = 0,
    `sms_provider`       = NULL,
    `sms_api_key`        = NULL,
    `sms_api_secret_enc` = NULL,
    `sms_from`           = NULL,
    `sms_endpoint`       = NULL,
    `sms_extra_config`   = NULL
WHERE id = 1;

-- Deshabilitar 2FA de usuarios que tenían method = 'sms' configurado.
-- Esos usuarios deberán volver a configurar su 2FA con email o autenticador.
UPDATE `tbl_users` SET
    `two_factor_enabled`    = 0,
    `two_factor_method`     = NULL,
    `two_factor_secret_enc` = NULL,
    `two_factor_phone`      = NULL
WHERE `two_factor_method` = 'sms';

-- Invalidar códigos OTP de SMS pendientes (ya no se usarán)
UPDATE `tbl_two_factor_codes` SET `used` = 1
WHERE `method` = 'sms' AND `used` = 0;
