# Skeleton MVC v3.0 — PHP 8.3 + MySQL + DashboardKit

Sistema web base (skeleton) con arquitectura MVC en PHP puro. Usa la plantilla visual **DashboardKit Free Admin Template** (Bootstrap 5) y está diseñado como punto de partida limpio y seguro para futuros proyectos.

**v3.0** incluye: Composer + autoload PSR-4, variables de entorno (.env), modo oscuro por usuario, internacionalización (ES/EN), gestión de idiomas, información del sistema, manuales descargables y **bloqueo de sesión por inactividad**.

---

## Tecnologías

| Tecnología | Descripción |
|---|---|
| PHP 8.3 | Lenguaje principal, sin frameworks |
| Composer 2 | Gestión de dependencias y autoload PSR-4 |
| vlucas/phpdotenv | Variables de entorno desde `.env` |
| phpmailer/phpmailer | Envío de correos SMTP |
| MySQL 8 | Base de datos relacional |
| PDO | Acceso a datos con consultas preparadas |
| Bootstrap 5 | UI base (vía DashboardKit) |
| DashboardKit Free | Plantilla visual admin (dark/light mode) |
| DataTables 1.13 | Tablas con búsqueda y paginación (CDN) |
| SweetAlert2 11 | Alertas y confirmaciones modales (CDN) |
| Select2 4.1 | Dropdowns con búsqueda (CDN) |
| jQuery 3.7 | Requerido por DataTables y Select2 (CDN) |

---

## Arquitectura MVC

| Capa | Carpeta | Responsabilidad |
|---|---|---|
| Model | `/app/Models/` | Consultas SQL con PDO, sin lógica de negocio |
| View | `/app/Views/` | HTML + PHP para presentación, sin SQL |
| Controller | `/app/Controllers/` | Flujo, validaciones, respuestas |
| Core | `/core/` | Clases base reutilizables |
| Config | `/config/` | Parámetros centralizados |
| Public | `/public/` | Único punto de entrada + assets |

---

## Estructura de carpetas

```
/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php              ← Login, logout
│   │   ├── DashboardController.php         ← Dashboard principal
│   │   ├── ProfileController.php           ← Mi Perfil + foto + preferencias
│   │   ├── AccountController.php           ← Mi Cuenta (email + password)
│   │   ├── UsersController.php             ← CRUD de usuarios (solo admin)
│   │   ├── LanguagesController.php         ← CRUD de idiomas (solo admin)
│   │   ├── SystemInformationController.php ← Info del sistema + manuales
│   │   ├── LockController.php              ← Pantalla de bloqueo + desbloqueo
│   │   └── SecurityController.php          ← Configuración de seguridad (solo admin)
│   ├── Models/
│   │   ├── User.php                        ← CRUD usuarios con JOINs
│   │   ├── Language.php                    ← Idiomas del sistema
│   │   ├── SystemSetting.php               ← Configuración del sistema
│   │   ├── UserManual.php                  ← Manuales de usuario
│   │   ├── Role.php                        ← Roles del sistema
│   │   ├── Status.php                      ← Estados del sistema
│   │   ├── LoginLog.php                    ← Registro de intentos de login
│   │   └── SecuritySetting.php             ← Configuración de bloqueo de sesión
│   └── Views/
│       ├── auth/login.php
│       ├── dashboard/index.php
│       ├── profile/
│       ├── account/
│       ├── users/
│       ├── languages/
│       ├── system_information/
│       ├── manuals/
│       ├── security/sessions/
│       ├── lock.php
│       ├── layouts/
│       └── errors/
├── config/
│   ├── app.php                      ← Configuración general (lee desde .env)
│   └── database.php                 ← Conexión BD (lee desde .env)
├── core/
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   ├── Database.php
│   ├── Auth.php
│   ├── Lang.php
│   ├── Session.php
│   ├── CSRF.php
│   ├── Validator.php
│   ├── Redirect.php
│   ├── Logger.php
│   ├── Audit.php
│   ├── ErrorHandler.php
│   └── helpers.php                  ← env(), __(), can()
├── lang/
│   ├── es.php                       ← Español (idioma base)
│   └── en.php                       ← English
├── database/
│   ├── db_skeleton.sql              ← Esquema base (001)
│   ├── 002_update_users_roles_statuses_profile_image.sql
│   ├── 003_add_preferences_languages_system_info_manuals.sql
│   ├── 004_add_security_session_settings.sql
│   ├── 005_add_modules_permissions_role_permissions.sql
│   └── 006_add_audit_logs.sql
├── docs/
│   └── despliegue-hosting-compartido.md
├── logs/
│   └── .gitkeep
├── vendor/                          ← Generado por Composer (no subir a Git)
├── public/
│   ├── index.php                    ← Front controller + rutas
│   ├── .htaccess
│   ├── uploads/
│   └── assets/
├── .env.example                     ← Plantilla de variables de entorno (sí subir)
├── .env                             ← Variables de entorno locales (NO subir)
├── .htaccess                        ← Protección raíz del proyecto
├── composer.json                    ← Dependencias (sí subir)
├── composer.lock                    ← Versiones exactas (sí subir)
└── README.md
```

