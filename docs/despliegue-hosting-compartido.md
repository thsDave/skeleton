# Despliegue En Hosting Compartido

Guia practica para instalar Skeleton en un hosting compartido con PHP y MySQL.

## Requisitos

- PHP 8.3 o superior.
- MySQL o MariaDB.
- Composer en el servidor, o dependencias instaladas localmente.
- Acceso a phpMyAdmin o herramienta equivalente.
- Posibilidad de apuntar el dominio a `public/` o usar reglas `.htaccess`.

## Subida De Archivos

Subir el proyecto sin archivos locales sensibles:

- No subir `.env` local.
- No subir logs.
- No subir backups.
- No subir uploads reales de otro ambiente.
- No subir `node_modules/`.

Si el hosting tiene SSH, puedes subir el proyecto sin `vendor/` y ejecutar:

```bash
composer install --no-dev --optimize-autoloader
```

Si no tiene SSH, ejecuta ese comando localmente y sube tambien `vendor/`.

## Configurar .env

Crear `.env` en la raiz del proyecto a partir de `.env.example`.

Valores minimos:

```env
APP_NAME="Mi Sistema"
APP_URL="https://tudominio.com"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE="America/El_Salvador"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base
DB_USERNAME=usuario_base
DB_PASSWORD=contrasena_segura
```

`APP_URL` debe apuntar a la URL publica real y no debe terminar con barra.

## Base De Datos

1. Crear la base de datos desde el panel del hosting.
2. Importar el schema consolidado oficial:

```text
database/schema/skeleton_schema.sql
```

Para instalaciones nuevas no importes las migraciones incrementales una por una.

## Configurar public/

Opcion recomendada: apuntar el document root del dominio a:

```text
/ruta-del-proyecto/public
```

Si el hosting no permite cambiar document root, usar el `.htaccess` raiz y activar el reenvio a `public/` siguiendo los comentarios incluidos en ese archivo.

## Permisos De Carpetas

Verificar escritura en:

- `public/uploads`
- `public/uploads/profiles`
- `public/uploads/manuals`
- `logs`
- `storage/logs` si existe
- `storage/cache` si existe

## Seguridad

- `APP_DEBUG=false` en produccion.
- Usar HTTPS.
- Confirmar que `.env` no sea accesible desde navegador.
- Confirmar que `database/`, `config/`, `core/`, `app/`, `vendor/` y `logs/` no sean accesibles.
- Cambiar la contrasena inicial del administrador.
- Configurar SMTP real.
- Configurar OAuth con Redirect URI del dominio final.
- Revisar Salud del Sistema.

## Verificacion

- [ ] Login carga.
- [ ] Dashboard carga.
- [ ] Assets CSS/JS cargan.
- [ ] Uploads funcionan.
- [ ] Recuperacion de contrasena envia correo.
- [ ] OAuth usa Redirect URI correcta.
- [ ] Salud del Sistema no muestra errores criticos.
- [ ] `.env` devuelve 403 o 404 desde navegador.
