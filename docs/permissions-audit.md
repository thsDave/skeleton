# Revision de permisos por rutas y controladores

Fecha de revision: 2026-05-11

## Autorizacion central

El sistema usa `Core\Auth::requirePermission($permission)` como punto central para rutas protegidas por permiso. Este metodo:

- exige sesion activa con `Auth::requireAuth()`;
- valida el permiso cargado en sesion desde `tbl_role_permissions`;
- registra denegaciones en logger de seguridad;
- registra auditoria con `action = access_denied`, permiso requerido, ruta y metodo HTTP;
- responde con la vista 403 y detiene la ejecucion.

Para rutas de cuenta propia se usa `Auth::requireAuth()` sin permiso administrativo adicional.

## Rutas publicas o controladas

Estas rutas no deben exigir permisos administrativos porque forman parte del flujo de autenticacion o recuperacion:

| Ruta | Controlador | Motivo |
| --- | --- | --- |
| `GET /login`, `POST /login` | `AuthController` | Inicio de sesion |
| `GET /forgot-password`, `POST /forgot-password` | `PasswordResetController` | Recuperacion de contrasena |
| `GET /reset-password/{token}`, `POST /reset-password` | `PasswordResetController` | Restablecimiento por token |
| `GET /auth/external/{provider}/redirect` | `ExternalAuthController` | Inicio OAuth |
| `GET /auth/external/{provider}/callback` | `ExternalAuthController` | Callback OAuth |
| `GET /two-factor/challenge`, `POST /two-factor/challenge`, `POST /two-factor/resend` | `TwoFactorChallengeController` | Verificacion MFA pendiente |

## Rutas autenticadas sin permiso administrativo

Estas rutas requieren usuario autenticado, pero corresponden a acciones propias del usuario:

| Ruta | Controlador | Proteccion |
| --- | --- | --- |
| `GET /profile`, `GET /profile/edit`, `POST /profile/update` | `ProfileController` | `Auth::requireAuth()` |
| `POST /profile/preferences`, `POST /profile/theme` | `ProfileController` | `Auth::requireAuth()` + CSRF |
| `GET /account` y rutas de correo/contrasena propias | `AccountController` | `Auth::requireAuth()` + CSRF en POST |
| `GET /profile/two-factor` y acciones MFA propias | `TwoFactorController` | `Auth::requireAuth()` + CSRF en POST |
| `GET /account/password/required-change`, `POST /account/password/required-change` | `RequiredPasswordChangeController` | `Auth::requireAuth()` + CSRF en POST |
| `GET /lock`, `POST /lock/session`, `POST /unlock` | `LockController` | Sesion existente + CSRF en POST |
| `POST /logout` | `AuthController` | CSRF |

## Rutas protegidas por permiso

| Modulo | Rutas | Permisos aplicados |
| --- | --- | --- |
| Dashboard | `/`, `/dashboard` | `dashboard.view` |
| Usuarios | `/users`, `/users/create`, `/users/store`, `/users/edit/{id}`, `/users/update/{id}`, `/users/delete/{id}`, `/users/unlock/{id}`, `/users/export/excel` | `users.view`, `users.create`, `users.edit`, `users.delete`, `users.unlock`, `users.export` |
| Idiomas | `/languages`, `/languages/create`, `/languages/store`, `/languages/edit/{id}`, `/languages/update/{id}`, `/languages/toggle/{id}` | `languages.view`, `languages.create`, `languages.edit`, `languages.activate`, `languages.deactivate` |
| Informacion del sistema | `/system-information`, `/system-information/edit`, `/system-information/update` | `system_information.view`, `system_information.edit` |
| Manuales | `/manuals/create`, `/manuals/store`, `/manuals/toggle/{id}`, `/manuals/replace/{id}`, `/manuals/delete/{id}`, `/manuals/download/{id}` | `manuals.upload`, `manuals.activate`, `manuals.deactivate`, `manuals.edit`, `manuals.delete`, `manuals.view` |
| Sesiones | `/security/sessions`, `/security/sessions/update` | `security_sessions.view`, `security_sessions.edit` |
| SMTP | `/security/smtp`, `/security/smtp/update`, `/security/smtp/test` | `security_smtp.view`, `security_smtp.edit`, `security_smtp.test` |
| MFA administrativo | `/security/mfa`, `/security/mfa/update` | `security_mfa.view`, `security_mfa.edit` |
| Intentos | `/security/attempts`, `/security/attempts/update` | `security_attempts.view`, `security_attempts.edit` |
| Autenticacion | `/security/authentication`, `/security/authentication/settings/update`, `/security/authentication/providers/edit/{id}`, `/security/authentication/providers/update/{id}`, `/security/authentication/providers/toggle/{id}`, `/security/authentication/providers/test/{id}` | `security_authentication.view`, `security_authentication.edit`, `security_authentication.providers_edit`, `security_authentication.providers_test` |
| Politica de contrasenas | `/security/password-policy`, `/security/password-policy/update` | `security_password_policy.view`, `security_password_policy.edit` |
| Roles y permisos | `/roles-permissions`, `/roles-permissions/edit/{id}`, `/roles-permissions/update/{id}` | `roles_permissions.view`, `roles_permissions.edit` |
| Auditoria | `/audit-logs`, `/audit-logs/show/{id}`, `/audit-logs/export/excel` | `audit_logs.view`, `audit_logs.show`, `audit_logs.export` |
| Apariencia | `/appearance`, `/appearance/update`, `/appearance/reset-logo`, `/appearance/reset-favicon`, `/appearance/reset-login-background`, `/appearance/reset-colors` | `appearance.view`, `appearance.edit`, `appearance.reset` |

## Criterios para nuevos modulos

Al agregar una nueva ruta administrativa:

1. crear o reutilizar un permiso granular;
2. validar ese permiso al inicio del metodo del controlador;
3. usar POST y CSRF para acciones sensibles;
4. alinear menus y botones con `can()`;
5. evitar exponer datos sensibles en auditoria o logs.

## Resultado

No se identificaron rutas administrativas enlazadas desde `public/index.php` sin validacion backend. No se requirio SQL en esta revision.
