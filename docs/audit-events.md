# Auditoria de eventos criticos

Fecha de revision: 2026-05-12

## Servicio central

La auditoria se registra con `Core\Audit::log()` en `tbl_audit_logs`. El servicio completa automaticamente:

- `user_id` desde la sesion cuando aplica;
- IP;
- user agent;
- ruta actual;
- metodo HTTP;
- estado normalizado: `success`, `failed`, `denied`, `warning` o `info`.

Si la auditoria falla, la accion principal no se interrumpe; el error se envia al log tecnico.

## Proteccion de datos sensibles

La metadata (`old_values` y `new_values`) se sanitiza de forma recursiva. Se redactan claves relacionadas con:

- contrasenas y hashes;
- tokens de recuperacion, sesion, OAuth o CSRF;
- client secrets;
- access tokens, refresh tokens e ID tokens;
- codigos MFA, codigos de verificacion y codigos de recuperacion;
- secretos TOTP y secretos de QR;
- contrasenas SMTP o de correo.

Las rutas auditadas tambien redactan tokens en paths como `/reset-password/{token}`.

## Eventos principales

| Modulo | Eventos | Cuando se registran |
| --- | --- | --- |
| `auth` | `auth.login_success`, `auth.login_failed`, `auth.logout`, `auth.user_inactive`, `auth.user_blocked`, `auth.account_locked`, `auth.domain_denied` | Login local, logout, bloqueo e intentos no permitidos |
| `auth` | `external_login.started`, `external_login.success`, `external_login.failed`, `external_login.denied`, `external_login.invalid_state`, `external_login.domain_denied` | Login OAuth y validaciones del proveedor |
| `security` | `security.access_denied` | Acceso manual o directo a rutas sin permiso backend |
| `users` | `users.created`, `users.updated`, `users.deactivated`, `users.activated`, `users.role_changed`, `users.unlocked`, `users.password_changed_by_admin`, `users.exported` | Administracion de usuarios y exportacion |
| `account` | `account.password_changed`, `account.password_change_failed`, `account.email_change_requested`, `account.email_change_completed`, `account.email_change_cancelled`, `account.email_change_failed` | Mi cuenta, cambio de correo y contrasena propia |
| `password_reset` | `password_reset.requested`, `password_reset.email_sent`, `password_reset.token_invalid`, `password_reset.completed`, `password_reset.domain_denied` | Recuperacion de contrasena |
| `security_smtp` | `smtp.updated`, `smtp.test_success`, `smtp.test_failed` | Configuracion y prueba SMTP |
| `security_mfa` / `profile` / `auth` | `mfa.settings_updated`, `mfa.user_enabled`, `mfa.user_disabled`, `mfa.challenge_success`, `mfa.challenge_failed` | MFA global, MFA de usuario y challenge de login |
| `security_attempts` | `login_attempts.settings_updated`, `login_attempts.ip_locked` | Configuracion y bloqueos por intentos |
| `security_authentication` | `authentication.settings_updated`, `external_provider.updated`, `external_provider.enabled`, `external_provider.disabled`, `external_provider.test_success`, `external_provider.test_failed`, `external_provider.verified` | Metodos de login y proveedores externos |
| `security_password_policy` | `password_policy.updated`, `password_policy.validation_failed`, `password_policy.history_reuse_blocked` | Politica y validaciones de contrasenas |
| `roles_permissions` | `roles.permissions_updated` | Cambios de permisos por rol |
| `audit_logs` | `audit.exported`, `audit.export_failed` | Exportacion Excel de auditoria |
| `appearance` | `appearance.updated`, `appearance.logo_reset`, `appearance.favicon_reset`, `appearance.login_background_reset`, `appearance.colors_reset` | Cambios visuales administrables |
| `system_manual` | `manuals.uploaded`, `manuals.replaced`, `manuals.activated`, `manuals.deactivated`, `manuals.deleted`, `manuals.replace_failed`, `manuals.delete_failed` | Administracion de manuales |

## Criterios para nuevos eventos

1. Usar nombres `modulo.accion`.
2. Usar solo estados normalizados.
3. No incluir secretos ni contenido completo de archivos/exportaciones.
4. Registrar cantidades, IDs y filtros seguros cuando ayuden a investigar.
5. Evitar auditar visualizaciones simples si generan ruido.
