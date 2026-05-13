-- ─────────────────────────────────────────────────────────────────────────────
-- Migración 010 — Verificación en 2 pasos por usuario
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `tbl_users`
    ADD COLUMN `two_factor_enabled`    TINYINT(1)                              NOT NULL DEFAULT 0   AFTER `updated_at`,
    ADD COLUMN `two_factor_method`     ENUM('email','sms','authenticator')     NULL                 AFTER `two_factor_enabled`,
    ADD COLUMN `two_factor_secret_enc` TEXT                                    NULL                 AFTER `two_factor_method`,
    ADD COLUMN `two_factor_phone`      VARCHAR(30)                             NULL                 AFTER `two_factor_secret_enc`;

CREATE TABLE IF NOT EXISTS `tbl_two_factor_codes` (
    `id`         INT         NOT NULL AUTO_INCREMENT,
    `user_id`    INT         NOT NULL,
    `code_hash`  VARCHAR(64) NOT NULL COMMENT 'SHA-256 del código de 6 dígitos',
    `method`     ENUM('email','sms') NOT NULL,
    `expires_at` DATETIME    NOT NULL,
    `attempts`   TINYINT     NOT NULL DEFAULT 0,
    `used`       TINYINT(1)  NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_method` (`user_id`, `method`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
