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