---

## Requisitos del entorno

| Requisito | Versión mínima |
|---|---|
| PHP | 8.3 |
| MySQL | 8.0 |
| Composer | 2.x |
| Apache / Laragon | con `mod_rewrite` habilitado |

**Extensiones PHP requeridas:** `pdo`, `pdo_mysql`, `mbstring`, `json`, `fileinfo`

---

## Instalación local (Laragon / Windows)

### Paso 1 — Copiar el proyecto

Coloca la carpeta `template` dentro de `C:\laragon\www\`:

```
C:\laragon\www\template\
```

### Paso 2 — Instalar dependencias con Composer

```bash
cd C:\laragon\www\template
composer install
```

Esto crea la carpeta `vendor/` y habilita el autoloader PSR-4 y las variables de entorno.

### Paso 3 — Crear el archivo .env

```cmd
copy .env.example .env
```

Edita `.env` y ajusta los valores a tu entorno local:

```env
APP_NAME="Skeleton"
APP_URL="http://localhost/template/public"
APP_ENV=local
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_skeleton
DB_USERNAME=root
DB_PASSWORD=
```

> Si usas virtual host en Laragon (recomendado), cambia `APP_URL` a `http://template.test/public`.

### Paso 4 — Importar la base de datos

1. Inicia Laragon → **Start All**.
2. Abre `http://localhost/phpmyadmin`.
3. Crea la base de datos `db_skeleton` con cotejamiento `utf8mb4_unicode_ci`.
4. Importa los archivos SQL en orden desde la carpeta `database/`:

| Archivo | Descripción |
|---|---|
| `db_skeleton.sql` | Esquema base |
| `002_update_users_roles_statuses_profile_image.sql` | Roles, estados, foto de perfil |
| `003_add_preferences_languages_system_info_manuals.sql` | Idiomas, sistema, manuales |
| `004_add_security_session_settings.sql` | Bloqueo de sesión |
| `005_add_modules_permissions_role_permissions.sql` | Módulos y permisos por rol |
| `006_add_audit_logs.sql` | Auditoría de acciones |

### Paso 5 — Acceder al sistema

```
http://localhost/template/public/login
```

O con virtual host:

```
http://template.test/public/login
```

---

## Instalación en hosting compartido con SSH

```bash
# 1. Subir el proyecto al servidor (excluye vendor/, .env y logs/)
# 2. Conectarse por SSH y ejecutar:
cd /ruta/al/proyecto
composer install --no-dev --optimize-autoloader

# 3. Crear .env
cp .env.example .env
nano .env   # editar con los datos de producción

# 4. Importar SQL desde phpMyAdmin del hosting

# 5. Configurar dominio apuntando a /public
```

> **APP_DEBUG=false** en producción para no exponer detalles técnicos.

Para detalle completo ver [docs/despliegue-hosting-compartido.md](docs/despliegue-hosting-compartido.md).

---

## Instalación en hosting compartido sin SSH

1. Ejecuta `composer install --no-dev --optimize-autoloader` en tu computadora local.
2. Sube el proyecto **incluyendo** la carpeta `vendor/` al hosting (por FTP o panel de archivos).
3. Crea el archivo `.env` manualmente en el hosting y configura las variables.
4. Importa los archivos SQL desde el phpMyAdmin del hosting.
5. Apunta el dominio/subdominio a la carpeta `/public` si el hosting lo permite.
6. Si no puedes cambiar el document root a `/public`, edita el `.htaccess` raíz y descomenta las líneas de reenvío a `/public`.

> **Importante:** No subas `.env` ni `logs/` al repositorio. Solo súbelos manualmente al servidor.

Para detalle completo ver [docs/despliegue-hosting-compartido.md](docs/despliegue-hosting-compartido.md).

---

## Variables de entorno (.env)

