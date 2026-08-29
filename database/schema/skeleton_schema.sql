-- ============================================================
-- Skeleton PHP MVC - Esquema consolidado limpio
-- Version estable: v3.0.0
-- Fecha: 2026-05-13
--
-- Importar en una base de datos vacia desde phpMyAdmin/MySQL.
-- No incluye datos personales reales, secretos, tokens, logs,
-- auditoria historica, sesiones, notificaciones ni archivos subidos.
-- Usuario inicial: admin@example.com / Admin123*
-- Cambiar la contrasena inmediatamente despues del primer acceso.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_appearance_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `app_display_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_background_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sidebar_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_overlay_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_overlay_opacity` decimal(3,2) DEFAULT '0.40',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user_id` (`user_id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_entity` (`entity`),
  KEY `idx_audit_entity_id` (`entity_id`),
  KEY `idx_audit_status` (`status`),
  KEY `idx_audit_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_authentication_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `local_login_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `external_login_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `allow_auto_user_creation` tinyint(1) NOT NULL DEFAULT '0',
  `default_role_id` int DEFAULT NULL,
  `require_existing_user` tinyint(1) NOT NULL DEFAULT '1',
  `allow_account_linking` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `restrict_external_domains` tinyint(1) NOT NULL DEFAULT '0',
  `allowed_external_domains` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_authentication_settings_default_role_id` (`default_role_id`),
  CONSTRAINT `fk_authentication_settings_default_role` FOREIGN KEY (`default_role_id`) REFERENCES `tbl_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_email_change_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `current_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_change_user_id` (`user_id`),
  KEY `idx_email_change_new_email` (`new_email`),
  KEY `idx_email_change_expires_at` (`expires_at`),
  KEY `idx_email_change_used_at` (`used_at`),
  CONSTRAINT `fk_email_change_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_external_auth_providers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_secret` text COLLATE utf8mb4_unicode_ci,
  `tenant_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `authorization_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userinfo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_tested_at` datetime DEFAULT NULL,
  `last_test_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_test_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eap_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status_id` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_languages_code` (`code`),
  KEY `fk_languages_status` (`status_id`),
  CONSTRAINT `fk_languages_status` FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `failure_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_user_id` (`user_id`),
  KEY `idx_la_email` (`email`),
  KEY `idx_la_ip_address` (`ip_address`),
  KEY `idx_la_status` (`status`),
  KEY `idx_la_attempted_at` (`attempted_at`),
  CONSTRAINT `fk_la_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('success','failed','blocked') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_logs_user_id` (`user_id`),
  KEY `idx_login_logs_status` (`status`),
  KEY `idx_login_logs_created` (`created_at`),
  CONSTRAINT `fk_login_logs_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_login_security_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `failed_login_protection_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `max_failed_attempts_user` int NOT NULL DEFAULT '5',
  `user_attempt_window_minutes` int NOT NULL DEFAULT '15',
  `user_lockout_minutes` int NOT NULL DEFAULT '15',
  `ip_protection_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `max_failed_attempts_ip` int NOT NULL DEFAULT '20',
  `ip_attempt_window_minutes` int NOT NULL DEFAULT '15',
  `ip_lockout_minutes` int NOT NULL DEFAULT '30',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_mfa_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `authenticator_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_modules_slug` (`slug`),
  KEY `fk_modules_status` (`status_id`),
  CONSTRAINT `fk_modules_status` FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `read_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_read_at` (`read_at`),
  KEY `idx_notifications_created_at` (`created_at`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_deleted_at` (`deleted_at`),
  KEY `idx_notifications_user_deleted_read` (`user_id`,`deleted_at`,`read_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_password_histories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ph_user_id` (`user_id`),
  KEY `idx_ph_created_at` (`created_at`),
  CONSTRAINT `fk_ph_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_password_policies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `min_length` int NOT NULL DEFAULT '10',
  `require_uppercase` tinyint(1) NOT NULL DEFAULT '1',
  `require_lowercase` tinyint(1) NOT NULL DEFAULT '1',
  `require_number` tinyint(1) NOT NULL DEFAULT '1',
  `require_special` tinyint(1) NOT NULL DEFAULT '1',
  `prevent_email_in_password` tinyint(1) NOT NULL DEFAULT '1',
  `prevent_name_in_password` tinyint(1) NOT NULL DEFAULT '1',
  `prevent_common_passwords` tinyint(1) NOT NULL DEFAULT '1',
  `password_history_count` int NOT NULL DEFAULT '3',
  `password_expiration_days` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_token_hash` (`token_hash`),
  KEY `idx_pr_expires_at` (`expires_at`),
  KEY `idx_pr_used_at` (`used_at`),
  KEY `fk_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_slug` (`slug`),
  KEY `fk_permissions_module` (`module_id`),
  CONSTRAINT `fk_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `tbl_modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_security_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_lock_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = activado, 0 = desactivado',
  `session_inactivity_seconds` int NOT NULL DEFAULT '900' COMMENT 'Segundos de inactividad antes de bloquear (900 = 15 min)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_smtp_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint NOT NULL DEFAULT '587',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password_enc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `encryption` enum('tls','ssl','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tls',
  `from_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `from_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_tested_at` datetime DEFAULT NULL,
  `last_test_status` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_statuses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_statuses_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `release_year` year NOT NULL,
  `project_leader` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `system_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_two_factor_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 del código de 6 dígitos',
  `method` enum('email') COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint NOT NULL DEFAULT '0',
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_method` (`user_id`,`method`,`expires_at`),
  CONSTRAINT `fk_two_factor_codes_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_user_external_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `provider_id` int NOT NULL,
  `provider_user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uea_provider_user` (`provider_id`,`provider_user_id`),
  KEY `idx_uea_user_id` (`user_id`),
  KEY `idx_uea_provider_id` (`provider_id`),
  KEY `idx_uea_provider_email` (`provider_email`),
  CONSTRAINT `fk_uea_provider` FOREIGN KEY (`provider_id`) REFERENCES `tbl_external_auth_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_uea_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_user_manuals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `uploaded_by` int NOT NULL,
  `status_id` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_manuals_uploaded_by` (`uploaded_by`),
  KEY `fk_manuals_status` (`status_id`),
  CONSTRAINT `fk_manuals_status` FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_manuals_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `tbl_users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `browser` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revoked_by` int DEFAULT NULL,
  `revoke_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_sessions_hash` (`session_hash`),
  KEY `idx_user_sessions_user_id` (`user_id`),
  KEY `idx_user_sessions_revoked_at` (`revoked_at`),
  KEY `idx_user_sessions_last_activity` (`last_activity_at`),
  KEY `fk_user_sessions_revoked_by` (`revoked_by`),
  CONSTRAINT `fk_user_sessions_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_id` int NOT NULL DEFAULT '1',
  `role_id` int NOT NULL DEFAULT '2',
  `failed_login_attempts` int NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_preference` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `language_id` int DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_method` enum('email','authenticator') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret_enc` text COLLATE utf8mb4_unicode_ci,
  `last_failed_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `fk_users_status_id` (`status_id`),
  KEY `fk_users_role_id` (`role_id`),
  KEY `fk_users_language_id` (`language_id`),
  CONSTRAINT `fk_users_language_id` FOREIGN KEY (`language_id`) REFERENCES `tbl_languages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_status_id` FOREIGN KEY (`status_id`) REFERENCES `tbl_statuses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ------------------------------------------------------------
-- Catalogos, modulos, permisos y roles base
-- ------------------------------------------------------------
INSERT INTO `tbl_statuses` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES (1,'Active','active','2026-05-09 17:40:17',NULL),(2,'Inactive','inactive','2026-05-09 17:40:17',NULL),(3,'Blocked','blocked','2026-05-09 17:40:17',NULL);
INSERT INTO `tbl_languages` (`id`, `name`, `native_name`, `code`, `is_default`, `status_id`, `created_at`, `updated_at`) VALUES (1,'Español','Español','es',1,1,'2026-05-09 17:40:22',NULL),(2,'English','English','en',0,1,'2026-05-09 17:40:22',NULL);
INSERT INTO `tbl_roles` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES (1,'Administrador','administrator','2026-05-09 17:40:17',NULL),(2,'Usuario','user','2026-05-09 17:40:17',NULL),(3,'Consultor','consultant','2026-05-09 17:40:17',NULL);
INSERT INTO `tbl_modules` (`id`, `name`, `slug`, `description`, `icon`, `route`, `sort_order`, `status_id`, `created_at`, `updated_at`) VALUES (1,'Dashboard','dashboard','Panel principal del sistema','ph-duotone ph-gauge','/dashboard',1,1,'2026-05-09 17:40:35',NULL),(2,'Mi Perfil','profile','Perfil personal del usuario','ph-duotone ph-user-circle','/profile',2,1,'2026-05-09 17:40:35',NULL),(3,'Mi Cuenta','account','Credenciales de acceso','ph-duotone ph-gear','/account',3,1,'2026-05-09 17:40:35',NULL),(4,'Usuarios','users','Gestión de usuarios del sistema','ph-duotone ph-users-three','/users',4,1,'2026-05-09 17:40:35',NULL),(5,'Idiomas','languages','Gestión de idiomas del sistema','ph-duotone ph-translate','/languages',5,1,'2026-05-09 17:40:35',NULL),(6,'Información del Sistema','system_information','Datos e información general del sistema','ph-duotone ph-info','/system-information',6,1,'2026-05-09 17:40:35',NULL),(7,'Manuales','manuals','Manuales de usuario','ph-duotone ph-file-text','/system-information',7,1,'2026-05-09 17:40:35',NULL),(8,'Seguridad / Sesiones','security_sessions','Configuración de bloqueo de sesión','ph-duotone ph-lock-key','/security/sessions',8,1,'2026-05-09 17:40:35',NULL),(9,'Roles y Permisos','roles_permissions','Gestión de permisos por rol','ph-duotone ph-shield-check','/roles-permissions',9,1,'2026-05-09 17:40:35',NULL),(10,'Auditoría','audit_logs','Registros de auditoría del sistema','ph-duotone ph-clipboard-text','/audit-logs',10,1,'2026-05-09 17:40:41',NULL),(11,'Seguridad / SMTP','security_smtp','Configuración SMTP del sistema','ph-duotone ph-envelope','/security/smtp',10,1,'2026-05-09 17:40:51',NULL),(12,'MFA','security_mfa','Configuración global de autenticación multifactor','ph-duotone ph-shield-plus','/security/mfa',11,1,'2026-05-09 17:40:55',NULL),(13,'Intentos','security_attempts','Configuración de protección contra intentos fallidos de login','ph-duotone ph-shield-warning','/security/attempts',12,1,'2026-05-09 19:22:14',NULL),(14,'Autenticación','security_authentication','Configuración de métodos de inicio de sesión: local y proveedores externos OAuth','ph-duotone ph-sign-in','/security/authentication',11,1,'2026-05-10 02:18:21',NULL),(15,'Apariencia','appearance','Configuración visual del sistema: logo, favicon, fondo de login y colores','ph-duotone ph-palette','/appearance',12,1,'2026-05-10 06:35:00',NULL),(16,'Política de Contraseñas','security_password_policy','Configuración de reglas de seguridad para contraseñas del sistema','ph-duotone ph-password','/security/password-policy',13,1,'2026-05-11 05:23:19',NULL),(17,'Salud del Sistema','system_health','Panel tecnico de verificaciones del sistema','ph-duotone ph-heartbeat','/system-health',12,1,'2026-05-13 03:02:49',NULL),(18,'Mantenimiento','maintenance','Herramientas administrativas de mantenimiento','ph-duotone ph-broom','/maintenance/cleanup',13,1,'2026-05-13 04:19:34',NULL),(19,'Notificaciones','notifications','Notificaciones internas del usuario','ph-duotone ph-bell','/notifications',14,1,'2026-05-13 04:37:58',NULL);
INSERT INTO `tbl_permissions` (`id`, `module_id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES (1,1,'Ver Dashboard','dashboard.view','Acceso al panel principal','2026-05-09 17:40:35',NULL),(2,2,'Ver Perfil','profile.view','Ver información del perfil','2026-05-09 17:40:35',NULL),(3,2,'Editar Perfil','profile.edit','Editar información del perfil','2026-05-09 17:40:35',NULL),(4,3,'Ver Cuenta','account.view','Ver configuración de cuenta','2026-05-09 17:40:35',NULL),(5,3,'Editar Cuenta','account.edit','Editar correo y contraseña','2026-05-09 17:40:35',NULL),(6,4,'Ver Usuarios','users.view','Ver lista de usuarios','2026-05-09 17:40:35',NULL),(7,4,'Crear Usuarios','users.create','Crear nuevos usuarios','2026-05-09 17:40:35',NULL),(8,4,'Editar Usuarios','users.edit','Editar usuarios existentes','2026-05-09 17:40:35',NULL),(9,4,'Eliminar Usuarios','users.delete','Inactivar usuarios','2026-05-09 17:40:35',NULL),(10,5,'Ver Idiomas','languages.view','Ver lista de idiomas','2026-05-09 17:40:35',NULL),(11,5,'Crear Idiomas','languages.create','Crear nuevos idiomas','2026-05-09 17:40:35',NULL),(12,5,'Editar Idiomas','languages.edit','Editar idiomas existentes','2026-05-09 17:40:35',NULL),(13,5,'Activar Idiomas','languages.activate','Activar idiomas del sistema','2026-05-09 17:40:35',NULL),(14,5,'Desactivar Idiomas','languages.deactivate','Desactivar idiomas del sistema','2026-05-09 17:40:35',NULL),(15,6,'Ver Información del Sistema','system_information.view','Ver datos del sistema','2026-05-09 17:40:35',NULL),(16,6,'Editar Información del Sistema','system_information.edit','Editar datos del sistema','2026-05-09 17:40:35',NULL),(17,7,'Ver Manuales','manuals.view','Ver y descargar manuales','2026-05-09 17:40:35',NULL),(18,7,'Subir Manuales','manuals.upload','Subir nuevos manuales','2026-05-09 17:40:35',NULL),(19,7,'Editar Manuales','manuals.edit','Editar datos de manuales','2026-05-09 17:40:35',NULL),(20,7,'Activar Manuales','manuals.activate','Activar manuales','2026-05-09 17:40:35',NULL),(21,7,'Desactivar Manuales','manuals.deactivate','Desactivar manuales','2026-05-09 17:40:35',NULL),(22,8,'Ver Seguridad / Sesiones','security_sessions.view','Ver configuración de sesión','2026-05-09 17:40:35',NULL),(23,8,'Editar Seguridad / Sesiones','security_sessions.edit','Editar configuración de sesión','2026-05-09 17:40:35',NULL),(24,9,'Ver Roles y Permisos','roles_permissions.view','Ver módulo de roles y permisos','2026-05-09 17:40:35',NULL),(25,9,'Editar Roles y Permisos','roles_permissions.edit','Editar permisos de roles','2026-05-09 17:40:35',NULL),(26,10,'Ver Auditoría','audit_logs.view','Ver listado de registros de auditoría','2026-05-09 17:40:41',NULL),(27,10,'Ver Detalle Auditoría','audit_logs.show','Ver detalle de un registro de auditoría','2026-05-09 17:40:41',NULL),(28,11,'Ver SMTP','security_smtp.view','Ver configuración SMTP','2026-05-09 17:40:51',NULL),(29,11,'Editar SMTP','security_smtp.edit','Editar configuración SMTP','2026-05-09 17:40:51',NULL),(30,11,'Probar SMTP','security_smtp.test','Enviar correo de prueba SMTP','2026-05-09 17:40:51',NULL),(31,12,'Ver Configuración MFA','security_mfa.view','Acceso de lectura al módulo MFA','2026-05-09 17:40:55',NULL),(32,12,'Editar Configuración MFA','security_mfa.edit','Guardar configuración global MFA','2026-05-09 17:40:55',NULL),(34,4,'Desbloquear Usuario','users.unlock','Permite desbloquear cuentas de usuario bloqueadas por intentos fallidos','2026-05-09 17:41:12',NULL),(35,13,'Ver Intentos fallidos','security_attempts.view','Permite ver la configuración de intentos fallidos de login','2026-05-09 19:22:14',NULL),(36,13,'Editar Intentos fallidos','security_attempts.edit','Permite editar la configuración de intentos fallidos de login','2026-05-09 19:22:14',NULL),(37,14,'Ver Autenticación','security_authentication.view','Permite ver la configuración de métodos de autenticación','2026-05-10 02:18:21',NULL),(38,14,'Editar configuración general','security_authentication.edit','Permite editar la configuración general de autenticación','2026-05-10 02:18:21',NULL),(39,14,'Configurar proveedores','security_authentication.providers_create','Permite configurar proveedores de autenticación externos','2026-05-10 02:18:21',NULL),(40,14,'Editar proveedores','security_authentication.providers_edit','Permite editar la configuración de proveedores externos','2026-05-10 02:18:21',NULL),(41,14,'Eliminar proveedores','security_authentication.providers_delete','Permite restablecer o eliminar proveedores externos','2026-05-10 02:18:21',NULL),(42,14,'Probar proveedores','security_authentication.providers_test','Permite probar la configuración de proveedores externos','2026-05-10 02:18:21',NULL),(43,15,'Ver Apariencia','appearance.view','Permite ver la configuración de apariencia del sistema','2026-05-10 06:35:00',NULL),(44,15,'Editar Apariencia','appearance.edit','Permite modificar la configuración de apariencia del sistema','2026-05-10 06:35:00',NULL),(45,15,'Restablecer Apariencia','appearance.reset','Permite restablecer elementos de apariencia a sus valores por defecto','2026-05-10 06:35:00',NULL),(46,16,'Ver Política de Contraseñas','security_password_policy.view','Permite ver la configuración de política de contraseñas','2026-05-11 05:23:19',NULL),(47,16,'Editar Política de Contraseñas','security_password_policy.edit','Permite editar la configuración de política de contraseñas','2026-05-11 05:23:19',NULL),(48,7,'Eliminar Manuales','manuals.delete','Eliminar manuales mediante borrado logico','2026-05-12 00:22:28',NULL),(49,4,'Exportar Usuarios','users.export','Exportar listado de usuarios en formato Excel','2026-05-12 00:39:58',NULL),(50,10,'Exportar Auditoria','audit_logs.export','Exportar registros de auditoria en formato Excel','2026-05-12 00:39:58',NULL),(51,3,'Ver sesiones propias','account.sessions.view','Ver sesiones activas propias','2026-05-12 16:00:51',NULL),(52,3,'Cerrar sesiones propias','account.sessions.revoke','Cerrar sesiones activas propias','2026-05-12 16:00:51',NULL),(53,8,'Ver sesiones activas','security_sessions.view_active','Ver sesiones activas de usuarios','2026-05-12 16:00:51',NULL),(54,8,'Cerrar sesion activa','security_sessions.revoke','Cerrar una sesion activa','2026-05-12 16:00:51',NULL),(55,8,'Cerrar sesiones de usuario','security_sessions.revoke_user_all','Cerrar todas las sesiones activas de un usuario','2026-05-12 16:00:51',NULL),(56,3,'Ver historial de sesiones propias','account.sessions.history','Consultar historial propio de sesiones','2026-05-12 16:34:41',NULL),(57,4,'Ver historial de sesiones de usuarios','users.sessions.view','Consultar historial de sesiones de usuarios','2026-05-12 16:34:41',NULL),(58,4,'Cerrar sesion desde historial','users.sessions.revoke','Cerrar una sesion activa desde historial de usuarios','2026-05-12 16:34:41',NULL),(59,4,'Cerrar todas las sesiones desde historial','users.sessions.revoke_all','Cerrar sesiones activas de un usuario desde historial','2026-05-12 16:34:41',NULL),(60,17,'Ver Salud del Sistema','system_health.view','Acceso al panel de salud del sistema','2026-05-13 03:02:49',NULL),(61,18,'Ver limpieza de datos temporales','maintenance.cleanup.view','Ver resumen de datos temporales limpiables','2026-05-13 04:19:34',NULL),(62,18,'Ejecutar limpieza de datos temporales','maintenance.cleanup.run','Ejecutar limpieza controlada de datos temporales','2026-05-13 04:19:34',NULL),(63,19,'Ver notificaciones','notifications.view','Ver notificaciones internas propias','2026-05-13 04:37:58',NULL),(64,19,'Marcar notificaciones como leidas','notifications.mark_read','Marcar notificaciones internas propias como leidas','2026-05-13 04:37:58',NULL);
INSERT INTO `tbl_role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES (32,2,5,'2026-05-09 17:40:35'),(33,2,4,'2026-05-09 17:40:35'),(34,2,1,'2026-05-09 17:40:35'),(35,2,17,'2026-05-09 17:40:35'),(36,2,3,'2026-05-09 17:40:35'),(37,2,2,'2026-05-09 17:40:35'),(38,2,15,'2026-05-09 17:40:35'),(39,3,5,'2026-05-09 17:40:35'),(40,3,4,'2026-05-09 17:40:35'),(41,3,1,'2026-05-09 17:40:35'),(42,3,17,'2026-05-09 17:40:35'),(43,3,3,'2026-05-09 17:40:35'),(44,3,2,'2026-05-09 17:40:35'),(45,3,15,'2026-05-09 17:40:35'),(298,3,51,'2026-05-12 16:00:51'),(299,3,52,'2026-05-12 16:00:51'),(300,2,51,'2026-05-12 16:00:51'),(301,2,52,'2026-05-12 16:00:51'),(307,3,56,'2026-05-12 16:34:41'),(308,2,56,'2026-05-12 16:34:41'),(312,1,1,'2026-05-13 02:24:05'),(313,1,2,'2026-05-13 02:24:05'),(314,1,3,'2026-05-13 02:24:05'),(315,1,4,'2026-05-13 02:24:05'),(316,1,5,'2026-05-13 02:24:05'),(317,1,51,'2026-05-13 02:24:05'),(318,1,52,'2026-05-13 02:24:05'),(319,1,56,'2026-05-13 02:24:05'),(320,1,6,'2026-05-13 02:24:05'),(321,1,7,'2026-05-13 02:24:05'),(322,1,8,'2026-05-13 02:24:05'),(323,1,9,'2026-05-13 02:24:05'),(324,1,34,'2026-05-13 02:24:05'),(325,1,49,'2026-05-13 02:24:05'),(326,1,57,'2026-05-13 02:24:05'),(327,1,58,'2026-05-13 02:24:05'),(328,1,59,'2026-05-13 02:24:05'),(329,1,10,'2026-05-13 02:24:05'),(330,1,11,'2026-05-13 02:24:05'),(331,1,12,'2026-05-13 02:24:05'),(332,1,13,'2026-05-13 02:24:05'),(333,1,14,'2026-05-13 02:24:05'),(334,1,15,'2026-05-13 02:24:05'),(335,1,16,'2026-05-13 02:24:05'),(336,1,17,'2026-05-13 02:24:05'),(337,1,18,'2026-05-13 02:24:05'),(338,1,19,'2026-05-13 02:24:05'),(339,1,20,'2026-05-13 02:24:05'),(340,1,21,'2026-05-13 02:24:05'),(341,1,48,'2026-05-13 02:24:05'),(342,1,22,'2026-05-13 02:24:05'),(343,1,23,'2026-05-13 02:24:05'),(344,1,53,'2026-05-13 02:24:05'),(345,1,54,'2026-05-13 02:24:05'),(346,1,55,'2026-05-13 02:24:05'),(347,1,24,'2026-05-13 02:24:05'),(348,1,25,'2026-05-13 02:24:05'),(349,1,26,'2026-05-13 02:24:05'),(350,1,27,'2026-05-13 02:24:05'),(351,1,50,'2026-05-13 02:24:05'),(352,1,28,'2026-05-13 02:24:05'),(353,1,29,'2026-05-13 02:24:05'),(354,1,30,'2026-05-13 02:24:05'),(355,1,31,'2026-05-13 02:24:05'),(356,1,32,'2026-05-13 02:24:05'),(358,1,35,'2026-05-13 02:24:05'),(359,1,36,'2026-05-13 02:24:05'),(360,1,37,'2026-05-13 02:24:05'),(361,1,38,'2026-05-13 02:24:05'),(362,1,39,'2026-05-13 02:24:05'),(363,1,40,'2026-05-13 02:24:05'),(364,1,41,'2026-05-13 02:24:05'),(365,1,42,'2026-05-13 02:24:05'),(366,1,43,'2026-05-13 02:24:05'),(367,1,44,'2026-05-13 02:24:05'),(368,1,45,'2026-05-13 02:24:05'),(369,1,46,'2026-05-13 02:24:05'),(370,1,47,'2026-05-13 02:24:05'),(371,1,60,'2026-05-13 03:02:49'),(372,1,62,'2026-05-13 04:19:34'),(373,1,61,'2026-05-13 04:19:34'),(375,1,63,'2026-05-13 04:37:58'),(376,1,64,'2026-05-13 04:37:58'),(377,3,63,'2026-05-13 04:37:58'),(378,3,64,'2026-05-13 04:37:58'),(379,2,63,'2026-05-13 04:37:58'),(380,2,64,'2026-05-13 04:37:58');

-- ------------------------------------------------------------
-- Configuracion base limpia
-- ------------------------------------------------------------
INSERT INTO `tbl_appearance_settings` (`id`, `app_display_name`, `app_tagline`, `logo_path`, `favicon_path`, `login_background_path`, `primary_color`, `sidebar_color`, `login_overlay_color`, `login_overlay_opacity`, `created_at`, `updated_at`) VALUES
(1, 'Skeleton', 'Sistema administrativo base', NULL, NULL, NULL, NULL, NULL, NULL, 0.40, CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_security_settings` (`id`, `session_lock_enabled`, `session_inactivity_seconds`, `created_at`, `updated_at`) VALUES
(1, 1, 900, CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_smtp_settings` (`id`, `host`, `port`, `username`, `password_enc`, `encryption`, `from_address`, `from_name`, `is_verified`, `last_tested_at`, `last_test_status`, `created_at`, `updated_at`) VALUES
(1, '', 587, '', '', 'tls', '', 'Skeleton', 0, NULL, NULL, CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_mfa_settings` (`id`, `email_enabled`, `authenticator_enabled`, `created_at`, `updated_at`) VALUES
(1, 0, 1, CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_login_security_settings` (`id`, `failed_login_protection_enabled`, `max_failed_attempts_user`, `user_attempt_window_minutes`, `user_lockout_minutes`, `ip_protection_enabled`, `max_failed_attempts_ip`, `ip_attempt_window_minutes`, `ip_lockout_minutes`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 15, 15, 1, 20, 15, 30, CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_authentication_settings` (`id`, `local_login_enabled`, `external_login_enabled`, `allow_auto_user_creation`, `default_role_id`, `require_existing_user`, `allow_account_linking`, `created_at`, `updated_at`, `restrict_external_domains`, `allowed_external_domains`) VALUES
(1, 1, 0, 0, NULL, 1, 1, CURRENT_TIMESTAMP, NULL, 0, NULL);

INSERT INTO `tbl_password_policies` (`id`, `is_enabled`, `min_length`, `require_uppercase`, `require_lowercase`, `require_number`, `require_special`, `prevent_email_in_password`, `prevent_name_in_password`, `prevent_common_passwords`, `password_history_count`, `password_expiration_days`, `created_at`, `updated_at`) VALUES
(1, 1, 10, 1, 1, 1, 1, 1, 1, 1, 3, 90, CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_system_settings` (`id`, `release_year`, `project_leader`, `system_version`, `created_at`, `updated_at`) VALUES
(1, 2026, 'Administrador', 'v3.0.0', CURRENT_TIMESTAMP, NULL);

INSERT INTO `tbl_external_auth_providers` (`id`, `name`, `slug`, `client_id`, `client_secret`, `tenant_id`, `redirect_uri`, `scopes`, `authorization_url`, `token_url`, `userinfo_url`, `is_enabled`, `is_verified`, `last_tested_at`, `last_test_status`, `last_test_message`, `created_at`, `updated_at`) VALUES
(1, 'Google', 'google', NULL, NULL, NULL, 'http://localhost/skeleton/public/auth/external/google/callback', 'openid email profile', 'https://accounts.google.com/o/oauth2/v2/auth', 'https://oauth2.googleapis.com/token', 'https://www.googleapis.com/oauth2/v3/userinfo', 0, 0, NULL, NULL, NULL, CURRENT_TIMESTAMP, NULL),
(2, 'Microsoft 365', 'microsoft', NULL, NULL, 'common', 'http://localhost/skeleton/public/auth/external/microsoft/callback', 'openid email profile User.Read', 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/authorize', 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token', 'https://graph.microsoft.com/v1.0/me', 0, 0, NULL, NULL, NULL, CURRENT_TIMESTAMP, NULL),
(3, 'GitHub', 'github', NULL, NULL, NULL, 'http://localhost/skeleton/public/auth/external/github/callback', 'read:user user:email', 'https://github.com/login/oauth/authorize', 'https://github.com/login/oauth/access_token', 'https://api.github.com/user', 0, 0, NULL, NULL, NULL, CURRENT_TIMESTAMP, NULL);

-- Usuario administrador inicial temporal.
-- Password temporal: Admin123*
INSERT INTO `tbl_users` (`id`, `nombres`, `apellidos`, `telefono`, `direccion`, `email`, `password`, `status_id`, `role_id`, `failed_login_attempts`, `locked_until`, `last_login_at`, `last_login_ip`, `remember_token`, `profile_image`, `theme_preference`, `language_id`, `password_changed_at`, `force_password_change`, `created_at`, `updated_at`, `two_factor_enabled`, `two_factor_method`, `two_factor_secret_enc`, `last_failed_login_at`) VALUES
(1, 'Administrador', 'Sistema', NULL, NULL, 'admin@example.com', '$2y$12$uNrWBnGKReMBxkKECMC2BOtcRHUYq/.FzqHHKUMdeUHtqMcQmD1W2', 1, 1, 0, NULL, NULL, NULL, NULL, NULL, 'light', 1, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, NULL, 0, NULL, NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;
