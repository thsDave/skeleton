# Base De Datos

Para una instalacion nueva de Skeleton, importar solo el schema consolidado oficial:

```text
database/schema/skeleton_schema.sql
```

El archivo esta preparado para una base de datos vacia y contiene:

- Estructura completa de tablas.
- Llaves primarias, foraneas e indices.
- Catalogos base.
- Modulos, permisos y roles base.
- Permisos asignados al rol Administrador.
- Configuraciones iniciales.
- Usuario administrador temporal.

Credenciales iniciales:

```text
Correo: admin@example.com
Password temporal: Admin123*
```

Cambiar la contrasena inmediatamente despues del primer acceso.

## Migraciones

La carpeta `database/migrations/` conserva archivos incrementales de referencia para proyectos que ya venian evolucionando desde versiones anteriores.

No son necesarias para una instalacion limpia si ya importaste:

```text
database/schema/skeleton_schema.sql
```

## Reglas

- Importar el schema en una base vacia.
- No subir dumps con datos reales.
- No incluir tokens, sesiones, logs, secretos SMTP ni `client_secret` OAuth.
- No usar `IF NOT EXISTS`.
- No usar `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`.
