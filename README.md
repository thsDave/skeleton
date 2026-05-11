# Skeleton MVC v3.0 — PHP 8.3 + MySQL + DashboardKit

Sistema web base (skeleton) con arquitectura MVC en PHP puro. Usa la plantilla visual **DashboardKit Free Admin Template** (Bootstrap 5) y está diseñado como punto de partida limpio y seguro para futuros proyectos.

**v3.0** incluye: Composer + autoload PSR-4, variables de entorno (.env), modo oscuro por usuario, internacionalización (ES/EN), gestión de idiomas, información del sistema, manuales descargables, **bloqueo de sesión por inactividad**, recuperación de contraseña por correo, **configuración SMTP administrable**, **configuración global de MFA**, **verificación en 2 pasos por usuario (email y TOTP)**, **login externo OAuth (Google, Microsoft, GitHub)**, **restricción por dominio institucional** y **política de contraseñas configurable**.

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
│   │   ├── SecurityController.php          ← Configuración de seguridad (solo admin)
│   │   ├── SmtpSettingsController.php      ← Configuración SMTP administrable
│   │   └── PasswordResetController.php     ← Recuperación de contraseña
│   ├── Models/
│   │   ├── User.php                        ← CRUD usuarios con JOINs
│   │   ├── Language.php                    ← Idiomas del sistema
│   │   ├── SystemSetting.php               ← Configuración del sistema
│   │   ├── UserManual.php                  ← Manuales de usuario
│   │   ├── Role.php                        ← Roles del sistema
│   │   ├── Status.php                      ← Estados del sistema
│   │   ├── LoginLog.php                    ← Registro de intentos de login
│   │   ├── SecuritySetting.php             ← Configuración de bloqueo de sesión
│   │   ├── SmtpSettings.php               ← Configuración SMTP (cifrado AES-256)
│   │   └── PasswordReset.php               ← Tokens de recuperación de contraseña
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
│       ├── security/smtp/
│       ├── auth/
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
│   ├── Crypt.php                    ← Cifrado AES-256-CBC (APP_KEY)
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
| `007_add_password_resets.sql` | Recuperación de contraseña por correo |
| `008_add_smtp_settings.sql` | Configuración SMTP administrable |
| `009_add_mfa_settings.sql` | Configuración global MFA |
| `010_add_user_two_factor_authentication.sql` | Verificación en 2 pasos por usuario |
| `011_remove_sms_from_mfa.sql` | Eliminar datos SMS de MFA (usuarios con method=sms deshabilitados) |
| `012_add_login_security_settings.sql` | Configuración de intentos fallidos y bloqueo |
| `013_add_external_auth_providers.sql` | Login externo OAuth (Google, Microsoft, GitHub) |
| `014_add_authentication_methods.sql` | Métodos de autenticación y proveedores externos |
| `015_add_appearance_settings.sql` | Apariencia del sistema (logo, favicon, colores) |
| `016_add_external_domain_restrictions.sql` | Restricción por dominio institucional |
| `017_add_password_policy.sql` | Política de contraseñas e historial |
| `018_add_force_password_change.sql` | Columna `force_password_change` en `tbl_users` |

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
| `APP_KEY` | Clave para cifrado AES-256 (APP_KEY). Genera con `php -r "echo bin2hex(random_bytes(32));"` | *(64 hex chars)* |
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
| GET | `/security/smtp` | Ver/editar configuración SMTP | Solo admin |
| POST | `/security/smtp/update` | Guardar configuración SMTP | Solo admin |
| POST | `/security/smtp/test` | Enviar correo de prueba | Solo admin |
| GET | `/security/authconfig` | Módulo Autenticación (pestañas MFA e Intentos fallidos) | `security_mfa.view` |
| POST | `/security/authconfig/mfa/update` | Guardar configuración MFA | `security_mfa.edit` |
| POST | `/security/authconfig/login-security/update` | Guardar configuración de intentos fallidos | `security_mfa.edit` |
| GET | `/security/mfa` | Redirige a `/security/authconfig` (compatibilidad) | — |
| POST | `/users/unlock/{id}` | Desbloquear usuario | `users.unlock` |

