# Permisos Y Auditoria

Skeleton usa permisos granulares para autorizacion y auditoria centralizada para acciones relevantes.

## Crear Permisos Para Un Modulo

1. Crear o registrar el modulo en `tbl_modules`.
2. Crear permisos en `tbl_permissions` con formato `modulo.accion`.
3. Asignar permisos al rol Administrador en `tbl_role_permissions`.
4. Agregar permisos a otros roles solo si forman parte de la instalacion base.

Ejemplo de permisos:

- `products.view`
- `products.create`
- `products.edit`
- `products.delete`
- `products.export`

## Validar Permisos En Backend

Cada accion administrativa debe validar su permiso:

```php
Auth::requirePermission('products.view');
```

Para acciones de cuenta propia usar autenticacion, no permiso administrativo:

```php
Auth::requireAuth();
```

Si el usuario no tiene permiso, el sistema debe responder con 403 y detener la ejecucion.

## Menus Y Botones

Usar `can()` en vistas para ocultar opciones:

```php
<?php if (can('products.create')): ?>
  <a href="<?= BASE_URL ?>/products/create" class="btn btn-primary">Crear</a>
<?php endif; ?>
```

Esto mejora la interfaz, pero no reemplaza `Auth::requirePermission()` en el controlador.

## Manejo De 403

Usar la validacion central de permisos. No implementar respuestas 403 manuales si `Auth::requirePermission()` cubre el caso.

Las denegaciones deben quedar registradas por seguridad y auditoria cuando corresponda.

## Registrar Auditoria

Usar `Core\Audit::log()`:

```php
Audit::log([
    'module' => 'products',
    'action' => 'products.updated',
    'entity' => 'product',
    'entity_id' => $id,
    'description' => 'Producto actualizado',
    'old_values' => $oldSafeValues,
    'new_values' => $newSafeValues,
    'status' => 'success',
]);
```

El servicio completa datos como usuario, IP, user agent, ruta, metodo HTTP y fecha cuando aplica.

## Glosario De Auditoria

El glosario esta en:

```text
config/audit_events.php
```

Agregar una entrada cuando el evento sea importante para administradores o revisiones de seguridad. Usar una descripcion clara y una recomendacion operativa breve.

Ejemplo conceptual:

```php
'products.updated' => $event(
    'Productos',
    'Producto actualizado',
    'Se actualizo un producto.',
    'info',
    'Revisar cambios si no corresponden al flujo normal.'
),
```

## Estados Recomendados

Usar estados normalizados:

- `success`
- `failed`
- `denied`
- `warning`
- `info`
- `critical` cuando el evento tenga impacto alto de seguridad

## Datos Que No Deben Registrarse

No registrar:

- Contrasenas.
- Hashes.
- Tokens.
- `client_secret`.
- `access_token`.
- `refresh_token`.
- Codigos MFA.
- Codigos de verificacion.
- CSRF tokens.
- Session IDs.
- Secretos TOTP.
- Contenido completo de archivos o exportaciones.

Si necesitas contexto, registra IDs, cantidades, filtros seguros y descripciones sin secretos.

## Eventos Recomendados En Nuevos Modulos

- Crear recurso: `modulo.created`.
- Actualizar recurso: `modulo.updated`.
- Eliminar o inactivar recurso: `modulo.deleted` o `modulo.deactivated`.
- Exportar datos: `modulo.exported`.
- Fallos relevantes: `modulo.action_failed`.
- Denegaciones de negocio: `modulo.denied`.

Evitar auditar visualizaciones simples si generan demasiado ruido.
