# Skeleton PHP MVC

Skeleton es un framework base desarrollado en PHP puro bajo arquitectura MVC (PDO, Composer y DashboardKit), diseñado para iniciar nuevos proyectos administrativos de forma rápida, segura y ordenada, sin rehacer autenticación, autorización, seguridad, auditoría, configuración y estructura visual desde cero.

Sobre Skeleton se pueden construir paneles administrativos, sistemas de gestión internos, backoffices y cualquier proyecto que necesite una base sólida de usuarios, roles/permisos, seguridad y auditoría ya resuelta.

Versión: **v3.0.0**
Fecha: **2026-05-13**

## Características Principales

- Login local y recuperación de contraseña por correo.
- SMTP administrable y prueba de envío.
- OAuth externo para Google, Microsoft 365 y GitHub.
- MFA por correo/autenticador (TOTP), política de contraseñas e intentos fallidos.
- Rate limiting (`RateLimitService`) en MFA TOTP, desbloqueo de sesión, recuperación de contraseña por IP y pruebas administrativas de SMTP/OAuth.
- Guard opcional en el Router (`core/Router.php`) para declarar `guest`/`auth`/`permission` por ruta, como segunda capa de defensa junto a los checks existentes en cada controlador.
- Gestión de sesiones activas, historial de sesiones y bloqueo por inactividad.
- Usuarios, roles, permisos y validación de permisos en backend.
- Auditoría, glosario de auditoría y exportaciones Excel.
- Apariencia: logo, favicon, fondo de login y colores.
- Manuales del sistema y Dashboard.
- Salud del Sistema y mantenimiento/limpieza de datos temporales.
- Notificaciones internas.
- Modo claro/oscuro, traducciones con `__()`, SweetAlert2, CSRF y páginas de error.
- UploadService para perfiles, manuales y apariencia.

## Requisitos

- PHP 8.3 o superior.
- MySQL 8 o MariaDB compatible.
- Composer.
- Laragon en Windows como entorno local sugerido.
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`, `curl`, `zip` y `gd` o `imagick`.
- Navegador moderno: Chrome, Edge, Firefox o equivalente.

## Instalación Local Desde Cero

1. Clonar el repositorio:

```bash
git clone URL_DEL_REPOSITORIO skeleton
cd skeleton
```

2. Instalar dependencias:

```bash
composer install
```

3. Crear el archivo de entorno:

```bash
cp .env.example .env
```

En Windows (símbolo del sistema o PowerShell):

```bash
copy .env.example .env
```

4. Configurar como mínimo estas variables en `.env`:

```env
APP_NAME="Skeleton"
APP_ENV=local
APP_DEBUG=true
APP_URL="http://localhost/skeleton/public"
APP_KEY=GENERA_UNA_CLAVE_SEGURA

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_skeleton
DB_USERNAME=root
DB_PASSWORD=
```

Si vas a usar correo/SMTP, configura también `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME` (ver `.env.example` para el detalle completo, incluyendo sesión, logs, recuperación de contraseña y 2FA).

5. Generar `APP_KEY`:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Copia el valor generado en `APP_KEY`. No existe un comando de instalación que la genere automáticamente; debe definirse manualmente con este método.

6. Crear una base de datos vacía. Desde phpMyAdmin (crear base `db_skeleton`) o por SQL:

```sql
CREATE DATABASE db_skeleton CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

7. Importar el schema consolidado:

```text
database/schema/skeleton_schema.sql
```

Importante: para una instalación limpia se importa **únicamente** este schema consolidado. No se deben ejecutar migraciones históricas después. Las migraciones en `database/migrations` son referencia histórica del proyecto y solo aplican para actualizar entornos ya existentes que partieron de una versión anterior del schema.

8. Configurar el servidor local (Laragon, XAMPP, Apache o Nginx):

- DocumentRoot recomendado: la carpeta `public`.
- Si usas Laragon con el proyecto en una subcarpeta, `APP_URL` queda así:

```text
http://localhost/skeleton/public
```

9. Verificar permisos de escritura en las carpetas reales del proyecto:

- `public/uploads`
- `public/uploads/profiles`
- `public/uploads/manuals`
- `public/uploads/appearance`
- `logs`
- `storage/logs` si existe
- `storage/cache` si existe

10. Acceder al sistema:

