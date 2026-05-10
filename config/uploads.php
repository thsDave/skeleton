<?php

return [

    'forbidden_extensions' => [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'bat', 'cmd', 'sh', 'pl', 'cgi', 'js', 'html', 'htm', 'svg',
    ],

    'profiles' => [

        'profile_images' => [
            'disk_path'      => dirname(__DIR__) . '/public/uploads/profiles/',
            'web_path'       => 'uploads/profiles/',
            'max_size'       => 2 * 1024 * 1024,
            'allowed_mime'   => ['image/jpeg', 'image/png', 'image/webp'],
            'validate_image' => true,
            'prefix'         => 'avatar_',
        ],

        'user_manuals' => [
            'disk_path'      => dirname(__DIR__) . '/public/uploads/manuals/',
            'web_path'       => 'uploads/manuals/',
            'max_size'       => 10 * 1024 * 1024,
            'allowed_mime'   => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'validate_image' => false,
            'prefix'         => 'manual_',
        ],

        'appearance_logo' => [
            'disk_path'      => dirname(__DIR__) . '/public/uploads/appearance/logo/',
            'web_path'       => 'uploads/appearance/logo/',
            'max_size'       => 2 * 1024 * 1024,
            'allowed_mime'   => ['image/jpeg', 'image/png', 'image/webp'],
            'validate_image' => true,
            'prefix'         => 'logo_',
        ],

        'appearance_favicon' => [
            'disk_path'      => dirname(__DIR__) . '/public/uploads/appearance/favicon/',
            'web_path'       => 'uploads/appearance/favicon/',
            'max_size'       => 1 * 1024 * 1024,
            'allowed_mime'   => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'],
            'validate_image' => false,
            'prefix'         => 'favicon_',
        ],

        'appearance_login_background' => [
            'disk_path'      => dirname(__DIR__) . '/public/uploads/appearance/login/',
            'web_path'       => 'uploads/appearance/login/',
            'max_size'       => 5 * 1024 * 1024,
            'allowed_mime'   => ['image/jpeg', 'image/png', 'image/webp'],
            'validate_image' => true,
            'prefix'         => 'login_bg_',
        ],

    ],
];
