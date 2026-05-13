# Correcciones de alta prioridad del modelo de base de datos

## Resumen

Esta fase corrige tres hallazgos de alta prioridad del analisis tecnico de base de datos de Skeleton:

1. `tbl_users.status` era un campo heredado que duplicaba `tbl_users.status_id`.
2. `tbl_two_factor_codes.user_id` no tenia una FK declarada hacia `tbl_users.id`.
3. `tbl_authentication_settings.default_role_id` no tenia una FK declarada hacia `tbl_roles.id`.

La correccion se entrega como migracion incremental en `database/028_clean_high_priority_database_model.sql`. No se ejecuta automaticamente desde el codigo.

## Estado oficial de usuarios

`tbl_users.status_id` queda como unica fuente oficial del estado de usuario. El codigo de usuarios, login local, OAuth, dashboard y exportacion administrativa consulta el estado mediante `tbl_statuses` y los aliases `status_slug` / `status_name`.

El campo heredado `tbl_users.status` se elimina en la migracion 028 despues de alinear sus valores con `status_id`.

## Llaves foraneas agregadas

| Relacion | Politica | Motivo |
|---|---|---|
| `tbl_two_factor_codes.user_id -> tbl_users.id` | `ON DELETE CASCADE` | Los codigos MFA son temporales y dependen completamente del usuario. |
| `tbl_authentication_settings.default_role_id -> tbl_roles.id` | `ON DELETE SET NULL` | Si se elimina un rol predeterminado, la configuracion no debe apuntar a un rol inexistente. |

## Codigo ajustado

- `AuthenticationController` valida que `default_role_id` exista antes de guardar la configuracion.
- Si la creacion automatica de usuarios por OAuth esta activa, se exige un rol por defecto valido.
- `ExternalAuthController` valida nuevamente el rol por defecto antes de crear usuarios desde OAuth y muestra un error amigable si la configuracion no es valida.
- `Status` expone helpers para consultar estados por slug y obtener IDs base.
- `User::inactivate()` usa el helper de estado inactivo en vez de depender de un ID literal.
- Las vistas de cuenta/perfil ya no hacen fallback a `user['status']`.
- `SystemHealthService` agrega checks de integridad para estado de usuario, estados base y FKs nuevas.

## Prevalidacion antes de importar

Antes de ejecutar la migracion, revisar:

```sql
SHOW CREATE TABLE tbl_users;
SHOW INDEX FROM tbl_users;
SHOW CREATE TABLE tbl_two_factor_codes;
SHOW CREATE TABLE tbl_authentication_settings;
SHOW CREATE TABLE tbl_roles;
SHOW CREATE TABLE tbl_statuses;

SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tbl_users'
  AND COLUMN_NAME IN ('status', 'status_id');

SELECT u.id, u.email, u.status_id
FROM tbl_users u
LEFT JOIN tbl_statuses s ON s.id = u.status_id
WHERE s.id IS NULL;

SELECT tfc.*
FROM tbl_two_factor_codes tfc
LEFT JOIN tbl_users u ON u.id = tfc.user_id
WHERE u.id IS NULL;

SELECT auth.id, auth.default_role_id
FROM tbl_authentication_settings auth
LEFT JOIN tbl_roles r ON r.id = auth.default_role_id
WHERE auth.default_role_id IS NOT NULL
  AND r.id IS NULL;

SELECT TABLE_NAME, CONSTRAINT_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  AND TABLE_NAME IN ('tbl_two_factor_codes', 'tbl_authentication_settings');
```

Resultados esperados antes de importar:

- `tbl_users.status` existe y `tbl_users.status_id` existe.
- El indice heredado sobre `tbl_users.status` se llama `idx_users_status`. Si tiene otro nombre, ajustar la linea `DROP INDEX` de la migracion antes de importarla.
- No existen las FKs `fk_two_factor_codes_user` ni `fk_authentication_settings_default_role`.
- Los SELECT de huerfanos pueden devolver filas; la migracion las corrige porque Skeleton aun no contiene datos productivos.

## Validacion posterior

Despues de importar `database/028_clean_high_priority_database_model.sql`, ejecutar:

```sql
SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tbl_users'
  AND COLUMN_NAME = 'status';

SELECT u.id, u.email, u.status_id
FROM tbl_users u
LEFT JOIN tbl_statuses s ON s.id = u.status_id
WHERE s.id IS NULL;

SELECT CONSTRAINT_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tbl_two_factor_codes'
  AND CONSTRAINT_NAME = 'fk_two_factor_codes_user'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SELECT CONSTRAINT_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tbl_authentication_settings'
  AND CONSTRAINT_NAME = 'fk_authentication_settings_default_role'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SELECT tfc.*
FROM tbl_two_factor_codes tfc
LEFT JOIN tbl_users u ON u.id = tfc.user_id
WHERE u.id IS NULL;

SELECT auth.id, auth.default_role_id
FROM tbl_authentication_settings auth
LEFT JOIN tbl_roles r ON r.id = auth.default_role_id
WHERE auth.default_role_id IS NOT NULL
  AND r.id IS NULL;
```

Resultados esperados despues de importar:

- La consulta de `tbl_users.status` no devuelve filas.
- No hay usuarios con `status_id` invalido.
- Ambas FKs existen.
- No hay codigos MFA huerfanos.
- `default_role_id` queda en `NULL` o apunta a un rol existente.

## Pruebas funcionales recomendadas

- Usuarios: listar, crear, editar, inactivar, desbloquear y exportar.
- Login local: usuario activo entra; inactivo o bloqueado no entra.
- OAuth: login con usuario existente, vinculacion y creacion automatica con rol por defecto valido.
- MFA: generar, validar y limpiar codigos por correo/app.
- Salud del Sistema: revisar checks de estado de usuarios, MFA y rol por defecto.
- Dashboard, auditoria, sesiones, mantenimiento y notificaciones: confirmar que cargan sin errores.

## Pendientes de prioridad media

- Evaluar `used_at` en `tbl_two_factor_codes` en una fase posterior.
- Definir una restriccion singleton formal para tablas de configuracion `id = 1`.
- Revisar indices compuestos sugeridos en `docs/database-analysis.md`.
