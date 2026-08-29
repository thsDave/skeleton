# Skeleton PHP MVC

Skeleton PHP MVC es una plantilla base para construir sistemas administrativos con PHP puro, arquitectura MVC, PDO, Composer y DashboardKit. Su objetivo es servir como punto de partida estable para nuevos proyectos sin rehacer autenticacion, autorizacion, seguridad, auditoria, configuracion y estructura visual.

Version estable: **v3.0.0**  
Fecha: **2026-05-13**

## Caracteristicas Principales

- Login local y recuperacion de contrasena por correo.
- SMTP administrable y prueba de envio.
- OAuth externo para Google, Microsoft 365 y GitHub.
- MFA por correo/autenticador, politica de contrasenas e intentos fallidos.
- Rate limiting (`RateLimitService`) en MFA TOTP, desbloqueo de sesion, recuperacion de contrasena por IP y pruebas administrativas de SMTP/OAuth.
- Gestion de sesiones activas, historial de sesiones y bloqueo por inactividad.
- Usuarios, roles, permisos y validacion de permisos en backend.
- Auditoria, glosario de auditoria y exportaciones Excel.
- Apariencia: logo, favicon, fondo de login y colores.
- Manuales del sistema y Dashboard.
- Salud del Sistema y mantenimiento/limpieza de datos temporales.
- Notificaciones internas.
- Modo claro/oscuro, traducciones con `__()`, SweetAlert2, CSRF y paginas de error.
- UploadService para perfiles, manuales y apariencia.

## Requisitos

