-- ============================================================
-- Base de datos: db_skeleton
-- Sistema MVC con DashboardKit
-- ============================================================

CREATE DATABASE IF NOT EXISTS `db_skeleton`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_skeleton`;

-- ------------------------------------------------------------
-- Tabla: tbl_users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_users` (
  `id`                    INT            NOT NULL AUTO_INCREMENT,
  `nombres`               VARCHAR(100)   NOT NULL,
  `apellidos`             VARCHAR(100)   NOT NULL,
  `telefono`              VARCHAR(25)    NULL DEFAULT NULL,
  `direccion`             VARCHAR(255)   NULL DEFAULT NULL,
  `email`                 VARCHAR(150)   NOT NULL,
  `password`              VARCHAR(255)   NOT NULL,
  `status`                ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `failed_login_attempts` INT            NOT NULL DEFAULT 0,
  `locked_until`          DATETIME       NULL DEFAULT NULL,
  `last_login_at`         DATETIME       NULL DEFAULT NULL,
  `last_login_ip`         VARCHAR(45)    NULL DEFAULT NULL,
  `remember_token`        VARCHAR(255)   NULL DEFAULT NULL,
  `password_changed_at`   DATETIME       NULL DEFAULT NULL,
  `created_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP      NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: tbl_login_logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_login_logs` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `user_id`     INT           NULL DEFAULT NULL,
  `email`       VARCHAR(150)  NULL DEFAULT NULL,
  `ip_address`  VARCHAR(45)   NULL DEFAULT NULL,
  `user_agent`  VARCHAR(255)  NULL DEFAULT NULL,
  `status`      ENUM('success','failed','blocked') NOT NULL,
  `message`     VARCHAR(255)  NULL DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_logs_user_id` (`user_id`),
  KEY `idx_login_logs_status`  (`status`),
  KEY `idx_login_logs_created` (`created_at`),
  CONSTRAINT `fk_login_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Usuario inicial de prueba
-- Email:    admin@skeleton.local
-- Password: Admin123*  (hash bcrypt generado en PHP 8.3)
-- ------------------------------------------------------------
INSERT INTO `tbl_users`
  (`nombres`, `apellidos`, `email`, `password`, `status`, `created_at`)
VALUES (
  'Administrador',
  'Sistema',
  'admin@skeleton.local',
  '$2y$12$qqvvrou/2T8h6NRnxYpKPOmyeUlMobbn1bziko04jr35uQ0nZUA1i',
  'active',
  NOW()
);

-- Hash generado con: password_hash('Admin123*', PASSWORD_BCRYPT, ['cost' => 12])