| Variable | Descripción | Ejemplo |
|---|---|---|
| `APP_NAME` | Nombre del sistema | `"Skeleton"` |
| `APP_URL` | URL base pública (sin barra final) | `http://localhost/template/public` |
| `APP_ENV` | Entorno: `local` o `production` | `local` |
| `APP_DEBUG` | Muestra detalles de error: `true` o `false` | `true` |
| `APP_TIMEZONE` | Zona horaria PHP | `America/El_Salvador` |
| `DB_HOST` | Host de MySQL | `127.0.0.1` |
| `DB_PORT` | Puerto de MySQL | `3306` |
| `DB_DATABASE` | Nombre de la base de datos | `db_skeleton` |
| `DB_USERNAME` | Usuario de MySQL | `root` |
| `DB_PASSWORD` | Contraseña de MySQL | *(vacío en Laragon)* |
| `SESSION_LIFETIME` | Tiempo de sesión en segundos | `1800` (30 min) |
| `LOG_PATH` | Ruta relativa del log de errores | `logs/error.log` |

> El archivo `.env.example` es la plantilla. Cópialo como `.env` y nunca lo subas al repositorio.

---

## Credenciales de prueba

| Campo | Valor |
|---|---|
| Correo electrónico | `admin@skeleton.local` |
| Contraseña | `Admin123*` |
| Rol | Administrador |

La contraseña está almacenada con `password_hash()` bcrypt (cost=12) en la base de datos.

---

## Rutas del sistema

| Método | Ruta | Descripción | Acceso |
|---|---|---|---|
| GET | `/login` | Formulario de login | Público |
| POST | `/login` | Procesar credenciales | Público |
| POST | `/logout` | Cerrar sesión | Autenticado |
| GET | `/dashboard` | Dashboard principal | Autenticado |
| GET | `/profile` | Ver perfil + preferencias | Autenticado |
| GET | `/profile/edit` | Formulario editar perfil + foto | Autenticado |
| POST | `/profile/update` | Guardar cambios de perfil | Autenticado |
| POST | `/profile/preferences` | Guardar tema e idioma | Autenticado |
| GET | `/account` | Ver cuenta | Autenticado |
| GET | `/account/edit-email` | Formulario cambiar correo | Autenticado |
| POST | `/account/update-email` | Guardar nuevo correo | Autenticado |
| GET | `/account/edit-password` | Formulario cambiar contraseña | Autenticado |
| POST | `/account/update-password` | Guardar nueva contraseña | Autenticado |
| GET | `/system-information` | Info del sistema + manuales | Autenticado |
| GET | `/system-information/edit` | Formulario editar info | Solo admin |
| POST | `/system-information/update` | Guardar info del sistema | Solo admin |
| GET | `/manuals/create` | Formulario subir manual | Solo admin |
| POST | `/manuals/store` | Subir manual | Solo admin |
| POST | `/manuals/toggle/{id}` | Activar/Desactivar manual | Solo admin |
| GET | `/manuals/download/{id}` | Descargar manual | Autenticado |
| GET | `/users` | Listado de usuarios (DataTables) | Solo admin |
| GET | `/users/create` | Formulario nuevo usuario | Solo admin |
| POST | `/users/store` | Crear usuario | Solo admin |
| GET | `/users/edit/{id}` | Formulario editar usuario | Solo admin |
| POST | `/users/update/{id}` | Guardar cambios de usuario | Solo admin |
| POST | `/users/delete/{id}` | Inactivar usuario | Solo admin |
| GET | `/languages` | Listado de idiomas | Solo admin |
| GET | `/languages/create` | Formulario nuevo idioma | Solo admin |
| POST | `/languages/store` | Crear idioma | Solo admin |
| GET | `/languages/edit/{id}` | Formulario editar idioma | Solo admin |
| POST | `/languages/update/{id}` | Guardar cambios de idioma | Solo admin |
| POST | `/languages/toggle/{id}` | Activar/Desactivar idioma | Solo admin |
| GET | `/lock` | Pantalla de bloqueo de sesión | Autenticado + bloqueado |
| POST | `/unlock` | Desbloquear sesión con contraseña | Autenticado + bloqueado |
| GET | `/security/sessions` | Configurar bloqueo por inactividad | Solo admin |
| POST | `/security/sessions/update` | Guardar configuración de seguridad | Solo admin |

---

## Novedades v3.0

### Modo oscuro y preferencias de usuario
- Cada usuario puede seleccionar **modo claro** u **oscuro** desde su perfil.
- Tema almacenado en `tbl_users.theme_preference`, activo en toda la sesión.
- Se aplica automáticamente al cargar cada página mediante `data-pc-theme`.

