# Backups Y Restauracion

Esta guia define que respaldar y como restaurar una instalacion de Skeleton.

## Que Respaldar

- Base de datos completa.
- `public/uploads`.
- `.env`.
- Archivos de configuracion relevantes si fueron modificados para produccion.

## Que No Guardar En Carpetas Publicas

No guardar backups dentro de:

- `public/`
- `public/uploads/`
- cualquier carpeta accesible desde el navegador

Los backups pueden contener credenciales, datos personales o informacion operativa sensible.

## Backup Desde phpMyAdmin

1. Entrar a phpMyAdmin.
2. Seleccionar la base de datos del sistema.
3. Ir a Exportar.
4. Elegir formato SQL.
5. Usar exportacion rapida o personalizada segun necesidad.
6. Descargar el archivo `.sql`.
7. Guardarlo fuera de `public/` y con acceso restringido.

## Restauracion Desde phpMyAdmin

1. Crear una base de datos vacia o limpiar una base destinada a restauracion.
2. Seleccionar la base.
3. Ir a Importar.
4. Cargar el archivo `.sql`.
5. Ejecutar la importacion.
6. Verificar tablas, usuario administrador y configuraciones.
7. Ajustar `.env` para apuntar a la base restaurada.

## Backup De Uploads

Respaldar la carpeta:

```text
public/uploads
```

Usar ZIP, el panel del hosting, FTP/SFTP o herramientas del proveedor. Mantener permisos y estructura de subcarpetas.

## Restauracion De Uploads

1. Restaurar `public/uploads` en la misma ruta.
2. Verificar permisos de lectura/escritura.
3. Probar avatars, logos, favicon, fondos de login y manuales.

## Frecuencia Recomendada

- Diario para sistemas con uso constante.
- Antes de despliegues.
- Antes de importar migraciones.
- Semanal para ambientes de baja actividad.

## Seguridad

- Cifrar backups cuando salgan del servidor.
- Restringir acceso al equipo responsable.
- No enviar backups por canales inseguros.
- No conservar respaldos obsoletos indefinidamente.
- Probar restauracion periodicamente.

## Checklist De Restauracion

- [ ] Base importada sin errores.
- [ ] `.env` apunta a la base correcta.
- [ ] `APP_URL` corresponde al dominio real.
- [ ] Login funciona.
- [ ] Uploads cargan.
- [ ] Salud del Sistema no reporta errores criticos.
- [ ] `.env` y backups no son accesibles desde navegador.