---

## Dashboard principal

El dashboard muestra información diferente según el rol del usuario.

### Qué ve el administrador (o usuario con `users.view`)
- **KPIs** (8 tarjetas): total usuarios, activos, inactivos, con 2FA, sin 2FA, perfil incompleto, idiomas activos, estado SMTP.
- **Gráficas** (Chart.js 4): usuarios por rol (doughnut), estado 2FA (doughnut), métodos 2FA activos (doughnut), estado de usuarios (barra).
- **Seguridad del sistema**: estado SMTP, MFA por correo y autenticador, conteo de usuarios con/sin 2FA, tiempo de bloqueo por inactividad.
- **Estado del sistema**: versión, año, líder, idioma base, módulos activos, permisos registrados.
- **Usuarios y perfiles**: último usuario registrado, conteos de foto de perfil y perfil completo.
- **Actividad reciente**: últimas 10 entradas de auditoría (requiere `audit_logs.view`).

### Qué ve un usuario normal (Consultor, Usuario, etc.)
- Tarjetas rápidas: Mi Perfil, Mi Cuenta, Seguridad (con tiempo real de bloqueo).
- Estado personal de 2FA, tema e idioma actual.
- Rol y estado de la cuenta.

### Origen de los datos
| Métrica | Fuente |
|---|---|
| KPIs de usuarios | `tbl_users` + `tbl_statuses` |
| Gráfica por roles | `tbl_users` + `tbl_roles` |
| Métodos 2FA | `tbl_users.two_factor_method` |
| Estado SMTP | `tbl_smtp_settings.is_verified` |
| Configuración MFA | `tbl_mfa_settings` |
| Bloqueo de sesión | `tbl_security_settings` |
| Estado del sistema | `tbl_system_settings` |
| Auditoría reciente | `tbl_audit_logs` |

### Si no hay datos suficientes
- Las gráficas muestran un mensaje amigable en lugar de un canvas vacío.
- Si la información del sistema no está configurada, la tarjeta muestra "No configurada".
- Si no hay actividad de auditoría, se muestra "No hay actividad reciente disponible."
- No se inventan datos: todo viene de la base de datos real.

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

### Configuración SMTP administrable
- El administrador puede configurar el servidor de correo saliente desde **Seguridad → SMTP** sin editar archivos.
- La configuración en BD tiene prioridad sobre las variables del `.env` (que sirven de respaldo).
- La contraseña SMTP se almacena cifrada con **AES-256-CBC** usando la clave `APP_KEY` del `.env`.
- El botón "Probar configuración" envía un correo de prueba al correo del admin autenticado y registra el resultado.
- Requiere importar `database/008_add_smtp_settings.sql` y definir `APP_KEY` en `.env`.

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

## Seguridad > Autenticación

El módulo **Seguridad > Autenticación** está en la ruta `/security/authconfig` (la antigua `/security/mfa` redirige automáticamente). Los permisos internos se mantienen como `security_mfa.view` y `security_mfa.edit`. El controlador principal es `AuthConfigController`.

El módulo ahora agrupa dos configuraciones de seguridad relacionadas con el acceso al sistema:

### Pestaña MFA

Permite al administrador habilitar o deshabilitar globalmente los métodos de autenticación multifactor disponibles para los usuarios del sistema.

### Métodos disponibles

| Método | Requiere | Estado |
|---|---|---|
| Correo electrónico | SMTP activo y verificado | Disponible |
| Aplicación de autenticación | Nada externo | Disponible |

> **SMS no está incluido en esta plantilla base.** El canal SMS fue removido en v3.0 para mantener el proyecto libre de dependencias de proveedores externos. Si necesitas SMS, integra un proveedor (Twilio, Vonage, etc.) como extensión.

