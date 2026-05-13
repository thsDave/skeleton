# Analisis tecnico de base de datos

## 1. Resumen ejecutivo

La base de datos de Skeleton tiene una estructura funcionalmente solida para una plantilla PHP MVC: separa usuarios, roles, permisos, auditoria, seguridad, configuraciones, OAuth, MFA, sesiones, manuales y notificaciones. El diseno base permite extender modulos y permisos sin tocar codigo estructural, y ya aplica buenas practicas importantes como hashes para contrasenas, tokens de recuperacion y codigos MFA, cifrado de secretos SMTP/OAuth desde la capa de aplicacion, auditoria con metadatos JSON y seguimiento de sesiones activas mediante `session_hash`.

El modelo, sin embargo, refleja una evolucion incremental por migraciones. Hay duplicidad historica en `tbl_users.status` y `tbl_users.status_id`, varias tablas de configuracion single-row sin una restriccion formal que impida multiples filas, algunas relaciones importantes sin FK real o sin politica `ON DELETE` explicita, y varios indices simples donde los casos de uso reales piden indices compuestos. Ninguno de estos puntos obliga a reescribir el sistema, pero conviene estabilizarlos antes de declarar Skeleton como plantilla base para proyectos futuros.

Este documento es solo analisis. No se ejecuto SQL, no se modifico la base de datos y no se creo ninguna migracion.

## 2. Estado general de la base de datos

La base actual se compone de tablas operativas, tablas de configuracion, tablas historicas, tablas temporales y tablas pivote. El uso de `tbl_` es consistente en las tablas principales y la mayoria de columnas siguen `snake_case`.

Fortalezas principales:

- RBAC normalizado con `tbl_roles`, `tbl_modules`, `tbl_permissions` y `tbl_role_permissions`.
- Auditoria centralizada en `tbl_audit_logs` con `old_values` y `new_values` como JSON.
- Tokens de recuperacion, codigos de correo y codigos MFA almacenados como hash, no en texto plano.
- Sesiones persistidas con `session_hash` unico, no con el ID de sesion en texto plano.
- Uso correcto de `VARCHAR(45)` para IP.
- Uso de `deleted_at` en entidades donde la trazabilidad visual importa, como manuales y notificaciones.
- Separacion razonable entre configuraciones SMTP, MFA, autenticacion, apariencia, seguridad de login y politica de contrasenas.

Riesgos principales:

- `tbl_users.status` convive con `tbl_users.status_id`; puede producir inconsistencias si alguna ruta usa el campo heredado.
- `tbl_two_factor_codes.user_id` no tiene FK declarada en la migracion revisada.
- `tbl_authentication_settings.default_role_id` no tiene FK hacia `tbl_roles`.
- Tablas single-row (`tbl_smtp_settings`, `tbl_mfa_settings`, `tbl_authentication_settings`, `tbl_password_policies`, `tbl_appearance_settings`, `tbl_system_settings`, `tbl_security_settings`, `tbl_login_security_settings`) dependen de convencion `id = 1`, pero no impiden multiples filas.
- `tbl_login_logs` y `tbl_login_attempts` se solapan parcialmente.
- Algunas columnas sensibles tienen nombres poco expresivos, por ejemplo `tbl_external_auth_providers.client_secret` almacena un valor cifrado, pero el nombre no indica `_enc`.
- Faltan indices compuestos para consultas frecuentes de auditoria, intentos fallidos, sesiones, notificaciones y manuales.

## 3. Diagrama logico textual

Relaciones principales:

- `tbl_users` pertenece a `tbl_roles`, `tbl_statuses` y opcionalmente `tbl_languages`.
- `tbl_roles` se relaciona muchos-a-muchos con `tbl_permissions` mediante `tbl_role_permissions`.
- `tbl_permissions` pertenece a `tbl_modules`.
- `tbl_modules` pertenece a `tbl_statuses`.
- `tbl_languages` pertenece a `tbl_statuses`.
- `tbl_user_manuals` pertenece a `tbl_users` como usuario que subio el manual y a `tbl_statuses`.
- `tbl_audit_logs` puede pertenecer a `tbl_users`, pero conserva el registro con `SET NULL` si el usuario desaparece.
- `tbl_password_resets`, `tbl_password_histories`, `tbl_email_change_verifications`, `tbl_user_external_accounts`, `tbl_user_sessions` y `tbl_notifications` pertenecen a `tbl_users`.
- `tbl_user_external_accounts` pertenece tambien a `tbl_external_auth_providers`.
- `tbl_user_sessions.revoked_by` referencia opcionalmente a `tbl_users`.
- Las tablas de configuracion son independientes y son consumidas por servicios/controladores como registros globales.

## 4. Clasificacion de tablas por dominio

### A. Identidad, seguridad y autenticacion

- `tbl_users`
- `tbl_login_logs`
- `tbl_login_attempts`
- `tbl_login_security_settings`
- `tbl_security_settings`
- `tbl_password_resets`
- `tbl_password_policies`
- `tbl_password_histories`
- `tbl_email_change_verifications`
- `tbl_mfa_settings`
- `tbl_two_factor_codes`
- `tbl_authentication_settings`
- `tbl_external_auth_providers`
- `tbl_user_external_accounts`
- `tbl_user_sessions`

