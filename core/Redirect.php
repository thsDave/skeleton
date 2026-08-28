<?php

namespace Core;

class Redirect
{
    public static function to(string $path): void
    {
        $config = require dirname(__DIR__) . '/config/app.php';
        $base = rtrim($config['url'], '/');
        $path = '/' . ltrim($path, '/');
        header('Location: ' . $base . $path);
        exit;
    }

    /**
     * Sanea una URL potencialmente no confiable (ej. HTTP_REFERER o
     * REQUEST_URI) y devuelve únicamente una ruta interna segura para este
     * mismo sitio. Descarta el host/esquema de cualquier URL absoluta
     * (evita open redirect) y rechaza rutas protocol-relative ('//host/...').
     */
    public static function sanitizeInternalPath(?string $rawUrl, string $fallback = '/dashboard'): string
    {
        if (!$rawUrl) {
            return $fallback;
        }

        $path = parse_url($rawUrl, PHP_URL_PATH) ?: '';

        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        if (preg_match('#^//#', $path) || str_contains($path, '://')) {
            return $fallback;
        }

        return $path;
    }

    public static function back(string $fallback = '/dashboard'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        self::to(self::sanitizeInternalPath($referer, $fallback));
    }

    public static function withSuccess(string $path, string $message): void
    {
        Session::flash('success', $message);
        self::to($path);
    }

    public static function withError(string $path, string $message): void
    {
        Session::flash('error', $message);
        self::to($path);
    }

    public static function withErrors(string $path, array $errors, array $old = []): void
    {
        Session::flash('errors', $errors);
        Session::flash('old', $old);
        self::to($path);
    }
}