### Cómo habilitar MFA por correo
1. Primero configura y prueba SMTP desde **Seguridad → SMTP**.
2. El test SMTP debe ser exitoso (`is_verified = 1`).
3. Entra a **Seguridad → MFA** y activa el toggle de "MFA por Correo".
4. Guarda los cambios.

> Si SMTP no está verificado, el sistema rechaza la activación y muestra un mensaje explicativo.

### Cómo habilitar la Aplicación de Autenticación
Simplemente activa el toggle "MFA por Aplicación de autenticación" y guarda. No requiere ningún proveedor externo.

### Migración SQL (MFA)
```
database/009_add_mfa_settings.sql
```
Crea `tbl_mfa_settings`, inserta el módulo `security_mfa`, sus 2 permisos (`view`, `edit`) y los asigna al rol Administrador.

---

### Pestaña Intentos fallidos

Configura la protección contra ataques de fuerza bruta en el inicio de sesión.

#### Configuración por usuario/correo

| Campo | Descripción | Default |
|---|---|---|
| Activar protección | Habilita el bloqueo por intentos fallidos | Activado |
| Máx. intentos por usuario | Intentos antes de bloquear la cuenta | 5 |
| Ventana de tiempo | Período en minutos en que cuentan los intentos | 15 min |
| Tiempo de bloqueo | Duración del bloqueo tras superar el límite | 15 min |

#### Configuración por IP

| Campo | Descripción | Default |
|---|---|---|
| Activar protección por IP | Habilita el bloqueo por IP | Activado |
| Máx. intentos por IP | Intentos de la misma IP antes de bloquear | 20 |
| Ventana de tiempo | Período en minutos en que cuentan los intentos | 15 min |
| Tiempo de bloqueo | Duración del bloqueo de la IP | 30 min |

#### Cómo funciona el bloqueo por usuario/correo

1. Si la protección está activa, el sistema lleva un contador en `tbl_users.failed_login_attempts`.
2. Cuando el contador supera el límite configurado, se establece `tbl_users.locked_until` con la fecha de desbloqueo.
3. Mientras `locked_until > now()`, cualquier intento de login con ese usuario muestra un mensaje genérico.
4. Un login exitoso borra el contador y limpia `locked_until`.

#### Cómo funciona el bloqueo por IP

1. Si la protección por IP está activa, el sistema consulta `tbl_login_attempts` para contar intentos fallidos de la IP en la ventana de tiempo.
2. Si el número de intentos supera el límite configurado, el login es rechazado antes de procesar las credenciales.
3. El bloqueo se libera automáticamente cuando expira la ventana de tiempo (sin intervención manual).

#### Desbloqueo manual de usuarios

Si un usuario quedó bloqueado por intentos fallidos, un administrador con permiso `users.unlock` puede desbloquearlo desde el módulo **Usuarios**:

- En el listado de usuarios: el usuario bloqueado aparece con un badge **Bloqueado** junto a su nombre, y un botón de candado abierto en las acciones.
- En la edición del usuario: aparece un panel de alerta con la fecha/hora hasta la que está bloqueado y un botón "Desbloquear usuario".
- Al desbloquear: se limpia `locked_until` y se reinicia `failed_login_attempts` a 0. El historial de `tbl_login_attempts` **no** se borra.
- El evento queda registrado en auditoría (`user_unlocked`) y en `tbl_login_attempts` con status `user_unlocked`.

#### Estado de implementación del bloqueo en login

✅ **Implementado y activo.** La lógica de bloqueo está integrada en `AuthController::loginProcess()` y en el `LoginSecurityService`. Los valores se leen de `tbl_login_security_settings`. Si la tabla no existe aún (migración 012 no importada), el sistema usa los valores del `config/app.php` como respaldo.

#### Tablas nuevas creadas

| Tabla | Descripción |
|---|---|
| `tbl_login_security_settings` | Configuración de protección contra intentos fallidos |
| `tbl_login_attempts` | Registro de todos los intentos de login (éxito, fallo, bloqueo) |