### Internacionalización (i18n)
- Sistema de traducción propio vía `Core\Lang` y el helper global `__($key)`.
- Archivos de traducción en `/lang/es.php` (base) y `/lang/en.php`.
- El idioma preferido del usuario se almacena en `tbl_users.language_id`.
- Fallback automático a Español si una clave no existe en el idioma activo.
- Para agregar un idioma nuevo: créalo en la sección **Idiomas** y luego agrega `/lang/{code}.php`.

### Módulo de idiomas (solo admin)
- CRUD completo para gestionar idiomas del sistema.
- Protección: el idioma predeterminado (Español) no puede desactivarse.
- Aviso automático si se crea un idioma sin archivo de traducción correspondiente.

### Información del Sistema
- Vista accesible para todos los usuarios autenticados.
- Muestra: año de lanzamiento, líder del proyecto, versión del sistema (SemVer).
- Solo el admin puede editar estos datos.

### Manuales de Usuario
- El admin puede subir archivos PDF, DOC o DOCX (máx. 10 MB).
- Todos los usuarios autenticados pueden descargarlos.
- El admin puede activar/desactivar manuales desde la misma vista.
- Los archivos se almacenan con nombre único en `public/uploads/manuals/`.

### Bloqueo de sesión por inactividad
- La sesión se bloquea automáticamente tras un período de inactividad configurable (por defecto 15 min / 900 seg).
- El bloqueo ocurre en el servidor: `Auth::requireAuth()` detecta el tiempo transcurrido y redirige a `/lock`.
- El usuario ve una pantalla de bloqueo con su avatar y nombre, donde ingresa su contraseña para continuar.
- Tras desbloquear, el sistema redirige automáticamente a la página donde estaba el usuario.
- El admin puede configurar el tiempo de inactividad y activar/desactivar el bloqueo desde **Seguridad → Sesiones**.
- La configuración se almacena en `tbl_security_settings` y se cachea 60 segundos en sesión.
- El JS en el layout detecta inactividad del cliente y muestra una advertencia 30 segundos antes de bloquear.

---

## Módulo de usuarios (v3.0)

- **Listado:** tabla con DataTables (búsqueda, paginación, orden), foto de perfil, nombre, email, teléfono, rol, estado, fecha de registro, botones de editar/inactivar.
- **Crear:** formulario con foto (opcional), todos los campos personales, contraseña obligatoria con confirmación, Select2 para rol y estado.
- **Editar:** igual que crear, pero contraseña y foto son opcionales (se mantiene la anterior si no se sube nueva).
- **Inactivar:** confirmación con SweetAlert2. Protecciones:
  - No puede inactivarse a sí mismo.
  - No puede inactivar al último administrador activo.
- **Acceso:** exclusivo para usuarios con rol `administrator`.

---

## Foto de perfil

- Se sube desde **Mi Perfil → Editar** o desde el formulario de usuario (admin).
- Formatos permitidos: JPG, PNG, WEBP (validado por MIME real, no solo extensión).
- Tamaño máximo: 2 MB.
- Se almacena en `public/uploads/profiles/` con nombre único (`avatar_{id}_{random}.ext`).
- Se muestra en: topbar, dashboard, vista de perfil, listado de usuarios.
- Fallback automático al avatar por defecto si el archivo no existe.

---

## Medidas de seguridad implementadas

| Amenaza | Solución |
|---|---|
| SQL Injection | PDO + consultas preparadas en todos los modelos |
| XSS | `htmlspecialchars()` en cada salida de variable en las vistas |
| CSRF | Token por sesión validado en todos los formularios POST |
| Contraseñas en texto plano | `password_hash()` bcrypt cost=12 |
| Fuerza bruta | Bloqueo automático por 15 min tras 5 intentos fallidos |
| Session fixation | `session_regenerate_id(true)` tras login y cambios sensibles |
| Sesión expirada | Timeout de 30 minutos de inactividad, redirección a login |
| Sesión desatendida | Bloqueo automático por inactividad configurable (default 15 min), requiere contraseña para continuar |
| Cookie insegura | `httponly=true`, `samesite=Lax`, `secure` en HTTPS |
| Enumeración de usuarios | Mensaje genérico en login para email y contraseña incorrectos |
| Acceso sin autenticación | `Auth::requireAuth()` en todas las rutas protegidas |
| Acceso sin rol admin | `Auth::requireAdmin()` en todas las rutas del módulo de usuarios |
| Upload malicioso | Validación MIME con `finfo_file()`, extensión y tamaño |
| Path traversal en uploads | Nombre de archivo generado internamente, nunca del cliente |
| Errores técnicos expuestos | Capturados en logs, mensajes genéricos al usuario |
| Headers HTTP inseguros | X-Frame-Options, X-Content-Type-Options, CSP, Referrer-Policy |

