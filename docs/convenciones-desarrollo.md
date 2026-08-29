# Convenciones De Desarrollo

Esta guia resume las reglas de estilo y arquitectura para extender Skeleton como plantilla base.

## Estructura MVC

- `app/Controllers`: flujo HTTP, validacion de permisos, CSRF, redirecciones y seleccion de vistas.
- `app/Models`: consultas PDO y lectura/escritura de tablas.
- `app/Services`: logica reutilizable que no pertenece a un controlador ni a un modelo.
- `app/Views`: HTML/PHP de presentacion.
- `core`: infraestructura compartida.
- `config`: configuracion versionable.
- `public`: front controller y assets publicos.

## Nombres

- Controladores: plural cuando administran recursos, por ejemplo `ProductsController`.
- Modelos: singular, por ejemplo `Product`.
- Vistas: carpeta por modulo, por ejemplo `app/Views/products/index.php`.
- Rutas: minusculas, con guiones cuando sea necesario, por ejemplo `/system-health`.
- Permisos: `modulo.accion`, por ejemplo `products.view`.
- Eventos de auditoria: `modulo.accion`, por ejemplo `products.created`.

## Controladores

- Validar permisos al inicio de cada accion administrativa.
- Usar `Auth::requireAuth()` para acciones de cuenta propia.
- Usar `CSRF::validateOrFail()` en acciones `POST`.
- Usar `Redirect` para redirecciones internas.
- No ejecutar SQL directamente si ya existe un modelo adecuado.
- Estos checks deben seguir estando en el controlador aunque la ruta
  ya tenga guard opcional en el Router (ver seccion siguiente) — el
  guard del Router es una segunda capa de defensa, no un reemplazo.

## Router — Guard Opcional (Etapa 4)