#### Campo nuevo en tbl_users

| Campo | Descripción |
|---|---|
| `last_failed_login_at` | Fecha/hora del último intento fallido |

#### Migración SQL (Autenticación — Intentos fallidos)
```
database/012_add_login_security_settings.sql
```

Crea `tbl_login_security_settings`, `tbl_login_attempts`, agrega `last_failed_login_at` a `tbl_users`, y crea el permiso `users.unlock` asignado al Administrador.

---

### Pasos manuales para aplicar la actualización (v3.0 — Autenticación)

1. **Importar SQL en phpMyAdmin:**
   - `database/012_add_login_security_settings.sql`
2. **Cerrar sesión e iniciar nuevamente** para que el nuevo permiso `users.unlock` se cargue en la sesión.
3. **Verificar permisos:** en **Seguridad → Roles y Permisos** → Administrador, debe aparecer `users.unlock`.
4. **Verificar menú:** debe mostrar **Seguridad → Autenticación** (ya no "MFA").
5. **Configurar intentos fallidos:** entrar a **Seguridad → Autenticación → pestaña Intentos fallidos** y revisar/ajustar los valores por defecto.
6. **Probar bloqueo con usuario secundario:**
   a. Iniciar sesión con una cuenta de prueba e ingresar contraseña incorrecta varias veces hasta superar el límite.
   b. Verificar que el login muestra el mensaje genérico.
   c. Desde otra sesión de Administrador, ir a **Usuarios** y desbloquear al usuario.
7. **Limpiar caché del navegador** si los estilos o el menú no se actualizan.

### Datos que debes configurar manualmente

| Qué | Dónde |
|---|---|
| Importar `009_add_mfa_settings.sql` | phpMyAdmin (si no se hizo antes) |
| Importar `012_add_login_security_settings.sql` | phpMyAdmin |
| SMTP activo y verificado (para email MFA) | Seguridad → SMTP |
| APP_KEY en `.env` (para cifrado de secretos TOTP) | `.env` |

---

## Verificación en 2 pasos por usuario (2FA)

Cada usuario puede configurar su propio segundo factor desde **Mi Perfil → Verificación en 2 pasos**. El administrador decide qué métodos están disponibles globalmente desde **Seguridad → MFA**.

### Métodos soportados

| Método | Descripción | Requisito global |
|---|---|---|
| Correo electrónico | Código de 6 dígitos enviado al email | SMTP activo y verificado |
| Aplicación autenticadora | TOTP con Google Authenticator / Authy | Ninguno |

> **SMS no está soportado.** El método SMS fue removido de esta plantilla base. Ver nota en la sección MFA superior.

### Flujo de login con 2FA

1. Usuario ingresa email + contraseña → credenciales válidas.
2. Si tiene 2FA activo y el método sigue habilitado globalmente → código OTP enviado (email/SMS) o se pide TOTP.
3. Usuario ingresa el código en `/two-factor/challenge`.
4. Código correcto → sesión creada, redirige al dashboard.

### Comportamiento de seguridad

- El `user_id` **no** se escribe en sesión hasta que el código 2FA es correcto. La sesión intermedia solo contiene `pending_2fa_user_id`.
- Los códigos OTP se almacenan en `tbl_two_factor_codes` como **SHA-256** — nunca en texto plano.
- El secreto TOTP del autenticador se almacena **cifrado con AES-256-CBC** via `Crypt::encrypt()`.
- Brute-force: máximo de intentos configurable (`TWO_FACTOR_MAX_ATTEMPTS`), código invalidado al superar el límite.
- Rate-limit de reenvío: mínimo configurable entre reenvíos (`TWO_FACTOR_RESEND_SECONDS`).
- Si el método global se deshabilita por el admin, el usuario pasa sin 2FA (comportamiento silencioso seguro).
- Si un usuario tiene `two_factor_method = 'sms'` en BD, el login muestra un error controlado y la sesión pendiente queda limpia.

