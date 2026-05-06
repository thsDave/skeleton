<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['user'], $config['pass'], $config['options']);
            } catch (PDOException $e) {
                Logger::error('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Error de conexión con la base de datos. Contacte al administrador.');
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
