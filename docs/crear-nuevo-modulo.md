# Crear Nuevo Modulo

Esta guia describe el flujo recomendado para agregar un modulo administrativo nuevo en Skeleton sin romper la estructura MVC existente.

## 1. Base De Datos

Crear una migracion SQL compatible con phpMyAdmin o actualizar el schema consolidado si estas preparando una nueva version base.

Reglas minimas:

- Usar tablas con prefijo `tbl_`.
- Usar columnas en `snake_case`.
- Usar `id` como PK.
- Usar FK con nombres como `user_id`, `role_id`, `status_id`.
- Usar `created_at`, `updated_at` y `deleted_at` cuando aplique.
- Usar sufijo `_hash` para hashes.
- Usar sufijo `_enc` para secretos cifrados.
- No usar `IF NOT EXISTS`.
- No usar `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`.

## 2. Modelo

Crear el modelo en `app/Models`, por ejemplo:

```text
app/Models/Product.php
```

El modelo debe concentrar consultas PDO y no debe mezclar HTML ni flujo HTTP. Reutiliza `Core\Model` y consultas preparadas.

## 3. Controlador

Crear el controlador en `app/Controllers`, por ejemplo:

```text
app/Controllers/ProductsController.php
```

Cada accion administrativa debe validar permiso al inicio:

```php
Auth::requirePermission('products.view');
```

Las acciones `POST` deben validar CSRF:

```php
CSRF::validateOrFail();
```

## 4. Vistas

Crear las vistas en una carpeta propia:

```text
app/Views/products/index.php
app/Views/products/create.php
app/Views/products/edit.php
```

Usar el layout de DashboardKit ya existente. Respetar modo claro/oscuro y evitar estilos aislados que rompan el tema.

Regla obligatoria de layout:

```html
<div class="page-header">...</div>
<div class="row">
  <div class="col-12">
    <div class="card">...</div>
  </div>
</div>
```

Despues de `.page-header` debe venir una `.row` antes de colocar `.card`. No iniciar directamente con `.card` despues de `.page-header`.

## 5. Rutas

Registrar rutas en `public/index.php`:

```php
$router->get('/products', [ProductsController::class, 'index']);
$router->get('/products/create', [ProductsController::class, 'create']);
$router->post('/products/store', [ProductsController::class, 'store']);
$router->get('/products/edit/{id}', [ProductsController::class, 'edit']);
$router->post('/products/update/{id}', [ProductsController::class, 'update']);
$router->post('/products/delete/{id}', [ProductsController::class, 'delete']);
```

Usar `POST` para acciones que cambian datos.

## 6. Permisos

Crear un modulo en `tbl_modules` y permisos en `tbl_permissions`.

Ejemplo de slugs:

- `products.view`
- `products.create`
- `products.edit`
- `products.delete`
- `products.export`

Asignar los permisos al rol Administrador en `tbl_role_permissions`.

## 7. Menu Y Botones

Agregar opcion al menu solo si el usuario tiene permiso:

```php
<?php if (can('products.view')): ?>
  <a href="<?= BASE_URL ?>/products" class="pc-link">Productos</a>
<?php endif; ?>
```

Ocultar botones de crear, editar, eliminar o exportar segun permisos. Ocultar un boton no reemplaza la validacion del controlador.

## 8. Auditoria

Registrar auditoria en acciones criticas:

```php
Audit::log([
    'module' => 'products',
    'action' => 'products.created',
    'entity' => 'product',
    'entity_id' => $id,
    'description' => 'Producto creado',
    'status' => 'success',
]);
```

Si el evento sera consultado por administradores, agregar su descripcion en `config/audit_events.php`.

No registrar contrasenas, hashes, tokens, secretos, codigos MFA, codigos de verificacion, CSRF tokens ni IDs de sesion.

## 9. Traducciones

Agregar textos en:

```text
lang/es.php
lang/en.php
```

Usar `__()` en vistas y controladores cuando el texto sea visible para el usuario.

No dejar textos hardcodeados innecesarios ni mezclar idiomas dentro de una misma vista.

Antes de cerrar un modulo nuevo, ejecutar la validacion de claves de idioma:

```bash
php scripts/check_lang_keys.php
```

El script es de solo lectura: compara `lang/es.php` contra `lang/en.php` y reporta claves faltantes en cada direccion. Debe devolver `OK` (sin diferencias nuevas introducidas por el modulo) antes de dar el modulo por terminado.

## 10. Uploads

Si el modulo sube archivos, usar `UploadService` y agregar una categoria en `config/uploads.php`. No escribir archivos fuera de las rutas configuradas.

## Checklist Final

- [ ] El menu aparece solo con permiso.
- [ ] Las rutas protegidas validan permisos en backend.
- [ ] Las acciones `POST` usan CSRF.
- [ ] CRUD probado: listar, crear, editar, eliminar/inactivar.
- [ ] Auditoria registra acciones criticas.
- [ ] Glosario de auditoria actualizado si aplica.
- [ ] Traducciones en espanol e ingles.
- [ ] Modo claro y oscuro revisado.
- [ ] Responsive revisado.
- [ ] Exportaciones probadas si aplica.
- [ ] No hay errores PHP visibles.
- [ ] No hay errores JavaScript graves.