### Migración SQL

```
database/010_add_user_two_factor_authentication.sql
```
Agrega columnas a `tbl_users` (`two_factor_enabled`, `two_factor_method`, `two_factor_secret_enc`, `two_factor_phone`) y crea `tbl_two_factor_codes`.

### Pasos manuales para aplicar la actualización

1. **Importar SQL:** en phpMyAdmin abre `database/010_add_user_two_factor_authentication.sql` e impórtalo.
2. **Instalar dependencias Composer:** `composer install` (ya incluidas en `composer.json`: `pragmarx/google2fa` v9 y `bacon/bacon-qr-code` v3).
3. **Agregar variables al `.env`:**
   ```
   TWO_FACTOR_CODE_EXPIRATION_MINUTES=10
   TWO_FACTOR_MAX_ATTEMPTS=5
   TWO_FACTOR_RESEND_SECONDS=60
   ```
4. **Habilitar métodos globalmente:** Seguridad → MFA → activar los métodos deseados.
5. **Probar desde Mi Perfil:** Mi Perfil → Verificación en 2 pasos → elegir método.

### Rutas 2FA

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/profile/two-factor` | Estado y opciones de 2FA |
| POST | `/profile/two-factor/enable-email` | Inicia activación por email |
| GET | `/profile/two-factor/confirm` | Formulario de confirmación de código |
| POST | `/profile/two-factor/confirm` | Verifica código y activa 2FA |
| POST | `/profile/two-factor/resend` | Reenvía código OTP |
| GET | `/profile/two-factor/setup-authenticator` | Muestra QR para TOTP |
| POST | `/profile/two-factor/confirm-authenticator` | Confirma TOTP y activa |
| POST | `/profile/two-factor/disable` | Deshabilita 2FA |
| GET | `/two-factor/challenge` | Challenge de login |
| POST | `/two-factor/challenge` | Verifica código en login |
| POST | `/two-factor/resend` | Reenvía código durante login |

---

## Inicio de sesión externo (OAuth 2.0)

El módulo **Seguridad > Autenticación** permite configurar proveedores OAuth 2.0 que aparecen como botones en la pantalla de login.

### Proveedores soportados

| Proveedor | Slug | Requiere |
|---|---|---|
| Google | `google` | Client ID, Client Secret, Redirect URI |
| Microsoft 365 | `microsoft` | Client ID, Client Secret, Tenant ID, Redirect URI |
| GitHub | `github` | Client ID, Client Secret, Redirect URI |

> **Google usa OAuth 2.0 tradicional, no Firebase Authentication.** Se configura con Client ID y Client Secret desde [Google Cloud Console](https://console.cloud.google.com/apis/credentials). No se usan ni se necesitan los campos de Firebase SDK (`apiKey`, `authDomain`, `projectId`, `storageBucket`, `messagingSenderId`, `appId`, `measurementId`). Si en el futuro se requiere Firebase Authentication, debe implementarse como una integración distinta basada en verificación de ID Token en backend.

### Cómo activar proveedores externos

1. Ir a **Seguridad → Autenticación → sección Proveedores externos**.
2. Hacer clic en **Configurar** en el proveedor deseado.
3. Completar: Client ID, Client Secret, Redirect URI (copiada desde el campo superior).
4. Guardar el proveedor.
5. Hacer clic en **Probar** (o **Iniciar prueba OAuth** desde la página de edición).
6. Serás redirigido al proveedor para autenticarte.
7. Tras autenticarte exitosamente, el sistema marca `is_verified = 1` y `last_test_status = success`.
8. Vuelve a **Seguridad → Autenticación → Métodos de inicio de sesión** y activa **Proveedores externos (OAuth)**.
9. Solo se puede activar si existe al menos un proveedor `is_enabled = 1` Y `is_verified = 1`.

### Redirect URI requerida

```
{APP_URL}/auth/external/{slug}/callback
```

Ejemplos:
- `https://tudominio.com/auth/external/google/callback`
- `https://tudominio.com/auth/external/microsoft/callback`
- `https://tudominio.com/auth/external/github/callback`

