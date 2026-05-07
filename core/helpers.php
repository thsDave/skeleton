<?php

// Archivo sin namespace — define funciones globales del sistema

if (!function_exists('env')) {
    /**
     * Lee una variable de entorno con soporte de valor por defecto.
     * Convierte strings "true", "false" y "null" a sus tipos nativos.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true',  '(true)'  => true,
            'false', '(false)' => false,
            'null',  '(null)'  => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('__')) {
    /**
     * Helper global de traducción.
     * Detecta el locale actual desde Core\Lang (ya inicializado en index.php)
     * y devuelve la cadena traducida. Si la clave no existe, devuelve la clave.
     */
    function __(string $key, array $replace = []): string
    {
        return \Core\Lang::get($key, $replace);
    }
}

if (!function_exists('can')) {
    /**
     * Verifica si el usuario autenticado posee el permiso indicado.
     * Uso en vistas: can('users.view'), can('roles_permissions.edit')
     */
    function can(string $permission): bool
    {
        return \Core\Auth::can($permission);
    }
}
