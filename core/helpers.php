<?php

// Archivo sin namespace — define funciones globales del sistema

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