Registra exactamente esta URL como "Redirect URI autorizada" en la consola del proveedor.

### Qué aparece en la pantalla de login

Solo aparecen botones de proveedores que cumplan simultáneamente:
- `is_enabled = 1`
- `is_verified = 1`
- `client_id`, `client_secret` y `redirect_uri` configurados

| Condición | Resultado |
|---|---|
| Solo login local activo | Formulario email/contraseña |
| Login local + externo activos | Formulario + separador "o" + botones de proveedores |
| Solo externo activo | Solo botones de proveedores |
| Externo activo pero sin proveedores verificados | Mensaje de error, sin botones |

### Verificación de proveedores

`is_verified = 1` **solo** se establece cuando:
1. **Prueba OAuth real**: el admin usa el botón "Probar" y completa el flujo OAuth exitosamente.
2. **Login externo real**: un usuario se autentica exitosamente con el proveedor.
3. **Vinculación de cuenta**: un usuario vincula su cuenta con el proveedor exitosamente.

El botón "Probar" inicia un flujo OAuth real — no es una simple prueba de conectividad. Esto garantiza que `is_verified = 1` signifique que las credenciales funcionan correctamente.

### Precaución antes de desactivar login local

No desactives el login local si no has:
1. Probado el login externo en la misma sesión.
2. Vinculado tu cuenta de administrador al proveedor externo desde **Mi Cuenta**.

Si deshabilitas el login local y el proveedor externo falla, perderás acceso al sistema.

### Rutas OAuth

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/auth/external/{slug}/redirect` | Inicia flujo OAuth (genera state, redirige al proveedor) |
| GET | `/auth/external/{slug}/callback` | Callback del proveedor (valida state, obtiene token, completa login) |
| POST | `/security/authentication/providers/test/{id}` | Inicia prueba OAuth real para el admin |

### Variables .env relevantes

```env
APP_URL=https://tudominio.com   # Se usa para construir la Redirect URI
APP_KEY=...                     # Clave AES-256 para cifrar client_secret
```

### Migración SQL (Autenticación externa)

```
database/014_add_authentication_methods.sql
```

Crea: `tbl_authentication_settings`, `tbl_external_auth_providers`, `tbl_user_external_accounts`. Inserta Google, Microsoft 365 y GitHub con configuración inicial.

---

## Restricción por dominio institucional

Permite limitar el acceso al sistema únicamente a cuentas cuyo correo pertenezca a uno o varios dominios institucionales autorizados.

### Qué hace

- Si la restricción está **activada**, solo los correos del tipo `usuario@contoso.org` (o cualquier dominio autorizado) pueden acceder al sistema.
- Los correos de otros dominios (Gmail, Hotmail, etc.) son rechazados con un mensaje genérico.
- La restricción aplica a **login local** (correo + contraseña) **y a proveedores externos** (Google, Microsoft, GitHub).
- La **recuperación de contraseña** también respeta la restricción: no se envía correo a dominios no autorizados.
- La vinculación de cuentas desde "Mi Cuenta" también respeta la restricción.
- Las pruebas OAuth del administrador (botón "Probar") **no** están sujetas a la restricción de dominio.
- La coincidencia es **exacta**: `contoso.org` no acepta automáticamente `sub.contoso.org` ni `fakecontoso.org`.

### Cómo configurar

1. Ir a **Seguridad → Autenticación → Opciones avanzadas**.
2. Activar el switch **Restringir por dominio institucional**.
3. Ingresar los dominios autorizados en el textarea (uno por línea o separados por coma):

```
contoso.org
contoso.com
otraempresa.org
```

4. Hacer clic en **Guardar configuración**.

### Formato de dominios aceptado

| Formato | ¿Aceptado? |
|---|---|
| `contoso.org` | ✓ |
| `sub.contoso.org` | ✓ (si se agrega explícitamente) |
| `@contoso.org` | ✗ |
| `https://contoso.org` | ✗ |
| `contoso.org, otraempresa.org` | ✓ (se normaliza) |

