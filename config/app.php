<?php

return [
    'name'            => env('APP_NAME', 'Skeleton MVC'),
    'url'             => env('APP_URL', 'http://localhost/template/public'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => env('APP_DEBUG', false),
    'timezone'        => env('APP_TIMEZONE', 'America/El_Salvador'),
    'session_timeout' => (int) env('SESSION_LIFETIME', 1800),
    'log_path'        => dirname(__DIR__) . '/logs/security.log',
    'error_log_path'  => dirname(__DIR__) . '/' . ltrim((string) env('LOG_PATH', 'logs/error.log'), '/'),
    'max_login_attempts' => 5,
    'lockout_minutes'    => 15,

    // Carga de imágenes de perfil
    'upload_max_size'      => 2 * 1024 * 1024, // 2 MB
    'upload_allowed_mime'  => ['image/jpeg', 'image/png', 'image/webp'],
    'upload_allowed_ext'   => ['jpg', 'jpeg', 'png', 'webp'],
    'upload_profile_path'  => dirname(__DIR__) . '/public/uploads/profiles/',
    'upload_profile_url'   => '/uploads/profiles/',

    // Carga de manuales
    'upload_manuals_max_size' => 10 * 1024 * 1024, // 10 MB
    'upload_manuals_mime'     => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'upload_manuals_path'     => dirname(__DIR__) . '/public/uploads/manuals/',
    'upload_manuals_url'      => '/uploads/manuals/',
];