### B. Autorizacion

- `tbl_roles`
- `tbl_modules`
- `tbl_permissions`
- `tbl_role_permissions`

### C. Auditoria

- `tbl_audit_logs`

El glosario de eventos vive en `config/audit_events.php`; no es una tabla.

### D. Configuracion

- `tbl_system_settings`
- `tbl_security_settings`
- `tbl_smtp_settings`
- `tbl_mfa_settings`
- `tbl_login_security_settings`
- `tbl_authentication_settings`
- `tbl_password_policies`
- `tbl_appearance_settings`

### E. Contenido y soporte

- `tbl_languages`
- `tbl_user_manuals`

### F. Notificaciones

- `tbl_notifications`

### G. Mantenimiento y temporales

- `tbl_password_resets`
- `tbl_email_change_verifications`
- `tbl_two_factor_codes`
- `tbl_login_attempts`
- `tbl_password_histories`
- `tbl_notifications` con `deleted_at`
- `tbl_user_sessions` con `revoked_at`

## 5. Mapa de tablas

| Tabla | Proposito | PK | Relaciones principales | Observaciones |
|------|-----------|----|-------------------------|---------------|
| `tbl_users` | Cuentas de usuario y credenciales locales | `id` | `status_id`, `role_id`, `language_id` | Bien centralizada, pero conserva `status` ENUM heredado junto a `status_id`. Contiene MFA por usuario. |
| `tbl_statuses` | Catalogo de estados reutilizado | `id` | Usada por usuarios, modulos, idiomas y manuales | Normalizada; conviene definir alcance semantico para evitar estados ambiguos entre dominios. |
| `tbl_roles` | Roles de autorizacion | `id` | `tbl_users`, `tbl_role_permissions` | Correcta para plantilla base. |
| `tbl_modules` | Modulos navegables/funcionales | `id` | `status_id`, `tbl_permissions` | Flexible para futuros modulos. |
| `tbl_permissions` | Permisos granulares | `id` | `module_id`, `tbl_role_permissions` | Correcta; `slug` unico es clave para extension. |
| `tbl_role_permissions` | Pivote rol-permiso | `id` | `role_id`, `permission_id` | Tiene unique compuesto; podria usar PK compuesta en una version futura, pero no es obligatorio. |
| `tbl_languages` | Idiomas disponibles | `id` | `status_id`; referida por `tbl_users` | Falta una garantia fuerte de un unico idioma por defecto. |
| `tbl_system_settings` | Datos generales del sistema | `id` | Sin FKs | Single-row por convencion; falta restriccion de una sola fila. |
| `tbl_user_manuals` | Manuales y archivos visibles al usuario | `id` | `uploaded_by`, `status_id` | Tiene soft delete. `uploaded_by` con RESTRICT implicito puede bloquear eliminacion fisica de usuario. |
| `tbl_security_settings` | Configuracion de bloqueo por inactividad | `id` | Sin FKs | Single-row por convencion. |
| `tbl_audit_logs` | Registro de actividad del sistema | `id` | `user_id` opcional | Buena base analitica; conviene indices compuestos para filtros frecuentes. |
| `tbl_login_logs` | Registro historico basico de login | `id` | `user_id` opcional | Se solapa con `tbl_login_attempts`; decidir si se conserva como historico resumido o se depreca. |
| `tbl_password_resets` | Tokens de recuperacion de contrasena | `id` | `user_id` | Token hash e indices adecuados; considerar unique en `token_hash`. |
| `tbl_smtp_settings` | Configuracion SMTP global | `id` | Sin FKs | `password_enc` esta bien nombrado y cifrado por aplicacion; single-row por convencion. |
| `tbl_mfa_settings` | Configuracion global MFA | `id` | Sin FKs | Conserva columnas SMS obsoletas por compatibilidad; documentar deprecacion. |
| `tbl_two_factor_codes` | Codigos temporales MFA | `id` | Deberia referir `user_id` | Falta FK en la migracion; tabla temporal con hash de codigo. |
| `tbl_login_security_settings` | Politica de intentos fallidos | `id` | Sin FKs | Single-row por convencion. |
| `tbl_login_attempts` | Intentos de login y bloqueos | `id` | `user_id` opcional | Buen registro operativo; faltan indices compuestos por ventana temporal. |
| `tbl_authentication_settings` | Configuracion de login local/OAuth | `id` | `default_role_id` deberia referir `tbl_roles` | `allowed_external_domains` como TEXT es funcional, pero semiestructurado. |
| `tbl_external_auth_providers` | Proveedores OAuth | `id` | Referida por cuentas externas | `client_secret` se cifra desde codigo, pero el nombre no lo expresa. |
| `tbl_user_external_accounts` | Vinculos usuario-proveedor OAuth | `id` | `user_id`, `provider_id` | Correcta, con unique por proveedor+provider_user_id. |
| `tbl_appearance_settings` | Apariencia global | `id` | Sin FKs | Single-row por convencion; rutas de archivos como VARCHAR. |
| `tbl_password_policies` | Politica global de contrasenas | `id` | Sin FKs | Single-row por convencion. |
| `tbl_password_histories` | Historial de hashes de contrasenas | `id` | `user_id` | Correcta; limpieza controlada conserva los ultimos hashes requeridos. |
| `tbl_email_change_verifications` | Codigos para cambio de correo | `id` | `user_id` | `code_hash` suficientemente largo; falta indice compuesto para pendiente por usuario. |
| `tbl_user_sessions` | Sesiones activas e historicas | `id` | `user_id`, `revoked_by` | Buen modelo con `revoked_at`; faltan indices compuestos para listados activos. |
| `tbl_notifications` | Notificaciones internas por usuario | `id` | `user_id` | Tiene soft delete con `deleted_at`; necesita mantener filtros `deleted_at IS NULL` en lecturas. |

