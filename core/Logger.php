<?php

namespace Core;

class Logger
{
    private static function write(string $level, string $message): void
    {
        $config = require dirname(__DIR__) . '/config/app.php';
        $logPath = $config['log_path'];

        $line = sprintf(
            "[%s] [%s] [IP: %s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $message
        );

        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function security(string $message): void
    {
        self::write('SECURITY', $message);
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }
}
