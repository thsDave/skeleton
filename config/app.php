<?php

return [
    'name'            => 'Skeleton MVC',
    'url'             => 'http://localhost/template/public',
    'env'             => 'development', // 'production'
    'debug'           => true,
    'timezone'        => 'America/El_Salvador',
    'session_timeout' => 1800, // 30 minutos en segundos
    'log_path'        => dirname(__DIR__) . '/logs/security.log',
    'max_login_attempts' => 5,
    'lockout_minutes'    => 15,
];