## 6. Evaluacion de normalizacion

### Bien normalizado

- RBAC: roles, modulos, permisos y pivote estan correctamente separados.
- OAuth: proveedores y cuentas externas de usuario estan separados.
- Auditoria: entidad generica con `entity` y `entity_id` es aceptable para logs transversales.
- Manuales: archivo/manual y usuario que lo subio estan separados.
- Notificaciones: entidad propia por usuario, con estado de lectura y borrado logico.
- Password reset, email change y MFA codes: tablas temporales separadas.

### Parcialmente normalizado

- `tbl_users.status` y `tbl_users.status_id` duplican el concepto de estado. El comentario de la migracion indica compatibilidad historica, pero a largo plazo es una fuente de desalineacion.
- `tbl_authentication_settings.allowed_external_domains` guarda multiples dominios en `TEXT`. Es simple, pero no normalizado. Para una plantilla reutilizable, podria convertirse en tabla hija si se requieren validaciones fuertes, auditoria por dominio o administracion granular.
- `tbl_mfa_settings` conserva columnas SMS que el sistema ya ignora. Es comprensible por compatibilidad, pero reduce limpieza conceptual.
- `tbl_login_logs` y `tbl_login_attempts` representan eventos similares. Conviene definir si ambos tienen propositos distintos o si uno queda como legado.

### No conviene tocar por ahora

- `tbl_role_permissions.id` como PK surrogate: una PK compuesta seria mas pura, pero el unique compuesto ya protege duplicados y cambiarlo no aporta beneficio proporcional.
- `tbl_audit_logs.entity`/`entity_id`: no debe tener FK fuerte porque audita multiples entidades y debe sobrevivir a cambios estructurales.
- Tablas de configuracion separadas: aunque un `settings` key/value seria flexible, las tablas tipadas actuales dan validacion y claridad. Para Skeleton es preferible mantenerlas separadas.

## 7. Integridad referencial

### Relaciones correctas

- `tbl_users.status_id -> tbl_statuses.id`
- `tbl_users.role_id -> tbl_roles.id`
- `tbl_users.language_id -> tbl_languages.id` con `ON DELETE SET NULL`
- `tbl_modules.status_id -> tbl_statuses.id`
- `tbl_permissions.module_id -> tbl_modules.id` con `ON DELETE CASCADE`
- `tbl_role_permissions.role_id -> tbl_roles.id` con `ON DELETE CASCADE`
- `tbl_role_permissions.permission_id -> tbl_permissions.id` con `ON DELETE CASCADE`
- `tbl_audit_logs.user_id -> tbl_users.id` con `ON DELETE SET NULL`
- `tbl_password_resets.user_id -> tbl_users.id` con `ON DELETE CASCADE`
- `tbl_login_attempts.user_id -> tbl_users.id` con `ON DELETE SET NULL`
- `tbl_user_external_accounts.user_id -> tbl_users.id` con `ON DELETE CASCADE`
- `tbl_user_external_accounts.provider_id -> tbl_external_auth_providers.id` con `ON DELETE CASCADE`
- `tbl_user_sessions.user_id -> tbl_users.id` con `ON DELETE CASCADE`
- `tbl_user_sessions.revoked_by -> tbl_users.id` con `ON DELETE SET NULL`
- `tbl_notifications.user_id -> tbl_users.id` con `ON DELETE CASCADE`

### Relaciones a formalizar en una fase posterior

| Relacion | Situacion actual | Recomendacion |
|---------|------------------|---------------|
| `tbl_two_factor_codes.user_id -> tbl_users.id` | No aparece FK en la migracion 010 | Agregar FK con `ON DELETE CASCADE`, porque son codigos temporales dependientes del usuario. |
| `tbl_authentication_settings.default_role_id -> tbl_roles.id` | Campo nullable sin FK | Agregar FK con `ON DELETE SET NULL` o `RESTRICT`. Para plantilla, `SET NULL` evita romper si se elimina un rol no esencial. |
| `tbl_user_manuals.uploaded_by -> tbl_users.id` | FK sin `ON DELETE`; equivale a RESTRICT | Mantener RESTRICT si no se eliminan usuarios fisicamente. Si en el futuro hay hard delete de usuarios, considerar `SET NULL` y hacer `uploaded_by` nullable. |
| `tbl_user_manuals.status_id -> tbl_statuses.id` | FK sin `ON DELETE`; equivale a RESTRICT | Correcto si los estados son catalogo base. |
| `tbl_languages.status_id -> tbl_statuses.id` | FK sin `ON DELETE`; equivale a RESTRICT | Correcto para catalogo base. |

