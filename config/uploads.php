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

    ],
];
