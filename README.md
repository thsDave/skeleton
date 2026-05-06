# Skeleton MVC — PHP 8.3 + MySQL + DashboardKit

Sistema web base (skeleton) con arquitectura MVC en PHP puro, sin frameworks externos. Usa la plantilla visual **DashboardKit Free Admin Template** (Bootstrap 5) y está diseñado como punto de partida limpio y seguro para futuros proyectos.

---

## Tecnologías

| Tecnología | Descripción |
|---|---|
| PHP 8.3 | Lenguaje principal, sin frameworks |
| MySQL 8 | Base de datos relacional |
| PDO | Acceso a datos con consultas preparadas |
| Bootstrap 5 | UI base (vía DashboardKit) |
| DashboardKit Free | Plantilla visual admin |

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
│   │   ├── AuthController.php       ← Login, logout
│   │   ├── DashboardController.php  ← Dashboard principal
│   │   ├── ProfileController.php    ← Mi Perfil
│   │   └── AccountController.php   ← Mi Cuenta (email + password)
│   ├── Models/
│   │   ├── User.php                 ← CRUD usuarios
│   │   └── LoginLog.php             ← Registro de intentos de login
│   └── Views/
│       ├── auth/
│       │   └── login.php
│       ├── dashboard/
│       │   └── index.php
│       ├── profile/
│       │   ├── index.php
│       │   └── edit.php
│       ├── account/
│       │   ├── index.php
│       │   ├── edit_email.php
│       │   └── edit_password.php
│       ├── layouts/
│       │   ├── header.php           ← <head> + inicio de <body>
│       │   ├── sidebar.php          ← Menú lateral DashboardKit
│       │   ├── topbar.php           ← Barra superior
│       │   ├── main.php             ← Incluye los 3 anteriores + abre pc-container
│       │   ├── footer.php           ← Cierra layout + scripts JS
│       │   └── alerts.php           ← Mensajes flash (success/error)
│       └── errors/
│           └── 404.php
├── config/
│   ├── app.php                      ← URL base, timezone, timeouts
│   └── database.php                 ← Host, puerto, BD, usuario, password
├── core/
│   ├── Router.php                   ← Enrutador front-controller
│   ├── Controller.php               ← Clase base de controllers
│   ├── Model.php                    ← Clase base de models
│   ├── Database.php                 ← Singleton PDO
│   ├── Auth.php                     ← Autenticación y control de acceso
│   ├── Session.php                  ← Manejo de sesiones + timeout
│   ├── CSRF.php                     ← Token CSRF para formularios POST
│   ├── Validator.php                ← Validaciones de entrada
│   ├── Redirect.php                 ← Redirecciones + mensajes flash
│   └── Logger.php                   ← Logs de seguridad en /logs/
├── database/
│   └── db_skeleton.sql              ← Script SQL completo para importar
├── logs/
│   └── security.log                 ← Log de eventos de seguridad
├── public/
│   ├── index.php                    ← Front controller (único punto de entrada)
│   ├── .htaccess                    ← Rewrite rules + headers de seguridad
│   └── assets/                      ← CSS, JS, fonts e imágenes de DashboardKit
└── README.md
```

---

## Requisitos del entorno

- **Laragon** (Windows) con:
  - PHP 8.3
  - MySQL 8.0
  - Apache con `mod_rewrite` habilitado
- Extensiones PHP requeridas: `pdo`, `pdo_mysql`, `mbstring`, `json`
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

> **Alternativa rápida:** En el menú de Laragon, haz clic derecho → **Database** → **phpMyAdmin** para abrirlo directamente.

### Paso 3 — Configurar la conexión a la base de datos

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

### Paso 4 — Configurar la URL base

Edita `config/app.php` y ajusta la URL según tu entorno:

```php
// Si accedes por subdominio (recomendado en Laragon):
'url' => 'http://template.test/public',

// O si accedes por subdirectorio:
'url' => 'http://localhost/template/public',
```

> **Recomendación con Laragon:** Crea un virtual host haciendo clic derecho en Laragon → **www** → **template** → **Create website**. Laragon creará automáticamente el dominio `template.test`.

### Paso 5 — Verificar permisos del directorio logs

El directorio `/logs` debe ser escribible por PHP. En Laragon/Windows esto funciona por defecto.

### Paso 6 — Acceder al sistema

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

La contraseña está almacenada con `password_hash()` bcrypt (cost=12) en la base de datos.

---

## Rutas del sistema

| Método | Ruta | Descripción | Acceso |
|---|---|---|---|
| GET | `/login` | Formulario de login | Público |
| POST | `/login` | Procesar credenciales | Público |
| POST | `/logout` | Cerrar sesión | Autenticado |
| GET | `/dashboard` | Dashboard principal | Autenticado |
| GET | `/profile` | Ver perfil personal | Autenticado |
| GET | `/profile/edit` | Formulario editar perfil | Autenticado |
| POST | `/profile/update` | Guardar cambios de perfil | Autenticado |
| GET | `/account` | Ver cuenta | Autenticado |
| GET | `/account/edit-email` | Formulario cambiar correo | Autenticado |
| POST | `/account/update-email` | Guardar nuevo correo | Autenticado |
| GET | `/account/edit-password` | Formulario cambiar contraseña | Autenticado |
| POST | `/account/update-password` | Guardar nueva contraseña | Autenticado |

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