```text
http://localhost/skeleton/public
```

11. Credenciales iniciales:

```text
Correo: admin@example.com
Password temporal: Admin123*
```

12. Al primer ingreso, el sistema solicita el cambio obligatorio de esta contraseña temporal antes de continuar.

13. Pruebas básicas después de instalar:

- Login con el usuario administrador seed.
- Dashboard carga correctamente.
- Módulo de Usuarios.
- Seguridad > MFA (correo y autenticador).
- Auditoría.
- Seguridad > SMTP (prueba de envío si ya se configuró).
- Salud del Sistema.
- Mantenimiento / limpieza de datos temporales.

14. Validar traducciones:

```bash
php scripts/check_lang_keys.php
```

Resultado esperado:

```text
Total ES: 1345
Total EN: 1345
Faltantes EN: 0
Faltantes ES: 0
```

15. Validar Composer:

```bash
composer validate
```

Puede mostrar una advertencia no bloqueante relacionada con el campo `license`; no impide la instalación.

## Crear Un Nuevo Proyecto A Partir De Skeleton

1. Clonar o copiar Skeleton como plantilla:

```bash
git clone URL_DEL_REPOSITORIO nombre-nuevo-proyecto
cd nombre-nuevo-proyecto
```

2. Eliminar el historial Git heredado de Skeleton, si el nuevo proyecto tendrá su propio historial:

```bash
rm -rf .git
```

En Windows PowerShell:

```powershell
Remove-Item -Recurse -Force .git
```

3. Inicializar el repositorio del nuevo proyecto:

```bash
git init
git add .
git commit -m "Inicializar proyecto basado en Skeleton"
```

4. Crear la rama principal según la convención que se vaya a usar (`main`, `dev` u otra).

5. Adaptar la identidad del nuevo proyecto:

- `APP_NAME` en `.env`.
- Título y contenido de este `README.md`.
- Branding visual (logo, favicon) desde Apariencia.
- Nombre de la base de datos.

6. Crear una nueva base de datos exclusiva del nuevo proyecto e importar el schema consolidado (ver "Instalación Local Desde Cero").

7. Cambiar la contraseña del usuario administrador inicial inmediatamente después del primer ingreso.

8. Configurar SMTP propio del nuevo proyecto.

9. Configurar OAuth propio si el nuevo proyecto usará login externo.

10. Revisar dominios permitidos en Seguridad > Autenticación, si aplica restricción por dominio.

11. Revisar roles y permisos iniciales según las necesidades del nuevo proyecto.

12. Empezar el desarrollo de módulos nuevos siguiendo [Crear nuevo modulo](docs/crear-nuevo-modulo.md).

Notas importantes:

- No reutilizar el `.env` de otro proyecto; cada proyecto debe tener su propia clave, credenciales y configuración.
- No subir `.env` al repositorio.
- No subir backups, dumps con datos reales, `vendor/` ni `node_modules/`.

## Estructura Del Proyecto