`Router::get()`/`Router::post()` aceptan un tercer parametro opcional
`array $options = []` con metadata de acceso (`guest`, `auth`,
`permission`). Es opcional y retrocompatible: una ruta sin tercer
parametro funciona exactamente igual que antes de la Etapa 4. Ver
`docs/crear-nuevo-modulo.md` (seccion "5.1 Guard opcional del
Router") para la sintaxis completa y ejemplos. Regla clave: el guard
NO reemplaza los checks dentro del controlador — ambos deben coexistir
hasta que una etapa futura apruebe explicitamente retirar el check
duplicado del controlador.

## CSRF

- Token almacenado en sesión (`Core\CSRF`), generado con `random_bytes(32)`.
- En formularios: imprimir `Core\CSRF::field()` (campo oculto `_csrf_token`).
- En peticiones AJAX: leer el meta `<meta name="csrf-token">` (ya presente en
  `app/Views/layouts/header.php`) y enviarlo como parte del body/POST.
- Validar con `CSRF::validateOrFail()` en toda acción `POST` sensible; esto
  responde 403 y detiene la ejecución si el token no coincide, y **regenera
  el token tras una validación exitosa** (no es un token estable por sesión).
  Esto es intencional: evita reutilización del mismo token entre distintas
  acciones sensibles consecutivas. Efecto práctico para quien desarrolle
  nuevas vistas: si un formulario queda abierto en una pestaña después de
  que otra acción ya usó y regeneró el token en esa misma sesión, ese
  formulario antiguo devolverá 403 al enviarse y deberá recargarse.
- `CSRF::verify()` (sin `Fail`) solo verifica sin regenerar el token; se usa
  en endpoints que gestionan su propia respuesta JSON/AJAX de forma manual
  (ej. `LockController::lockSession()`) para no invalidar el formulario
  visible en pantalla. Úsalo solo cuando el propio endpoint controle su
  respuesta; para el resto, usar `validateOrFail()`.

## Cifrado (Crypt)

- `Core\Crypt` implementa cifrado **reversible** (AES-256-CBC + HMAC-SHA256,
  clave derivada de `APP_KEY`), para secretos que el sistema necesita volver
  a leer en texto plano (ej. contraseña SMTP, secreto TOTP cifrado en BD).
- `Crypt` **no debe usarse para contraseñas de usuario** ni para ningún dato
  que deba ser irreversible. Las contraseñas siempre se manejan con
  `password_hash()` / `password_verify()` (patrón ya usado en `AuthController`,
  `UsersController`, `AccountController`, `LockController`, etc.).
- Nunca registrar en auditoría/logs el resultado de `Crypt::decrypt()` ni
  ningún valor cifrado reversible — ver la lista completa de datos que no
  deben registrarse en `docs/permisos-y-auditoria.md`.

## Autenticación — `remember_token` (Etapa 5)

`tbl_users.remember_token` puede existir por compatibilidad o reserva
futura, pero actualmente Skeleton **no implementa** login persistente
tipo "recordarme" — no hay checkbox visible, no hay cookie persistente,
y ningún controlador/modelo lee, escribe ni compara esta columna (solo
viaja incidentalmente dentro de los `SELECT u.*` de `App\Models\User`,
sin que nada la consuma). No usar esta columna sin una etapa de diseño
específica. Cualquier implementación futura de "recordarme" debe usar
tokens persistentes seguros: selector + validador, hash en base de
datos (nunca el token plano), expiración, rotación, revocación en
logout/cambio de contraseña/MFA, y cookies `HttpOnly`/`Secure`/`SameSite`.

## MFA — SMS eliminado (Etapa 6 → Etapa 6.1)

Skeleton **no implementa** MFA por SMS. El método `sms` existió en
versiones tempranas, fue retirado deliberadamente en la migración 011
(`011_remove_sms_from_mfa.sql`), y las columnas/enum/permiso
heredados que quedaban tras ese retiro fueron **eliminados de forma
controlada** en la migración 031
(`031_remove_legacy_sms_mfa.sql`, Etapa 6.1), con backup previo y
verificación de que no existían datos SMS reales. Ya no existen:
`tbl_mfa_settings.sms_enabled/sms_provider/sms_api_key/sms_api_secret_enc/sms_from/sms_endpoint/sms_extra_config`,
`tbl_users.two_factor_phone`, el valor `'sms'` en los enum
`tbl_users.two_factor_method` (ahora `enum('email','authenticator')`)
y `tbl_two_factor_codes.method` (ahora `enum('email')`), ni el permiso
`security_mfa.test`. El código que bloqueaba defensivamente el valor
`'sms'` (`AuthController`, `TwoFactorChallengeController`) se
generalizó a una validación de método 2FA soportado
(`in_array($method, ['email', 'authenticator'], true)`), sin mencionar
SMS, para no depender de un valor concreto retirado. SMS **no está
reservado** para el futuro: si algún día se necesita, requiere una
etapa nueva con diseño completo (proveedor, validación de número,
rate limit, auditoría, expiración, consentimiento/costos) y sus
propias columnas — ver
`resultados/etapa-6-revision-columnas-sms-heredadas-skeleton.txt` y
`resultados/etapa-6-1-eliminacion-sms-heredado-skeleton.txt` para el
análisis y la migración de eliminación completos.

## Modelos

- Extender `Core\Model`.
- Usar consultas preparadas.
- No renderizar HTML.
- No leer `$_POST`, `$_GET` o `$_FILES` dentro del modelo.

## Servicios

Usar servicios para responsabilidades transversales:

- Uploads.
- Correo.
- OAuth.
- Salud del sistema.
- Limpieza de temporales.
- Politica de contrasenas.
- Sesiones activas.
- Rate limit (`App\Services\RateLimitService`): servicio generico para limitar intentos por accion + identificador (IP, email, usuario, etc.), respaldado por `tbl_rate_limits`. Guarda unicamente `identifier_hash` (HMAC-SHA256 con `APP_KEY`), nunca el identificador crudo. El servicio solo cuenta intentos y decide si algo esta bloqueado (`hit()`, `tooManyAttempts()`, `availableIn()`, `clear()`); el mensaje mostrado al usuario y la auditoria los decide siempre el controlador que lo usa, no el servicio. Se limpia automaticamente desde mantenimiento via `TemporaryDataCleanupService` (categoria `rate_limits`), respetando bloqueos activos. Ya esta integrado en: MFA TOTP (`mfa.totp_challenge`), desbloqueo de sesion (`session.unlock`), recuperacion de contrasena por IP (`password_reset.request_ip`, adicional al limite existente por correo) y pruebas administrativas de SMTP/OAuth (`admin.smtp_test`, `admin.oauth_test`). Todavia NO reemplaza el login local ni `LoginSecurityService` (que sigue usando su propio mecanismo por intentos/IP en `tbl_login_attempts`). No usar `RateLimitService` para guardar secretos, tokens, contrasenas ni codigos MFA — solo cuenta intentos por accion+identificador.

## UploadService

- Configurar categorias en `config/uploads.php`.
- Validar extension, MIME y tamano.
- Guardar solo el nombre/ruta relativa necesaria.
- No confiar en nombres originales de usuario para rutas.
- No versionar archivos reales subidos.

## Traducciones

- Usar `__()` para textos visibles.
- Mantener claves equivalentes en `lang/es.php` y `lang/en.php`.
- Evitar textos hardcodeados en nuevas vistas cuando formen parte de UI final.

## SweetAlert2

- Usar SweetAlert2 para confirmaciones de acciones destructivas o sensibles.
- Mantener confirmaciones en JS asociado a formularios existentes.
- No confiar en la confirmacion visual como control de seguridad; la validacion real va en backend.

## DataTables

- Usar DataTables en listados tabulares cuando aporte busqueda, paginacion u ordenamiento.
- No cargar DataTables en pantallas que no lo necesitan.
- Mantener textos traducibles si se personaliza la interfaz.

## Chart.js

- Usar la copia local de Chart.js cuando el modulo requiera graficas.
- Cargar el script desde `BASE_URL`.
- Evitar dependencias CDN nuevas si ya existe un asset local equivalente.

## JavaScript Y URLs

- Construir URLs con `BASE_URL` desde PHP.
- No hardcodear `localhost`, nombres de carpeta ni dominios.
- Para `fetch`, usar rutas absolutas basadas en `BASE_URL`.
- Evitar duplicar reglas de autorizacion en JS; el backend siempre decide.

## Modo Oscuro

- Usar clases compatibles con DashboardKit/Bootstrap.
- Verificar contraste de textos, tablas, formularios y alertas.
- Evitar colores fijos que se pierdan en modo oscuro.

## Layout

Despues de `.page-header` debe venir una `.row` antes de colocar `.card`.

No usar:

```html
<div class="page-header">...</div>
<div class="card">...</div>
```

Usar:

```html
<div class="page-header">...</div>
<div class="row">
  <div class="col-12">
    <div class="card">...</div>
  </div>
</div>
```

## SQL

- No usar `IF NOT EXISTS`.
- No usar `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`.
- Crear migraciones compatibles con phpMyAdmin.
- Usar `snake_case`.
- Usar prefijo `tbl_` en tablas.
- PK como `id`.
- FK como `user_id`, `role_id`, `status_id`.
- Hashes con sufijo `_hash`.
- Secretos cifrados con sufijo `_enc`.
- `tbl_users.status_id` es la fuente oficial del estado del usuario.
- No volver a usar `tbl_users.status`.

## Commits

- Hacer commits con mensajes claros y en infinitivo o descripcion breve.
- No mezclar cambios funcionales con limpieza de documentacion.
- No incluir `.env`, `vendor/`, `node_modules/`, logs, backups ni uploads reales.
- Revisar `git status` antes de commitear.
