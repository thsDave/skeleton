-- ============================================================
-- 014 – Módulo Seguridad > Autenticación (métodos de login)
-- Importar en phpMyAdmin después de la migración 013.
-- Todos los INSERT usan IGNORE: seguro ejecutar más de una vez.
-- CREATE TABLE IF NOT EXISTS: seguro ejecutar más de una vez.
-- ============================================================

-- 1. Configuración general de autenticación (fila única, id=1)
CREATE TABLE IF NOT EXISTS `tbl_authentication_settings` (
    `id`                      INT        NOT NULL AUTO_INCREMENT,
    `local_login_enabled`     TINYINT(1) NOT NULL DEFAULT 1,
    `external_login_enabled`  TINYINT(1) NOT NULL DEFAULT 0,
    `allow_auto_user_creation`TINYINT(1) NOT NULL DEFAULT 0,
    `default_role_id`         INT        NULL DEFAULT NULL,
    `require_existing_user`   TINYINT(1) NOT NULL DEFAULT 1,
    `allow_account_linking`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`              TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP  NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tbl_authentication_settings`
    (`id`, `local_login_enabled`, `external_login_enabled`,
     `allow_auto_user_creation`, `require_existing_user`, `allow_account_linking`)
VALUES (1, 1, 0, 0, 1, 1);

-- 2. Proveedores de autenticación externos
CREATE TABLE IF NOT EXISTS `tbl_external_auth_providers` (
    `id`                INT          NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(100) NOT NULL,
    `slug`              VARCHAR(50)  NOT NULL,
    `client_id`         VARCHAR(255) NULL DEFAULT NULL,
    `client_secret`     TEXT         NULL DEFAULT NULL,
    `tenant_id`         VARCHAR(255) NULL DEFAULT NULL,
    `redirect_uri`      VARCHAR(255) NULL DEFAULT NULL,
    `scopes`            TEXT         NULL DEFAULT NULL,
    `authorization_url` VARCHAR(255) NULL DEFAULT NULL,
    `token_url`         VARCHAR(255) NULL DEFAULT NULL,
    `userinfo_url`      VARCHAR(255) NULL DEFAULT NULL,
    `is_enabled`        TINYINT(1)   NOT NULL DEFAULT 0,
    `is_verified`       TINYINT(1)   NOT NULL DEFAULT 0,
    `last_tested_at`    DATETIME     NULL DEFAULT NULL,
    `last_test_status`  VARCHAR(50)  NULL DEFAULT NULL,
    `last_test_message` TEXT         NULL DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_eap_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Proveedores iniciales (INSERT IGNORE: no sobrescribe si ya existen)
INSERT IGNORE INTO `tbl_external_auth_providers`
    (`name`, `slug`, `authorization_url`, `token_url`, `userinfo_url`, `scopes`, `is_enabled`, `is_verified`)
VALUES
    ('Google',
     'google',
     'https://accounts.google.com/o/oauth2/v2/auth',
     'https://oauth2.googleapis.com/token',
     'https://www.googleapis.com/oauth2/v3/userinfo',
     'openid email profile',
     0, 0),
    ('Microsoft 365',
     'microsoft',
     'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/authorize',
     'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token',
     'https://graph.microsoft.com/v1.0/me',
     'openid email profile User.Read',
     0, 0),
    ('GitHub',
     'github',
     'https://github.com/login/oauth/authorize',
     'https://github.com/login/oauth/access_token',
     'https://api.github.com/user',
     'read:user user:email',
     0, 0);

-- 3. Vínculos usuario ↔ proveedor externo
CREATE TABLE IF NOT EXISTS `tbl_user_external_accounts` (
    `id`               INT          NOT NULL AUTO_INCREMENT,
    `user_id`          INT          NOT NULL,
    `provider_id`      INT          NOT NULL,
    `provider_user_id` VARCHAR(255) NOT NULL,
    `provider_email`   VARCHAR(150) NULL DEFAULT NULL,
    `provider_name`    VARCHAR(150) NULL DEFAULT NULL,
    `avatar_url`       VARCHAR(255) NULL DEFAULT NULL,
    `linked_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login_at`    DATETIME     NULL DEFAULT NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_uea_provider_user` (`provider_id`, `provider_user_id`),
    INDEX `idx_uea_user_id`        (`user_id`),
    INDEX `idx_uea_provider_id`    (`provider_id`),
    INDEX `idx_uea_provider_email` (`provider_email`),
    CONSTRAINT `fk_uea_user`
        FOREIGN KEY (`user_id`)     REFERENCES `tbl_users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_uea_provider`
        FOREIGN KEY (`provider_id`) REFERENCES `tbl_external_auth_providers` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Módulo security_authentication en tbl_modules
INSERT IGNORE INTO `tbl_modules`
    (`name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`)
VALUES
    ('Autenticación',
     'security_authentication',
     'Configuración de métodos de inicio de sesión: local y proveedores externos OAuth',
     'ph-duotone ph-sign-in',
     '/security/authentication',
     11,
     1);

-- 5. Permisos del módulo
INSERT IGNORE INTO `tbl_permissions`
    (`module_id`, `name`, `slug`, `description`)
VALUES
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_authentication' LIMIT 1),
     'Ver Autenticación',
     'security_authentication.view',
     'Permite ver la configuración de métodos de autenticación'),
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_authentication' LIMIT 1),
     'Editar configuración general',
     'security_authentication.edit',
     'Permite editar la configuración general de autenticación'),
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_authentication' LIMIT 1),
     'Configurar proveedores',
     'security_authentication.providers_create',
     'Permite configurar proveedores de autenticación externos'),
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_authentication' LIMIT 1),
     'Editar proveedores',
     'security_authentication.providers_edit',
     'Permite editar la configuración de proveedores externos'),
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_authentication' LIMIT 1),
     'Eliminar proveedores',
     'security_authentication.providers_delete',
     'Permite restablecer o eliminar proveedores externos'),
    ((SELECT `id` FROM `tbl_modules` WHERE `slug` = 'security_authentication' LIMIT 1),
     'Probar proveedores',
     'security_authentication.providers_test',
     'Permite probar la configuración de proveedores externos');

-- 6. Asignar permisos al Administrador (role_id = 1)
INSERT IGNORE INTO `tbl_role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `tbl_permissions`
WHERE `slug` IN (
    'security_authentication.view',
    'security_authentication.edit',
    'security_authentication.providers_create',
    'security_authentication.providers_edit',
    'security_authentication.providers_delete',
    'security_authentication.providers_test'
);
