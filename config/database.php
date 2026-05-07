<?php

return [
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => env('DB_PORT', '3306'),
    'dbname'  => env('DB_DATABASE', 'db_skeleton'),
    'charset' => 'utf8mb4',
    'user'    => env('DB_USERNAME', 'root'),
    'pass'    => env('DB_PASSWORD', ''),
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