### Politica recomendada de borrado

- Usuarios: no hacer hard delete operativo; usar estado inactivo/bloqueado. Si se implementa `deleted_at` en usuarios, conservar auditoria y sesiones historicas.
- Auditoria: `ON DELETE SET NULL` para usuarios es correcto; no debe impedir retencion.
- Tokens/codigos temporales: `ON DELETE CASCADE` es correcto.
- Sesiones: `ON DELETE CASCADE` para usuario es aceptable si se permite purga fisica de usuarios, aunque una plantilla de cumplimiento podria preferir anonimizar en vez de borrar.
- Manuales: preferible soft delete y conservar `uploaded_by`; no borrar usuarios fisicamente.
- Notificaciones: `ON DELETE CASCADE` es aceptable por ser contenido dependiente del usuario.

## 8. Evaluacion de nombres y convenciones

Convencion actual predominante:

- Tablas con prefijo `tbl_`.
- Columnas en `snake_case`.
- PK como `id`.
- FKs como `user_id`, `role_id`, `status_id`, `permission_id`.
- Booleanos como `is_enabled`, `is_verified`, `allow_*`, `require_*`.
- Timestamps como `created_at`, `updated_at`, `deleted_at`, `used_at`, `expires_at`, `revoked_at`, `read_at`.

Inconsistencias detectadas:

- `tbl_users.status` usa ENUM heredado mientras `status_id` es la fuente normalizada.
- `tbl_external_auth_providers.client_secret` almacena un secreto cifrado; deberia llamarse `client_secret_enc` en una version futura.
- `tbl_two_factor_codes.used` es booleano; otros flujos usan `used_at`. Para trazabilidad, `used_at` es mas informativo.
- Algunas migraciones usan nombres de indices con estilos distintos: `uq_`, `uk_`, `idx_`.
- `tbl_login_logs.created_at` y `tbl_login_attempts.attempted_at` representan eventos parecidos con nombres distintos.

Convencion recomendada para Skeleton:

- Mantener `tbl_` si el proyecto ya lo adopto.
- Mantener `snake_case`.
- PK `id`.
- FK `<entidad>_id`.
- Booleanos con `is_`, `has_`, `allow_`, `require_` o `<feature>_enabled`.
- Secretos cifrados con sufijo `_enc`.
- Hashes con sufijo `_hash`.
- Eventos de tiempo con nombres especificos (`used_at`, `revoked_at`, `verified_at`) en vez de booleanos cuando importe trazabilidad.
- Indices: `idx_<tabla_corta>_<columnas>`.
- Unique: `uq_<tabla_corta>_<columnas>`.
- FK: `fk_<tabla_corta>_<referencia>`.

## 9. Evaluacion de tipos de datos

### Bien definidos

- `VARCHAR(45)` para IP.
- `VARCHAR(255)` para password hash.
- `VARCHAR(64)` para hashes SHA-256 en password reset y codigos MFA.
- `JSON` para auditoria y configuraciones extra.
- `TEXT` para mensajes largos, user agents largos en sesiones y secretos cifrados.
- `TINYINT(1)` para booleanos.
- `DATETIME`/`TIMESTAMP` usados de forma razonable para eventos.

### Campos a revisar

- IDs `INT`: suficiente para una plantilla pequena/mediana. Para Skeleton como base de proyectos grandes, evaluar `BIGINT UNSIGNED` en nuevas instalaciones. No cambiar en caliente sin plan.
- `tbl_login_logs.user_agent VARCHAR(255)`: puede truncar agentes largos. `tbl_user_sessions.user_agent TEXT` es mas robusto.
- `tbl_audit_logs.user_agent VARCHAR(255)`: aceptable para auditoria resumida, pero puede truncar. Si se requiere forense completo, usar `TEXT`.
- `tbl_email_change_verifications.code_hash VARCHAR(255)`: es amplio; si siempre es SHA-256 podria ser `VARCHAR(64)`. No es grave.
- `tbl_authentication_settings.allowed_external_domains TEXT`: funcional, pero si se gestionan dominios como entidad deberia normalizarse.
- `tbl_external_auth_providers.scopes TEXT`: correcto por flexibilidad, aunque una tabla hija o JSON seria mas validable.
- `tbl_users.two_factor_method ENUM('email','sms','authenticator')`: SMS fue retirado, pero el ENUM lo conserva. Conviene documentarlo como valor heredado hasta una migracion de limpieza.
- `tbl_mfa_settings` conserva campos SMS obsoletos; no es riesgo activo si el codigo los ignora y los limpia.