- PHP 8.3 o superior.
- MySQL 8 o MariaDB compatible.
- Composer.
- Laragon en Windows como entorno local sugerido.
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`, `curl`, `zip` y `gd` o `imagick`.
- Navegador moderno: Chrome, Edge, Firefox o equivalente.

## Instalacion Local Desde Cero

1. Clonar el repositorio:

```bash
git clone URL_DEL_REPOSITORIO skeleton
```

2. Entrar a la carpeta:

```bash
cd skeleton
```

3. Instalar dependencias:

```bash
composer install
```

4. Configurar entorno:

```bash
cp .env.example .env
```

Configura como minimo:

```env
APP_URL="http://localhost/skeleton/public"
DB_HOST=127.0.0.1
DB_DATABASE=db_skeleton
DB_USERNAME=root
DB_PASSWORD=
APP_KEY=GENERA_UNA_CLAVE_SEGURA
```

Genera `APP_KEY` con:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

5. Crear una base de datos vacia desde phpMyAdmin, por ejemplo `db_skeleton`.

6. Importar el SQL consolidado:

```text
database/schema/skeleton_schema.sql
```

7. Verificar permisos de escritura:

- `public/uploads`
- `public/uploads/profiles`
- `public/uploads/manuals`
- `storage/logs` si existe
- `storage/cache` si existe
- `logs`

8. Acceder:

```text
http://localhost/skeleton/public
```

9. Credenciales iniciales:

```text
Correo: admin@example.com
Password temporal: Admin123*
```

Cambia la contrasena inmediatamente al primer acceso. El usuario inicial queda con cambio obligatorio habilitado.

## Configuracion Posterior

- Verifica que `APP_URL` no tenga barra final y apunte a la URL publica real.
- Configura SMTP desde Seguridad > SMTP y ejecuta una prueba.
- Configura proveedores OAuth desde Seguridad > Autenticacion.
- Configura restricciones por dominio si el login externo debe limitarse.
- Revisa Seguridad > Politica de Contrasenas.
- Revisa Seguridad > MFA, Seguridad > Intentos y Seguridad > Sesiones.
- Configura Apariencia.
- Revisa Salud del Sistema.

## OAuth

La Redirect URI depende de `APP_URL`. En local debe quedar asi:

```text
http://localhost/skeleton/public/auth/external/google/callback
http://localhost/skeleton/public/auth/external/microsoft/callback
http://localhost/skeleton/public/auth/external/github/callback
```

En produccion cambia `APP_URL` al dominio real y actualiza las Redirect URI en cada proveedor. No guardes `client_secret` ni tokens en archivos versionados.

## Estructura Del Proyecto

| Carpeta | Proposito |
|---|---|
| `app/Controllers` | Controladores MVC y flujo HTTP. |
| `app/Models` | Acceso a datos con PDO. |
| `app/Services` | Servicios de dominio: correo, OAuth, uploads, sesiones, salud, limpieza, rate limit. |
| `app/Views` | Vistas PHP. |
| `app/Views/layouts` | Header, sidebar, topbar y footer. |
| `config` | Configuracion de aplicacion, DB, uploads y auditoria. |
| `core` | Router, Auth, Controller, Session, CSRF, helpers y base tecnica. |
| `database` | SQL consolidado oficial y migraciones incrementales de referencia. |
| `public` | Punto de entrada y assets publicos. |
| `public/assets` | CSS, JS, fuentes e imagenes base. |
| `public/uploads` | Archivos subidos por usuarios, no versionar contenido real. |
| `storage` | Cache/logs si el entorno lo usa. |
| `lang` | Traducciones. |
| `docs` | Documentacion operativa adicional. |

## Esquema De Base De Datos

| Tabla | Proposito |
|---|---|
| `tbl_users` | Usuarios, credenciales, estado, rol, preferencias y MFA. |
| `tbl_roles` | Roles base del sistema. |
| `tbl_permissions` | Permisos granulares. |
| `tbl_role_permissions` | Asignacion de permisos a roles. |
| `tbl_modules` | Modulos del sistema y metadatos de menu. |
| `tbl_statuses` | Estados generales: active, inactive, blocked. |
| `tbl_languages` | Idiomas disponibles. |
| `tbl_audit_logs` | Auditoria de acciones del sistema. |
| `tbl_login_logs` | Registro historico de logins. |
| `tbl_login_attempts` | Intentos fallidos y proteccion de acceso. |
| `tbl_rate_limits` | Control generico de intentos por accion (MFA TOTP, desbloqueo de sesion, recuperacion de contrasena por IP, pruebas admin SMTP/OAuth) via `RateLimitService`. |
| `tbl_security_settings` | Bloqueo por inactividad. |
| `tbl_smtp_settings` | Configuracion SMTP cifrada. |
| `tbl_mfa_settings` | Configuracion global MFA. |
| `tbl_authentication_settings` | Metodos de autenticacion y dominios permitidos. |
| `tbl_external_auth_providers` | Proveedores OAuth. |
| `tbl_user_external_accounts` | Vinculos entre usuarios y cuentas externas. |
| `tbl_password_resets` | Tokens hasheados de recuperacion de contrasena. |
| `tbl_password_policies` | Reglas de contrasenas. |
| `tbl_password_histories` | Historial de hashes para prevenir reutilizacion. |
| `tbl_email_change_verifications` | Verificacion de cambio de correo. |
| `tbl_two_factor_codes` | Codigos temporales MFA. |
| `tbl_user_sessions` | Sesiones activas e historial de revocacion. |
| `tbl_system_settings` | Informacion general del sistema. |
| `tbl_user_manuals` | Metadatos de manuales subidos. |
| `tbl_appearance_settings` | Logo, favicon, fondo de login y colores. |
| `tbl_notifications` | Notificaciones internas. |

Dominios principales:

- Identidad y seguridad: usuarios, sesiones, MFA, intentos, recuperacion, politica de contrasenas.
- Autorizacion: roles, permisos, modulos y asignaciones.
- Auditoria: eventos, glosario desde configuracion y exportacion.
- Configuracion: SMTP, autenticacion, apariencia, sistema y salud.
- Contenido/manuales: metadatos de manuales y archivos en `public/uploads`.
- Notificaciones: mensajes internos por usuario.
- Mantenimiento/temporales: limpieza controlada de tokens, codigos MFA, limites de tasa, sesiones, intentos, logs y notificaciones.

## Convenciones De Base De Datos

- Tablas con prefijo `tbl_`.
- Columnas en `snake_case`.
- Llave primaria como `id`.
- Llaves foraneas como `user_id`, `role_id`, `status_id`.
- Timestamps habituales: `created_at`, `updated_at`, `deleted_at`.
- Hashes con sufijo `_hash`.
- Secretos cifrados con sufijo `_enc` cuando aplique.
- `tbl_users.status_id` es la fuente oficial del estado del usuario.
- No usar `tbl_users.status`; fue retirado.

## Crear Nuevos Modulos

1. Crear la tabla SQL con PK, FK, indices y timestamps.
2. Crear modelo en `app/Models`.
3. Crear controlador en `app/Controllers`.
4. Crear vistas en `app/Views`.
5. Registrar rutas en `public/index.php`.
6. Crear modulo y permisos base en SQL.
7. Asignar permisos al rol Administrador.
8. Validar permisos en backend con `Auth::requirePermission()`.
9. Mostrar menu solo si el usuario tiene permiso con `can()`.
10. Usar CSRF en acciones `POST`.
11. Registrar auditoria en acciones criticas.
12. Agregar traducciones en `lang`.
13. Respetar modo oscuro y estructura DashboardKit.
14. Usar UploadService para archivos.

Regla de layout: despues de `.page-header` debe venir una `.row` antes de colocar `.card`. No iniciar directamente con `.card` despues de `.page-header`.

## Reglas SQL Importantes

- No usar `IF NOT EXISTS`.
- No usar `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`.
- Crear migraciones compatibles con phpMyAdmin.
- Indicar pasos manuales cuando una migracion dependa del estado previo.
- Importar `database/schema/skeleton_schema.sql` solo en una base vacia.

## Documentacion Adicional

- [Crear nuevo modulo](docs/crear-nuevo-modulo.md)
- [Convenciones de desarrollo](docs/convenciones-desarrollo.md)
- [Permisos y auditoria](docs/permisos-y-auditoria.md)
- [Despliegue en hosting compartido](docs/despliegue-hosting-compartido.md)
- [Backups y restauracion](docs/backups-restauracion.md)
- [Base de datos](database/README.md)

## Seguridad

- No subir `.env`.
- No subir `vendor/`.
- No subir `node_modules/`.
- No versionar uploads reales, dumps con datos reales, tokens ni secretos.
- Cambiar credenciales iniciales inmediatamente.
- Usar `APP_DEBUG=false` en produccion.
- Usar HTTPS en produccion.
- Configurar SMTP real desde la interfaz.
- Revisar Salud del Sistema despues de instalar.
- Configurar politica de contrasenas.
- Revisar auditoria y permisos por rol.

## Backups

Respaldar:

- Base de datos.
- `public/uploads`.
- `.env`.
- Archivos de configuracion relevantes.

Frecuencia recomendada:

- Diario para sistemas activos.
- Antes de despliegues o cambios de base de datos.
- Semanal como minimo en entornos de baja actividad.

Desde phpMyAdmin: selecciona la base de datos, usa Exportar, formato SQL y guarda el archivo fuera de `public/`. Para restaurar, crea una base vacia o usa una copia controlada, importa el SQL y restaura `public/uploads` y `.env`.

No guardes backups dentro de `public/`. Protege los respaldos con acceso restringido y prueba restauraciones periodicamente.

## Roadmap Tecnico Pendiente

Prioridad media:

- Agregar indices compuestos segun patrones reales de consulta.
- Formalizar tablas single-row.
- Revisar consolidacion entre `tbl_login_logs` y `tbl_login_attempts`.
- Normalizar dominios externos si se vuelven administrables.
- Planificar limpieza de columnas SMS heredadas.

Prioridad baja:

- Estandarizar nombres de indices `idx_`, `uq_`, `fk_`.
- Evaluar `TEXT` para `user_agent` en auditoria/login logs.
- Agregar `updated_by` en configuraciones sensibles si se requiere trazabilidad directa.

## Versionado

Version estable actual: **v3.0.0**  
Fecha: **2026-05-13**

No hacer `push` automaticamente. Para publicar, usar los comandos indicados por el responsable del repositorio.