---

## Agregar un nuevo módulo (guía rápida)

1. Crea `app/Controllers/MiModuloController.php` extendiendo `Core\Controller`.
2. Crea `app/Models/MiModelo.php` extendiendo `Core\Model`.
3. Crea las vistas en `app/Views/mi_modulo/`.
4. Registra las rutas en `public/index.php`:
   ```php
   $router->get('/mi-modulo', [MiModuloController::class, 'index']);
   $router->post('/mi-modulo/guardar', [MiModuloController::class, 'store']);
   ```
5. Agrega el enlace al menú en `app/Views/layouts/sidebar.php`.

---

## Recuperación de contraseña

### Flujo
1. El usuario va a `/forgot-password` y escribe su correo.
2. El sistema valida el formato, aplica rate limit (3 solicitudes / 15 min), y busca el usuario.
3. Si el correo existe y el usuario está activo: genera un token seguro, lo guarda hasheado (SHA-256) en `tbl_password_resets` y envía un correo con el enlace.
4. **Siempre se muestra el mismo mensaje genérico** — nunca se revela si el correo existe.
5. El usuario abre el enlace (`/reset-password/{token}`), el sistema verifica el token.
6. El usuario ingresa su nueva contraseña (mínimo 10 chars, mayúscula, minúscula, número, especial).
7. La contraseña se actualiza con `password_hash()`, el token queda invalidado y se redirige a la vista de éxito.

### Tabla tbl_password_resets
Migración: `database/007_add_password_resets.sql`

| Campo | Tipo | Descripción |
|---|---|---|
| `token_hash` | VARCHAR(64) | SHA-256 del token (nunca el token plano) |
| `expires_at` | DATETIME | Fecha de expiración |
| `used_at` | DATETIME | NULL si activo, fecha si ya fue usado |
| `ip_address` | VARCHAR(45) | IP que solicitó el reset |

### Configuración SMTP en .env

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=mi_correo@gmail.com
MAIL_PASSWORD=contraseña_de_aplicacion
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=mi_correo@gmail.com
MAIL_FROM_NAME="Skeleton"

PASSWORD_RESET_TOKEN_EXPIRATION_MINUTES=60
PASSWORD_RESET_MAX_REQUESTS=3
PASSWORD_RESET_RATE_LIMIT_MINUTES=15
```

> **Gmail:** Activa la verificación en 2 pasos y genera una "Contraseña de aplicación" en tu cuenta Google. Usa esa contraseña (no tu contraseña normal) en `MAIL_PASSWORD`.

### Cómo probar en local
1. Configura las variables SMTP en `.env` con credenciales reales.
2. Ve a `http://localhost/template/public/forgot-password`.
3. Ingresa el correo del usuario administrador: `admin@skeleton.local`.
4. Revisa la bandeja del correo configurado.
5. Abre el enlace de recuperación.
6. Establece la nueva contraseña.
7. Inicia sesión con la nueva contraseña.

### Si el correo no llega
- Verifica `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_ENCRYPTION` en `.env`.
- Revisa la carpeta de **spam/correo no deseado**.
- Comprueba los logs en `logs/security.log` para mensajes de error SMTP.
- Gmail requiere contraseña de aplicación (no la contraseña normal).
- Prueba con un servicio SMTP de prueba como [Mailtrap](https://mailtrap.io) o [Mailpit](https://github.com/axllent/mailpit) en local.

### Mailtrap / Mailpit (pruebas locales sin SMTP real)
Para probar sin enviar correos reales:

```env
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario_mailtrap
MAIL_PASSWORD=tu_password_mailtrap
MAIL_ENCRYPTION=tls
```

---

## Notas de seguridad para producción

- En `.env` usa `APP_DEBUG=false` para no exponer trazas de error al usuario.
- En `.env` usa `APP_ENV=production`.
- Nunca subas el archivo `.env` al repositorio Git.
- La carpeta `vendor/` no debe subirse a Git; en hosting sin SSH súbela directamente al servidor.
- Activa HTTPS y el flag `secure` en cookies de sesión (`core/Session.php`).
- La carpeta `logs/` está protegida por `.htaccess` y no debe ser accesible públicamente.
- El directorio `dashboardkit-src/` (fuente del template) puede eliminarse en producción.
- La carpeta `public/uploads/` debe tener permisos de escritura para el proceso PHP.