## 10. Indices y rendimiento

### Indices actuales relevantes

- `tbl_users.email` unico.
- `tbl_users.status` heredado.
- `tbl_statuses.slug` unico.
- `tbl_roles.slug` unico.
- `tbl_modules.slug` unico.
- `tbl_permissions.slug` unico.
- `tbl_role_permissions(role_id, permission_id)` unico.
- `tbl_audit_logs` tiene indices individuales para usuario, modulo, accion, entidad, estado y fecha.
- `tbl_password_resets` indexa email, token_hash, expires_at y used_at.
- `tbl_login_attempts` indexa user_id, email, ip_address, status y attempted_at.
- `tbl_user_external_accounts` indexa user_id, provider_id, provider_email y unique provider_id+provider_user_id.
- `tbl_user_sessions` tiene unique `session_hash`, e indices por user_id, revoked_at y last_activity_at.
- `tbl_notifications` tiene indices por user_id, read_at, created_at, type, deleted_at y compuesto user_id+deleted_at+read_at.

### Indices recomendados en fase posterior

| Tabla | Indice recomendado | Motivo |
|------|--------------------|--------|
| `tbl_users` | `(status_id, role_id)` | Listados y filtros administrativos por estado/rol. |
| `tbl_users` | `(created_at)` | Dashboard y listados recientes. |
| `tbl_audit_logs` | `(created_at, id)` | Listados recientes estables y paginacion. |
| `tbl_audit_logs` | `(user_id, created_at)` | Filtros por usuario y rango de fechas. |
| `tbl_audit_logs` | `(module, action, created_at)` | Analisis por modulo/evento. |
| `tbl_login_attempts` | `(email, status, attempted_at)` | Rate limiting por cuenta en ventana temporal. |
| `tbl_login_attempts` | `(ip_address, status, attempted_at)` | Rate limiting por IP en ventana temporal. |
| `tbl_login_attempts` | `(user_id, status, attempted_at)` | Analisis por usuario autenticado. |
| `tbl_password_resets` | unique o indice compuesto sobre `(token_hash, used_at, expires_at)` | Busqueda de token valido y reducir riesgo de duplicado accidental. |
| `tbl_email_change_verifications` | `(user_id, used_at, created_at)` | Buscar solicitud pendiente mas reciente. |
| `tbl_two_factor_codes` | `(user_id, method, used, expires_at, id)` | Buscar ultimo codigo valido. |
| `tbl_user_sessions` | `(user_id, revoked_at, last_activity_at)` | Sesiones activas por usuario. |
| `tbl_user_sessions` | `(revoked_at, last_activity_at)` | Panel administrativo de sesiones activas. |
| `tbl_notifications` | `(user_id, deleted_at, read_at, created_at)` | Campanita, contador y listado por usuario. |
| `tbl_user_manuals` | `(status_id, deleted_at, created_at)` | Listado de manuales visibles. |
| `tbl_external_auth_providers` | `(is_enabled, is_verified)` | Salud del sistema y login externo. |

### Riesgo de indices duplicados

No se observaron duplicados criticos en el esquema revisado. El mayor riesgo es agregar indices compuestos sin retirar indices simples que queden cubiertos. En una fase posterior se debe validar con `SHOW INDEX` sobre la base real antes de proponer cambios.

## 11. Seguridad de datos

### Practicas correctas

- `tbl_users.password` almacena hash de contrasena, no texto plano.
- `tbl_password_resets.token_hash` almacena hash del token.
- `tbl_email_change_verifications.code_hash` almacena hash del codigo.
- `tbl_two_factor_codes.code_hash` almacena hash del codigo.
- `tbl_user_sessions.session_hash` evita persistir el ID de sesion en texto plano.
- `tbl_smtp_settings.password_enc` se cifra desde `Core\Crypt`.
- `tbl_external_auth_providers.client_secret` se cifra desde el modelo `ExternalAuthProvider`.
- `tbl_users.two_factor_secret_enc` indica cifrado del secreto TOTP.
- Auditoria sanitiza claves sensibles antes de escribir `old_values` y `new_values`.

### Riesgos o mejoras recomendadas

- Renombrar en una version mayor `tbl_external_auth_providers.client_secret` a `client_secret_enc` para evitar malinterpretacion.
- `tbl_mfa_settings.sms_api_key` no tiene sufijo `_enc`; aunque SMS esta retirado y los datos se limpian, conviene eliminar o marcar formalmente como legado en una migracion futura.
- `tbl_audit_logs.user_agent VARCHAR(255)` y `tbl_login_logs.user_agent VARCHAR(255)` pueden truncar informacion. No es critico, pero limita analisis forense.
- Evaluar retencion de auditoria e intentos fallidos. Auditoria puede crecer indefinidamente.
- Evitar registrar contenidos completos de notificaciones, secretos OAuth, SMTP o codigos en auditoria. La capa actual ya apunta en esa direccion.
- Para `remember_token`, confirmar si se usa. Si se usa, debe almacenarse hasheado; si no se usa, conviene retirarlo en una version mayor.