La coincidencia es **exacta**: `contoso.org` no acepta `sub.contoso.org`, `fakecontoso.org` ni `contoso.org.fake.com`.

### Migración SQL

```
database/016_add_external_domain_restrictions.sql
```

Agrega las columnas `restrict_external_domains` y `allowed_external_domains` a `tbl_authentication_settings`. **Ejecutar solo si las columnas no existen** — verificar antes en phpMyAdmin > Estructura de tabla.

---

## Política de Contraseñas

Módulo administrativo en `/security/password-policy` (Seguridad > Política de Contraseñas) que permite definir los requisitos que deben cumplir las contraseñas de todos los usuarios del sistema.

### Qué controla

| Configuración | Descripción |
|---|---|
| Activar/desactivar política | Si está inactiva, solo se exige un mínimo de 6 caracteres |
| Longitud mínima | Entre 6 y 128 caracteres (mínimo recomendado: 8 cuando está activa) |
| Mayúsculas / Minúsculas | Requerir al menos una letra de cada tipo |
| Números | Requerir al menos un dígito |
| Caracteres especiales | Requerir al menos un símbolo (`@`, `#`, `$`, etc.) |
| Sin parte del correo | Rechaza contraseñas que contengan la parte local del correo (≥3 chars) |
| Sin nombre o apellido | Rechaza contraseñas que contengan el nombre o apellido del usuario (≥3 chars) |
| Sin contraseñas comunes | Bloquea passwords conocidos como `password123`, `admin`, `123456` |
| Historial | Número de contraseñas anteriores que no se pueden reutilizar (0–10) |
| Expiración | Días antes de que la contraseña expire (0 = sin expiración) |

### Dónde aplica

La política se valida en todos los flujos de cambio de contraseña:

- **Crear usuario** (admin)
- **Editar usuario** (admin, si se ingresa nueva contraseña)
- **Mi Cuenta → Cambiar contraseña** (usuario autenticado)
- **Restablecer contraseña por correo** (flujo público)
- **Cambio obligatorio** (`/account/password/required-change`) — ver sección siguiente

Los formularios muestran un widget visual de requisitos que se actualiza en tiempo real mientras el usuario escribe.

### Historial de contraseñas

Los hashes de contraseñas anteriores se almacenan en `tbl_password_histories`. Al cambiar la contraseña, el sistema verifica contra los últimos N hashes usando `password_verify()`. Las entradas antiguas se eliminan automáticamente para mantener solo las N más recientes.

### Cambio obligatorio de contraseña

El sistema puede forzar a un usuario a cambiar su contraseña en dos escenarios:

**1. Expiración automática**

Si `password_expiration_days > 0` y han pasado más días desde `password_changed_at`, el usuario es redirigido a `/account/password/required-change` en su próxima solicitud autenticada. La verificación se realiza con caché de 60 segundos en sesión para evitar una consulta a BD por cada petición.

Si `password_changed_at` es `NULL` (cuenta sin contraseña establecida), el sistema trata la contraseña como expirada.

**2. Forzar por administrador (`force_password_change = 1`)**

En el módulo Usuarios, al crear o editar un usuario, un administrador puede activar la casilla **"Forzar cambio de contraseña en próximo inicio"**. La columna `force_password_change` se establece en `1` y el usuario verá la pantalla obligatoria al iniciar sesión.

**Flujo centralizado**

La verificación ocurre en `Auth::checkPasswordChangeRequired()`, llamado desde `Auth::requireAuth()` — el punto de entrada de todos los controladores protegidos. No requiere lógica adicional en cada controlador.

Rutas involucradas:
- `GET  /account/password/required-change` — pantalla de actualización
- `POST /account/password/required-change` — procesar cambio

