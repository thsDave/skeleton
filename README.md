# Skeleton MVC v3.0 — PHP 8.3 + MySQL + DashboardKit

Sistema web base (skeleton) con arquitectura MVC en PHP puro, sin frameworks externos. Usa la plantilla visual **DashboardKit Free Admin Template** (Bootstrap 5) y está diseñado como punto de partida limpio y seguro para futuros proyectos.

**v3.0** agrega: modo oscuro por usuario, internacionalización (ES/EN), gestión de idiomas, información del sistema y manuales de usuario descargables.

---

## Tecnologías

| Tecnología | Descripción |
|---|---|
| PHP 8.3 | Lenguaje principal, sin frameworks |
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
│   │   └── SystemInformationController.php ← Info del sistema + manuales
│   ├── Models/
│   │   ├── User.php                        ← CRUD usuarios con JOINs
│   │   ├── Language.php                    ← Idiomas del sistema
│   │   ├── SystemSetting.php               ← Configuración del sistema
│   │   ├── UserManual.php                  ← Manuales de usuario
│   │   ├── Role.php                        ← Roles del sistema
│   │   ├── Status.php                      ← Estados del sistema
│   │   └── LoginLog.php                    ← Registro de intentos de login
│   └── Views/
│       ├── auth/login.php
│       ├── dashboard/index.php
│       ├── profile/
│       │   ├── index.php            ← Datos personales + preferencias (tema/idioma)
│       │   └── edit.php             ← Editar info + foto de perfil
│       ├── account/
│       │   ├── index.php
│       │   ├── edit_email.php
│       │   └── edit_password.php
│       ├── users/
│       │   ├── index.php            ← DataTables + inactivar con SweetAlert2
│       │   ├── create.php
│       │   └── edit.php
│       ├── languages/
│       │   ├── index.php            ← Listado de idiomas (solo admin)
│       │   ├── create.php
│       │   └── edit.php
│       ├── system_information/
│       │   ├── index.php            ← Info del sistema + listado de manuales
│       │   └── edit.php             ← Editar info (solo admin)
│       ├── manuals/
│       │   └── create.php           ← Subir manual (solo admin)
│       ├── layouts/
│       │   ├── header.php           ← <head> + tema dinámico (light/dark)
│       │   ├── sidebar.php          ← Menú con i18n + secciones por rol
│       │   ├── topbar.php           ← Avatar + dropdown con i18n
│       │   ├── main.php             ← Incluye los 3 anteriores
│       │   ├── footer.php           ← JS + SweetAlert2 + tema dinámico
│       │   └── alerts.php
│       └── errors/404.php
├── config/
│   ├── app.php                      ← URL, timezone, uploads (perfiles + manuales)
│   └── database.php
├── core/
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   ├── Database.php
│   ├── Auth.php                     ← + theme(), lang(), updateSession()
│   ├── Lang.php                     ← i18n: __($key), carga /lang/*.php
│   ├── Session.php
│   ├── CSRF.php
│   ├── Validator.php
│   ├── Redirect.php
│   └── Logger.php
├── lang/
│   ├── es.php                       ← Español (idioma base)
│   └── en.php                       ← English
├── database/
│   ├── db_skeleton.sql                                        ← Esquema base (001)
│   ├── 002_update_users_roles_statuses_profile_image.sql      ← Migración (002)
│   └── 003_add_preferences_languages_system_info_manuals.sql  ← Migración v3.0
├── logs/
│   └── security.log
├── public/
│   ├── index.php                    ← Front controller + todas las rutas
│   ├── .htaccess
│   ├── uploads/
│   │   ├── profiles/                ← Fotos de perfil
│   │   └── manuals/                 ← Archivos de manuales (PDF/DOC/DOCX)
│   └── assets/
└── README.md
```

---

## Requisitos del entorno

- **Laragon** (Windows) con:
  - PHP 8.3
  - MySQL 8.0
  - Apache con `mod_rewrite` habilitado
- Extensiones PHP requeridas: `pdo`, `pdo_mysql`, `mbstring`, `json`, `fileinfo`
- No requiere Composer ni dependencias externas

---

## Instalación paso a paso

### Paso 1 — Copiar el proyecto

Coloca la carpeta `template` dentro de `C:\laragon\www\`:

```
C:\laragon\www\template\
```

### Paso 2 — Importar la base de datos desde phpMyAdmin

1. Abre **Laragon** y haz clic en **Start All** para iniciar MySQL y Apache.
2. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
3. Inicia sesión con usuario `root` y contraseña vacía (configuración por defecto de Laragon).
4. En el panel izquierdo, haz clic en **Nueva** (o **New**) para crear una base de datos.
   - Nombre: `db_skeleton`
   - Cotejamiento: `utf8mb4_unicode_ci`
   - Haz clic en **Crear**.
5. Con la base de datos `db_skeleton` seleccionada, haz clic en la pestaña **Importar**.
6. Haz clic en **Seleccionar archivo** y navega hasta:
   ```
   C:\laragon\www\template\database\db_skeleton.sql
   ```
7. Haz clic en **Importar** (o **Go**).
8. Verifica que se hayan creado las tablas `tbl_users` y `tbl_login_logs`.

### Paso 3 — Aplicar las migraciones

Repite el proceso de importación con cada archivo SQL en orden:

**Migración 002:**
1. Con `db_skeleton` seleccionada, ve a **Importar**.
2. Selecciona:
   ```
   C:\laragon\www\template\database\002_update_users_roles_statuses_profile_image.sql
   ```
3. Haz clic en **Importar**.
4. Verifica que existan `tbl_roles`, `tbl_statuses` y que `tbl_users` tenga `role_id`, `status_id` y `profile_image`.

**Migración 003 (v3.0 — nueva):**
1. Con `db_skeleton` seleccionada, ve a **Importar**.
2. Selecciona:
   ```
   C:\laragon\www\template\database\003_add_preferences_languages_system_info_manuals.sql
   ```
3. Haz clic en **Importar**.
4. Verifica que existan `tbl_languages`, `tbl_system_settings`, `tbl_user_manuals` y que `tbl_users` tenga `theme_preference` y `language_id`.

> **Alternativa rápida:** En el menú de Laragon, haz clic derecho → **Database** → **phpMyAdmin** para abrirlo directamente.

### Paso 4 — Configurar la conexión a la base de datos

Edita `config/database.php` si tu configuración de MySQL en Laragon es diferente:

```php
return [
    'host'   => '127.0.0.1',
    'port'   => '3306',
    'dbname' => 'db_skeleton',
    'user'   => 'root',
    'pass'   => '',          // Vacío por defecto en Laragon
];
```

### Paso 5 — Configurar la URL base

Edita `config/app.php` y ajusta la URL según tu entorno:

```php
// Si accedes por subdominio (recomendado en Laragon):
'url' => 'http://template.test/public',

// O si accedes por subdirectorio:
'url' => 'http://localhost/template/public',
```

> **Recomendación con Laragon:** Crea un virtual host haciendo clic derecho en Laragon → **www** → **template** → **Create website**. Laragon creará automáticamente el dominio `template.test`.

### Paso 6 — Verificar permisos de los directorios de uploads

Los directorios `public/uploads/profiles/` y `public/uploads/manuals/` deben ser escribibles por PHP. En Laragon/Windows esto funciona por defecto.

### Paso 7 — Acceder al sistema

Abre tu navegador y ve a:

```
http://localhost/template/public/login
```

O si usas virtual host:

```
http://template.test/public/login
```

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

## Notas para producción

- Cambia `'env' => 'production'` en `config/app.php` para desactivar errores.
- Activa HTTPS y el flag `secure` en cookies de sesión (`Session.php`).
- Cambia las credenciales de base de datos en `config/database.php`.
- Asegúrate de que `/logs/security.log` no sea accesible públicamente (el `.htaccess` ya lo bloquea).
- El directorio `dashboardkit-src/` (fuente del template) puede eliminarse en producción.
- El directorio `public/uploads/` debe tener permisos de escritura para el proceso de PHP.