## 12. Uso de soft delete

Uso actual:

- `tbl_user_manuals.deleted_at`: correcto para conservar trazabilidad de archivos.
- `tbl_notifications.deleted_at`: correcto para ocultar notificaciones al usuario sin perder trazabilidad inmediata.
- `tbl_user_sessions.revoked_at`: correcto; sesiones no necesitan `deleted_at`.
- `tbl_password_resets.used_at`, `tbl_email_change_verifications.used_at`, `tbl_notifications.read_at`: eventos de ciclo de vida correctos.

Politica recomendada:

- Usuarios: preferir `status_id` para inactivar/bloquear. Si se requiere eliminacion logica real, agregar `deleted_at` en una fase posterior y definir claramente filtros globales.
- Manuales: mantener soft delete.
- Notificaciones: mantener soft delete y purga fisica controlada por mantenimiento tras retencion.
- Auditoria: no usar soft delete; usar retencion administrativa o archivado.
- Sesiones: usar `revoked_at`, no `deleted_at`.
- Tokens/codigos: usar `used_at`/`expires_at` y limpieza fisica controlada.

## 13. Timestamps

Bien usados:

- `created_at` en casi todas las entidades.
- `updated_at` en entidades configurables o editables.
- `expires_at` y `used_at` en tokens/codigos.
- `revoked_at` en sesiones.
- `read_at` y `deleted_at` en notificaciones.
- `last_login_at`, `last_failed_login_at`, `locked_until`, `password_changed_at` en usuarios.

Recomendaciones:

- `tbl_two_factor_codes.used` deberia evolucionar a `used_at` para consistencia y trazabilidad.
- Tablas single-row deberian incluir `updated_by` nullable para configuraciones sensibles: SMTP, MFA, autenticacion, intentos, sesiones, politica de contrasenas y apariencia.
- `tbl_user_manuals` podria agregar `deleted_by` si se requiere trazabilidad de eliminacion logica.
- `tbl_notifications` no necesita `updated_at` salvo que se edite contenido; `read_at` y `deleted_at` bastan.
- `tbl_audit_logs` no necesita `updated_at`; la auditoria debe ser append-only.

## 14. Tablas de configuracion

El modelo usa tablas especificas por configuracion. Esto es recomendable para Skeleton porque evita convertir todo en strings dentro de una tabla key/value.

Riesgo comun: varias tablas dependen de que la aplicacion lea `WHERE id = 1`, pero la BD no impide filas adicionales.

Tablas afectadas:

- `tbl_system_settings`
- `tbl_security_settings`
- `tbl_smtp_settings`
- `tbl_mfa_settings`
- `tbl_login_security_settings`
- `tbl_authentication_settings`
- `tbl_password_policies`
- `tbl_appearance_settings`

Recomendacion:

- Mantener tablas separadas.
- En fase posterior, agregar una regla formal para single-row. Opciones: CHECK `id = 1` en MySQL compatible, PK fija gestionada por migraciones, o columna `singleton_key` unica con valor constante.
- Agregar `updated_by` nullable hacia `tbl_users` en configuraciones sensibles si se quiere auditoria relacional ademas de `tbl_audit_logs`.

## 15. Auditoria

`tbl_audit_logs` es adecuada para una plantilla base:

- Tiene `user_id`.
- Tiene `module` y `action` separados.
- Tiene `entity` y `entity_id`.
- Tiene `description`.
- Tiene `old_values` y `new_values` como JSON.
- Tiene `ip_address`, `user_agent`, `route`, `method`, `status` y `created_at`.

Mejoras recomendadas:

- Agregar indices compuestos por patrones reales de busqueda.
- Evitar `DATE(created_at)` en filtros porque puede impedir uso eficiente de indice; en fase de codigo, preferir rangos `created_at >= inicio AND created_at < fin`.
- Definir politica de retencion: por ejemplo conservar auditoria critica mas tiempo que eventos informativos, o exportar/archivar antes de purgar.
- Mantener `ON DELETE SET NULL` para usuario.
- No normalizar `action` a FK en esta fase; el glosario en PHP es suficiente y mas flexible para plantilla.

## 16. Adaptabilidad para futuros proyectos

Listo para reutilizar:

- Sistema de permisos por slug.
- Modulos extensibles.
- Auditoria generica.
- Configuraciones separadas y tipadas.
- Login local y OAuth.
- Sesiones activas e historial.
- Notificaciones internas por usuario.
- Manuales y apariencia.
- Limpieza de temporales.

Podria limitar proyectos futuros:

- Estados globales en `tbl_statuses` pueden quedarse cortos si cada dominio necesita estados especificos.
- `tbl_users` concentra datos de perfil, seguridad, MFA y preferencias. Es practico para Skeleton, pero proyectos grandes podrian separar `user_profiles`, `user_security_settings` o `user_preferences`.
- OAuth restringido por `ALLOWED_SLUGS` en codigo y registros iniciales; agregar proveedores no predefinidos requiere tocar codigo.
- Configuraciones single-row sin restriccion formal pueden duplicarse por error operativo.
- Dominios externos en `TEXT` limitan validacion e historico.