| Carpeta | Propósito |
|---|---|
| `app/Controllers` | Controladores MVC y flujo HTTP. |
| `app/Models` | Acceso a datos con PDO. |
| `app/Services` | Servicios de dominio: correo, OAuth, uploads, sesiones, salud, limpieza, rate limit. |
| `app/Views` | Vistas PHP. |
| `app/Views/layouts` | Header, sidebar, topbar y footer. |
| `config` | Configuración de aplicación, DB, uploads y auditoría. |
| `core` | Router, Auth, Controller, Session, CSRF, helpers y base técnica. |
| `database/schema` | Schema SQL consolidado oficial para instalación limpia. |
| `database/migrations` | Migraciones incrementales, de referencia histórica o para actualizar entornos existentes. |
| `docs` | Documentación operativa adicional. |
| `lang` | Traducciones. |
| `public` | Punto de entrada y assets públicos. |
| `public/assets` | CSS, JS, fuentes e imágenes base. |
| `public/uploads` | Archivos subidos por usuarios, no versionar contenido real. |
| `scripts` | Scripts de mantenimiento y validación del proyecto (por ejemplo `check_lang_keys.php`). |
| `.env.example` | Plantilla de variables de entorno; copiar como `.env` y no versionar `.env`. |
| `composer.json` | Dependencias PHP y autoload PSR-4 (`App\`, `Core\`). |

## Esquema De Base De Datos

| Tabla | Propósito |
|---|---|
| `tbl_users` | Usuarios, credenciales, estado, rol, preferencias y MFA. |
| `tbl_roles` | Roles base del sistema. |
| `tbl_permissions` | Permisos granulares. |
| `tbl_role_permissions` | Asignación de permisos a roles. |
| `tbl_modules` | Módulos del sistema y metadatos de menú. |
| `tbl_statuses` | Estados generales: active, inactive, blocked. |
| `tbl_languages` | Idiomas disponibles. |
| `tbl_audit_logs` | Auditoría de acciones del sistema. |
| `tbl_login_logs` | Registro histórico de logins. |
| `tbl_login_attempts` | Intentos fallidos y protección de acceso. |
| `tbl_rate_limits` | Control genérico de intentos por acción (MFA TOTP, desbloqueo de sesión, recuperación de contraseña por IP, pruebas admin SMTP/OAuth) vía `RateLimitService`. |
| `tbl_security_settings` | Bloqueo por inactividad. |
| `tbl_smtp_settings` | Configuración SMTP cifrada. |
| `tbl_mfa_settings` | Configuración global MFA. |
| `tbl_authentication_settings` | Métodos de autenticación y dominios permitidos. |
| `tbl_external_auth_providers` | Proveedores OAuth. |
| `tbl_user_external_accounts` | Vínculos entre usuarios y cuentas externas. |
| `tbl_password_resets` | Tokens hasheados de recuperación de contraseña. |
| `tbl_password_policies` | Reglas de contraseñas. |
| `tbl_password_histories` | Historial de hashes para prevenir reutilización. |
| `tbl_email_change_verifications` | Verificación de cambio de correo. |
| `tbl_two_factor_codes` | Códigos temporales MFA. |
| `tbl_user_sessions` | Sesiones activas e historial de revocación. |
| `tbl_system_settings` | Información general del sistema. |
| `tbl_user_manuals` | Metadatos de manuales subidos. |
| `tbl_appearance_settings` | Logo, favicon, fondo de login y colores. |
| `tbl_notifications` | Notificaciones internas. |

Dominios principales:

- Identidad y seguridad: usuarios, sesiones, MFA, intentos, recuperación, política de contraseñas.
- Autorización: roles, permisos, módulos y asignaciones.
- Auditoría: eventos, glosario desde configuración y exportación.
- Configuración: SMTP, autenticación, apariencia, sistema y salud.
- Contenido/manuales: metadatos de manuales y archivos en `public/uploads`.
- Notificaciones: mensajes internos por usuario.
- Mantenimiento/temporales: limpieza controlada de tokens, códigos MFA, límites de tasa, sesiones, intentos, logs y notificaciones.

## Convenciones De Base De Datos

- Tablas con prefijo `tbl_`.
- Columnas en `snake_case`.
- Llave primaria como `id`.
- Llaves foráneas como `user_id`, `role_id`, `status_id`.
- Timestamps habituales: `created_at`, `updated_at`, `deleted_at`.
- Hashes con sufijo `_hash`.
- Secretos cifrados con sufijo `_enc` cuando aplique.
- `tbl_users.status_id` es la fuente oficial del estado del usuario.
- No usar `tbl_users.status`; fue retirado.

## Crear Nuevos Modulos

1. Crear la tabla SQL con PK, FK, índices y timestamps.
2. Crear modelo en `app/Models`.
3. Crear controlador en `app/Controllers`.
4. Crear vistas en `app/Views`.
5. Registrar rutas en `public/index.php`.
6. Crear módulo y permisos base en SQL.
7. Asignar permisos al rol Administrador.
8. Validar permisos en backend con `Auth::requirePermission()`.
9. Mostrar menú solo si el usuario tiene permiso con `can()`.
10. Usar CSRF en acciones `POST`.
11. Registrar auditoría en acciones críticas.
12. Agregar traducciones en `lang`.
13. Respetar modo oscuro y estructura DashboardKit.
14. Usar UploadService para archivos.

Regla de layout: después de `.page-header` debe venir una `.row` antes de colocar `.card`. No iniciar directamente con `.card` después de `.page-header`.

## Reglas SQL Importantes

- No usar `IF NOT EXISTS`.
- No usar `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`.
- Crear migraciones compatibles con phpMyAdmin.
- Indicar pasos manuales cuando una migración dependa del estado previo.
- Importar `database/schema/skeleton_schema.sql` solo en una base vacía.

## Documentacion Adicional

- [Crear nuevo modulo](docs/crear-nuevo-modulo.md)
- [Convenciones de desarrollo](docs/convenciones-desarrollo.md)
- [Permisos y auditoria](docs/permisos-y-auditoria.md)
- [Despliegue en hosting compartido](docs/despliegue-hosting-compartido.md)
- [Backups y restauracion](docs/backups-restauracion.md)
- [Base de datos](database/README.md)

## Comandos Útiles

```bash
composer install
composer validate
php scripts/check_lang_keys.php
git status
git log --oneline -5
```

## Recomendaciones Después De Instalar

1. Cambiar la contraseña del administrador seed.
2. Configurar SMTP.
3. Activar MFA.
4. Revisar usuarios, roles y permisos.
5. Configurar OAuth solo si se usará login externo.
6. Revisar `APP_ENV` y `APP_DEBUG` antes de producción.
7. Verificar que `.env` no esté versionado.
8. Hacer backup antes de cambios grandes.
9. No usar las credenciales del proyecto base en producción.

## Seguridad

- No subir `.env`.
- No subir `vendor/`.
- No subir `node_modules/`.
- No versionar uploads reales, dumps con datos reales, tokens ni secretos.
- Usar `APP_DEBUG=false` en producción.
- Usar HTTPS en producción.
- Revisar auditoría y permisos por rol periódicamente.

## Backups

Respaldar:

- Base de datos.
- `public/uploads`.
- `.env`.
- Archivos de configuración relevantes.

Frecuencia recomendada:

- Diario para sistemas activos.
- Antes de despliegues o cambios de base de datos.
- Semanal como mínimo en entornos de baja actividad.

Desde phpMyAdmin: selecciona la base de datos, usa Exportar, formato SQL y guarda el archivo fuera de `public/`. Para restaurar, crea una base vacía o usa una copia controlada, importa el SQL y restaura `public/uploads` y `.env`.

No guardes backups dentro de `public/`. Protege los respaldos con acceso restringido y prueba restauraciones periódicamente.

## Roadmap Tecnico Pendiente

Prioridad media:

- Agregar índices compuestos según patrones reales de consulta.
- Formalizar tablas single-row.
- Revisar consolidación entre `tbl_login_logs` y `tbl_login_attempts`.
- Normalizar dominios externos si se vuelven administrables.
- `tbl_users.remember_token` existe en el schema como campo reservado/documentado, sin funcionalidad de login persistente ("recordarme") implementada actualmente. Pendiente decidir entre implementarlo por completo (tokens seguros, hash, expiración, revocación) o eliminarlo con una migración controlada.

Prioridad baja:

- Estandarizar nombres de índices `idx_`, `uq_`, `fk_`.
- Evaluar `TEXT` para `user_agent` en auditoría/login logs.
- Agregar `updated_by` en configuraciones sensibles si se requiere trazabilidad directa.

## Checklist De Verificación Inicial

Al terminar una instalación nueva, verificar:

- [ ] Importar el schema limpio (`database/schema/skeleton_schema.sql`) en una base vacía.
- [ ] Configurar `.env` (base de datos, `APP_URL`, `APP_KEY`, SMTP).
- [ ] Entrar con el usuario administrador seed (`admin@example.com` / `Admin123*`).
- [ ] Completar el cambio de contraseña obligatorio del primer acceso.
- [ ] Revisar que el Dashboard carga correctamente.
- [ ] Revisar el módulo de Usuarios.
- [ ] Revisar Seguridad > MFA (email y autenticador).
- [ ] Revisar Seguridad > SMTP y ejecutar una prueba de envío.
- [ ] Revisar Seguridad > Autenticación/OAuth si se usará login externo.
- [ ] Revisar Auditoría.
- [ ] Revisar Mantenimiento / limpieza de datos temporales.
- [ ] Ejecutar `php scripts/check_lang_keys.php` (debe devolver código 0).
- [ ] Confirmar que `.env` no quedó incluido en el control de versiones.

## Versionado

Versión actual: **v3.0.0**
Fecha: **2026-05-13**
