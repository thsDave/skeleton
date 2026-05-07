# Despliegue en hosting compartido

Guía para instalar Skeleton MVC en hosting compartido con o sin acceso SSH.

---

## Opción A — Con acceso SSH (recomendado)

### Requisitos
- Hosting con PHP 8.3+, MySQL y acceso SSH.
- Composer instalado en el servidor (o instalable localmente).

### Pasos

```bash
# 1. Subir el proyecto al servidor (sin vendor/, sin .env, sin logs/)
#    Usa FTP, Git o el panel de archivos de tu hosting.

# 2. Conectarse al servidor por SSH
ssh usuario@tudominio.com

# 3. Navegar al directorio del proyecto
cd /home/usuario/public_html   # o donde hayas subido el proyecto

# 4. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 5. Crear el archivo .env
cp .env.example .env
nano .env
```

Edita `.env` con los datos de tu hosting:

```env
APP_NAME="Mi Sistema"
APP_URL="https://tudominio.com/public"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE="America/El_Salvador"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usuario_db_skeleton
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_segura

SESSION_LIFETIME=1800
LOG_PATH="logs/error.log"
```

```bash
# 6. Crear la base de datos desde phpMyAdmin del hosting
#    Importa los archivos SQL de la carpeta database/ en orden.

# 7. Configurar el dominio apuntando a /public
#    En cPanel: Dominios → directorio raíz → apuntar a /public_html/public
#    (depende de tu hosting)
```

---

## Opción B — Sin acceso SSH

### Pasos

1. **En tu computadora local**, ejecuta:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Sube todo el proyecto** al hosting (incluyendo `vendor/`) usando FTP o el panel de archivos.

3. **Crea `.env` manualmente** en el hosting:
   - Abre el administrador de archivos de tu hosting (cPanel, Plesk, etc.).
   - Crea un archivo llamado `.env` en la raíz del proyecto.
   - Copia el contenido de `.env.example` y edita los valores con los datos de tu hosting.

4. **Importa los archivos SQL** desde phpMyAdmin del hosting, en orden numérico.

5. **Configura el dominio** para que apunte a la carpeta `/public`:
   - Si tu hosting lo permite, cambia el document root a `/public`.
   - Si no puedes cambiar el document root, activa el reenvío en `.htaccess` raíz:
     ```apache
     # Descomenta estas líneas en .htaccess de la raíz:
     RewriteCond %{REQUEST_URI} !^/public/
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule ^(.*)$ public/$1 [L,QSA]
     ```

---

## Protección de archivos sensibles

El `.htaccess` de `public/` ya protege el acceso directo a archivos PHP fuera de `/public`.

El `.htaccess` raíz del proyecto bloquea acceso web a:
- `.env` (credenciales)
- `composer.json` / `composer.lock`
- Carpetas: `app/`, `core/`, `config/`, `database/`, `vendor/`, `logs/`

> Verifica siempre que `.env` **no sea accesible** desde el navegador antes de dar el sistema por listo en producción. Intenta acceder a `https://tudominio.com/.env` — debe devolver 403 o 404.

---

## Verificación post-despliegue

- [ ] El sistema carga sin errores blancos.
- [ ] El login funciona con las credenciales de prueba.
- [ ] El dashboard carga correctamente.
- [ ] `.env` no es accesible desde el navegador.
- [ ] `APP_DEBUG=false` en `.env` (no se muestran trazas al usuario).
- [ ] Los uploads de imágenes funcionan (permisos de escritura en `public/uploads/`).