Modelo recomendado para Skeleton:

- Mantener RBAC actual.
- Mantener tablas de configuracion separadas, pero formalizar single-row.
- Mantener auditoria append-only.
- Mantener usuarios como tabla central, pero retirar gradualmente campos heredados (`status`, SMS).
- Agregar FKs faltantes y revisar politicas `ON DELETE`.
- Agregar indices compuestos por caso de uso.
- Documentar convenciones para nuevas tablas de negocio.

## 17. Hallazgos por prioridad

| Hallazgo | Prioridad | Riesgo | Recomendacion |
|---------|-----------|--------|---------------|
| `tbl_users.status` duplica `tbl_users.status_id` | Alta | Estados inconsistentes entre codigo legado y codigo nuevo | Corregido en fase 028: `status_id` queda como fuente oficial y el campo heredado se elimina al importar la migracion. |
| `tbl_two_factor_codes.user_id` sin FK declarada | Alta | Codigos huerfanos si se elimina un usuario; integridad incompleta en seguridad | Corregido en fase 028 con FK hacia `tbl_users(id)` y `ON DELETE CASCADE`. |
| `tbl_authentication_settings.default_role_id` sin FK | Alta | Configuracion puede apuntar a rol inexistente | Corregido en fase 028 con FK hacia `tbl_roles(id)` y `ON DELETE SET NULL`. |
| Tablas single-row sin restriccion formal | Media | Duplicados de configuracion y lecturas ambiguas por `id = 1` | Formalizar singleton por constraint/check o clave unica constante. |
| Indices simples donde se necesitan compuestos | Media | Consultas lentas al crecer auditoria, intentos, sesiones y notificaciones | Agregar indices compuestos tras revisar `SHOW INDEX` de la BD real. |
| `tbl_login_logs` y `tbl_login_attempts` se solapan | Media | Doble fuente de verdad para eventos de login | Definir proposito de cada tabla o deprecar una en version futura. |
| `client_secret` cifrado sin sufijo `_enc` | Media | Riesgo de interpretacion incorrecta por futuros desarrolladores | Renombrar en version mayor o documentar claramente. |
| SMS retirado pero columnas/ENUM siguen presentes | Media | Ruido conceptual y superficie de mantenimiento | Mantener por compatibilidad ahora; planificar limpieza en version mayor. |
| `allowed_external_domains` como `TEXT` | Media | Dificulta validar dominios individualmente y auditar cambios granulares | Si se vuelve importante, crear tabla hija de dominios permitidos. |
| Falta `updated_by` en configuraciones sensibles | Baja | Auditoria relacional depende solo de logs | Agregar si se requiere trazabilidad directa por tabla. |
| `user_agent` truncado en algunas tablas | Baja | Menor detalle forense | Evaluar `TEXT` en auditoria/login logs si se requiere analisis completo. |
| Estilos de nombres `uk_`/`uq_` mezclados | Baja | Inconsistencia estetica | Adoptar `uq_`, `idx_`, `fk_` para nuevas migraciones. |

## 18. Recomendaciones de normalizacion

- Mantener `tbl_statuses`, pero decidir si sera catalogo global o si nuevos proyectos deben crear estados por dominio cuando haya reglas especificas.
- Retirar gradualmente `tbl_users.status` cuando se confirme que toda la aplicacion usa `status_id`.
- Considerar `tbl_authentication_allowed_domains` si la restriccion de dominios externos se vuelve una entidad administrable.
- Considerar `used_at` en `tbl_two_factor_codes` en vez de `used`.
- Documentar columnas SMS como legado hasta eliminarlas.
- No convertir configuraciones tipadas a key/value salvo que el sistema necesite plugins con configuraciones arbitrarias.

## 19. Recomendaciones de integridad referencial

- Formalizar FK de `tbl_two_factor_codes.user_id`.
- Formalizar FK de `tbl_authentication_settings.default_role_id`.
- Revisar `ON DELETE` de `tbl_user_manuals.uploaded_by`; mantener RESTRICT si no se eliminan usuarios fisicamente.
- Mantener `SET NULL` en auditoria y `revoked_by`.
- Mantener `CASCADE` en tokens/codigos temporales dependientes.
- Evitar FKs desde auditoria hacia entidades genericas auditadas.

## 20. Recomendaciones de indices

Prioridad media-alta:

- `tbl_audit_logs(user_id, created_at)`
- `tbl_audit_logs(module, action, created_at)`
- `tbl_login_attempts(email, status, attempted_at)`
- `tbl_login_attempts(ip_address, status, attempted_at)`
- `tbl_user_sessions(user_id, revoked_at, last_activity_at)`
- `tbl_notifications(user_id, deleted_at, read_at, created_at)`

Prioridad media:

- `tbl_users(status_id, role_id)`
- `tbl_users(created_at)`
- `tbl_email_change_verifications(user_id, used_at, created_at)`
- `tbl_two_factor_codes(user_id, method, used, expires_at, id)`
- `tbl_user_manuals(status_id, deleted_at, created_at)`
- `tbl_external_auth_providers(is_enabled, is_verified)`