Al completar el cambio, se actualiza `password_changed_at`, se pone `force_password_change = 0`, se guarda en el historial y se limpian los flags de sesión.

### Migraciones SQL

```
database/017_add_password_policy.sql
database/018_add_force_password_change.sql
```

`017` crea las tablas `tbl_password_policies` y `tbl_password_histories` y registra el módulo y permisos.
`018` agrega la columna `force_password_change TINYINT(1) DEFAULT 0` a `tbl_users`.

---

## Apariencia del sistema

Módulo administrativo en `/appearance` (Administración > Apariencia) que permite configurar visualmente el sistema sin tocar código.

**Qué se puede configurar:**

| Elemento | Ruta de archivos | Extensiones |
|---|---|---|
| Logo | `public/uploads/appearance/logo/` | jpg, png, webp |
| Favicon | `public/uploads/appearance/favicon/` | ico, png |
| Fondo de login | `public/uploads/appearance/login/` | jpg, png, webp |
| Color principal | — (BD) | Hex `#rrggbb` |
| Color del sidebar | — (BD) | Hex `#rrggbb` |
| Nombre visual y lema | — (BD) | texto |

- Usa **UploadService** para validar y guardar todos los archivos.
- Los colores se inyectan como variables CSS en el layout; no sobrescriben DashboardKit completamente.
- Los ajustes globales de modo oscuro viven en `public/assets/css/dark-mode.css` y se cargan después de DashboardKit, Select2 y DataTables para corregir contraste sin afectar el modo claro.
- El avatar del topbar se obtiene con `current_user_avatar_url()`, usando la imagen de perfil guardada en sesión y el fallback `public/assets/images/user/avatar-1.jpg` si no hay archivo válido.
- Para restablecer cualquier elemento: botón "Restablecer" en la vista (requiere permiso `appearance.reset`).
- **Migración a importar:** `database/015_add_appearance_settings.sql`
- Tabla de BD: `tbl_appearance_settings` (un solo registro).
- Permisos: `appearance.view`, `appearance.edit`, `appearance.reset` — asignados al Administrador.

---

## UploadService

Servicio centralizado para la subida de archivos (`app/Services/UploadService.php`). Toda la lógica de validación, naming y almacenamiento de archivos pasa por este servicio; los controladores solo llaman `upload()` o `delete()`.

### Perfiles disponibles (config/uploads.php)

| Perfil | Ruta | Tamaño máx. | Extensiones |
|---|---|---|---|
| `profile_images` | `public/uploads/profiles/` | 2 MB | jpg, jpeg, png, webp |
| `user_manuals` | `public/uploads/manuals/` | 10 MB | pdf, doc, docx |

### Módulos que lo usan

- **Imagen de perfil** — `ProfileController` y `UsersController`
- **Manuales** — `SystemInformationController`

### Extensiones siempre bloqueadas

`php`, `phtml`, `phar`, `php3`–`php8`, `exe`, `bat`, `cmd`, `sh`, `pl`, `cgi`, `js`, `html`, `htm`, `svg`

La carpeta `public/uploads/` incluye un `.htaccess` que bloquea la ejecución de scripts y deshabilita el listado de directorio.

### Uso en módulos futuros

```php
use App\Services\UploadService;

$svc    = new UploadService();
$result = $svc->upload($_FILES['campo'], 'profile_images', ['prefix' => 'avatar_1_']);

if (!$result['success']) {
    // $result['error'] contiene el mensaje amigable para el usuario
}

// $result['filename']      — basename del archivo guardado
// $result['original_name'] — nombre original del cliente
// $result['mime']          — MIME real detectado
// $result['size']          — tamaño en bytes

// Para eliminar un archivo anterior:
$svc->delete('avatar_1_abc123.jpg', 'profile_images');
```

Para agregar un nuevo perfil, define una entrada en `config/uploads.php` siguiendo el mismo esquema.

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
