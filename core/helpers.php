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

if (!function_exists('profile_avatar_url')) {
    /**
     * Devuelve la URL publica de una imagen de perfil o el avatar por defecto.
     * Valida que el archivo exista para evitar imagenes rotas en layouts globales.
     */
    function profile_avatar_url(?string $filename = null): string
    {
        $fallback = BASE_URL . '/assets/images/user/avatar-1.jpg';

        $filename = trim((string) $filename);
        if ($filename === '') {
            return $fallback;
        }

        $safeFilename = basename(str_replace('\\', '/', $filename));
        if ($safeFilename === '') {
            return $fallback;
        }

        $diskPath = dirname(__DIR__) . '/public/uploads/profiles/' . $safeFilename;
        if (!is_file($diskPath)) {
            return $fallback;
        }

        $url = BASE_URL . '/uploads/profiles/' . rawurlencode($safeFilename);
        $mtime = @filemtime($diskPath);

        return $mtime ? $url . '?v=' . $mtime : $url;
    }
}

if (!function_exists('current_user_avatar_url')) {
    /**
     * Devuelve el avatar del usuario autenticado usando la sesion como fuente.
     */
    function current_user_avatar_url(?array $user = null): string
    {
        $user = $user ?? \Core\Auth::user();
        return profile_avatar_url($user['profile_image'] ?? null);
    }
}