Antes de aplicar cualquier indice, revisar la base real con `SHOW INDEX` y validar cardinalidad.

## 21. Recomendaciones de seguridad

- Confirmar que `remember_token`, si se usa, se almacene hasheado. Si no se usa, marcarlo como candidato a retiro.
- Mantener cifrado de `password_enc`, `client_secret` y `two_factor_secret_enc`.
- Renombrar secretos cifrados con sufijo `_enc` en una version mayor.
- Mantener sanitizacion de auditoria y evitar registrar secretos o payloads completos.
- Definir retencion para auditoria, intentos fallidos, codigos, tokens y notificaciones eliminadas.
- Evaluar `user_agent` como `TEXT` si la auditoria forense es importante.
- Evitar hard delete de usuarios en proyectos que requieran trazabilidad.

## 22. Propuesta de modelo mejorado

Tablas que se mantienen igual en concepto:

- `tbl_roles`
- `tbl_modules`
- `tbl_permissions`
- `tbl_role_permissions`
- `tbl_audit_logs`
- `tbl_user_external_accounts`
- `tbl_user_sessions`
- `tbl_notifications`
- `tbl_password_histories`

Tablas que deberian ajustarse:

- `tbl_users`: retirar `status` heredado en version mayor; evaluar separar preferencias/perfil en proyectos grandes.
- `tbl_two_factor_codes`: agregar FK y reemplazar `used` por `used_at` en version mayor.
- `tbl_authentication_settings`: formalizar FK de `default_role_id` y singleton.
- `tbl_external_auth_providers`: renombrar `client_secret` a `client_secret_enc` en version mayor.
- `tbl_mfa_settings`: retirar columnas SMS heredadas cuando se haga una migracion de limpieza.
- `tbl_login_logs`/`tbl_login_attempts`: definir consolidacion o proposito separado.
- Tablas single-row: formalizar restriccion de una unica fila.

Columnas candidatas a agregar:

- `updated_by` en configuraciones sensibles.
- `deleted_by` en `tbl_user_manuals` si se requiere trazabilidad de borrado logico.
- `used_at` en `tbl_two_factor_codes`.
- `verified_at` opcional en proveedores OAuth o SMTP si se quiere distinguir ultima verificacion exitosa de ultima prueba.

Reglas de integridad recomendadas:

- FKs explicitas para todo `*_id` operacional salvo auditoria generica.
- `ON DELETE CASCADE` para datos temporales dependientes del usuario.
- `ON DELETE SET NULL` para autores/responsables historicos cuando se conserva la entidad.
- `ON DELETE RESTRICT` para catalogos base como roles, estados y modulos criticos.
- Soft delete para entidades visibles del usuario; retencion fisica solo desde mantenimiento.

## 23. Proximos pasos sugeridos

1. Confirmar el esquema real en la base instalada con `SHOW CREATE TABLE` para cada tabla antes de preparar migraciones.
2. Crear una fase de migraciones correctivas no destructivas: FKs faltantes, indices compuestos y constraints single-row.
3. Crear una fase de limpieza de version mayor para retirar campos heredados: `tbl_users.status`, SMS en MFA y nombres de secretos sin `_enc`.
4. Documentar convenciones de nuevas tablas de negocio para proyectos que usen Skeleton.
5. Definir politica de retencion por dominio: auditoria, intentos, sesiones revocadas, tokens, codigos y notificaciones eliminadas.
6. Revisar consultas con filtros por fecha para evitar funciones sobre columnas indexadas.
7. Generar un diagrama ER visual a partir de la base real cuando el esquema quede estabilizado.

## 24. Validaciones de esta revision

- Se revisaron los archivos SQL disponibles en `database/`.
- Se revisaron modelos y servicios relevantes para interpretar relaciones y uso real.
- No se modifico la base de datos.
- No se ejecutaron migraciones.
- No se creo ningun archivo SQL nuevo.
- No se incluyo contenido de `.env`.
- No se incluyeron contrasenas, tokens ni secretos reales.
- Las recomendaciones son propuestas para fases posteriores, no cambios aplicados.

## 25. Actualizacion: fase de correcciones de alta prioridad

Se trabajo una fase correctiva documentada en `docs/database-high-priority-fixes.md` y entregada en la migracion `database/028_clean_high_priority_database_model.sql`.

Hallazgos de alta prioridad abordados:

- `tbl_users.status` se retira como campo heredado; `tbl_users.status_id` queda como fuente oficial del estado del usuario.
- `tbl_two_factor_codes.user_id` se formaliza como FK hacia `tbl_users.id` con `ON DELETE CASCADE`.
- `tbl_authentication_settings.default_role_id` se formaliza como FK hacia `tbl_roles.id` con `ON DELETE SET NULL`.

Estas correcciones deben importarse manualmente en la base de datos de desarrollo despues de ejecutar las consultas de prevalidacion indicadas en la documentacion de la fase.
