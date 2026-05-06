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

    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
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
