<?php

declare(strict_types=1);

// Autoloader
spl_autoload_register(function (string $class): void {
    $base = dirname(__DIR__);
    $map  = [
        'Core\\'      => $base . '/core/',
        'App\\Controllers\\' => $base . '/app/Controllers/',
        'App\\Models\\'      => $base . '/app/Models/',
    ];

    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file     = $dir . $relative . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

// Helpers globales — define __() en el espacio global (sin namespace)
// Core\Lang es cargado por el autoloader cuando __() lo necesite por primera vez
require dirname(__DIR__) . '/core/helpers.php';

// Configuración de errores según entorno
$appConfig = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['env'] === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Headers de seguridad
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://cdn.datatables.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net; font-src 'self' data: https://cdn.jsdelivr.net; img-src 'self' data: https://cdn.datatables.net;");

// Iniciar sesión
use Core\Session;
Session::start();

// Inicializar idioma del usuario
\Core\Lang::setLocale(Session::get('user_lang', 'es'));

// Definir BASE_URL para vistas
define('BASE_URL', rtrim($appConfig['url'], '/'));

// Routing
use Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ProfileController;
use App\Controllers\AccountController;
use App\Controllers\UsersController;
use App\Controllers\LanguagesController;
use App\Controllers\SystemInformationController;
use App\Controllers\LockController;
use App\Controllers\SecurityController;

$router = new Router();

// Auth
$router->get('/login',  [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'loginProcess']);
$router->post('/logout',[AuthController::class, 'logout']);

// Session lock
$router->get('/lock',    [LockController::class, 'show']);
$router->post('/unlock', [LockController::class, 'unlock']);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);

// Profile
$router->get('/profile',                   [ProfileController::class, 'index']);
$router->get('/profile/edit',              [ProfileController::class, 'edit']);
$router->post('/profile/update',           [ProfileController::class, 'update']);
$router->post('/profile/preferences',      [ProfileController::class, 'updatePreferences']);
$router->post('/profile/theme',            [ProfileController::class, 'updateTheme']);

// Account
$router->get('/account',                  [AccountController::class, 'index']);
$router->get('/account/edit-email',       [AccountController::class, 'editEmail']);
$router->post('/account/update-email',    [AccountController::class, 'updateEmail']);
$router->get('/account/edit-password',    [AccountController::class, 'editPassword']);
$router->post('/account/update-password', [AccountController::class, 'updatePassword']);

// Users (admin only)
$router->get('/users',                    [UsersController::class, 'index']);
$router->get('/users/create',             [UsersController::class, 'create']);
$router->post('/users/store',             [UsersController::class, 'store']);
$router->get('/users/edit/{id}',          [UsersController::class, 'edit']);
$router->post('/users/update/{id}',       [UsersController::class, 'update']);
$router->post('/users/delete/{id}',       [UsersController::class, 'delete']);

// Languages (admin)
$router->get('/languages',                   [LanguagesController::class, 'index']);
$router->get('/languages/create',            [LanguagesController::class, 'create']);
$router->post('/languages/store',            [LanguagesController::class, 'store']);
$router->get('/languages/edit/{id}',         [LanguagesController::class, 'edit']);
$router->post('/languages/update/{id}',      [LanguagesController::class, 'update']);
$router->post('/languages/toggle/{id}',      [LanguagesController::class, 'toggle']);

// System Information
$router->get('/system-information',          [SystemInformationController::class, 'index']);
$router->get('/system-information/edit',     [SystemInformationController::class, 'edit']);
$router->post('/system-information/update',  [SystemInformationController::class, 'update']);

// Manuals
$router->get('/manuals/create',              [SystemInformationController::class, 'createManual']);
$router->post('/manuals/store',              [SystemInformationController::class, 'storeManual']);
$router->post('/manuals/toggle/{id}',        [SystemInformationController::class, 'toggleManual']);
$router->get('/manuals/download/{id}',       [SystemInformationController::class, 'downloadManual']);

// Security (admin only)
$router->get('/security/sessions',        [SecurityController::class, 'sessions']);
$router->post('/security/sessions/update',[SecurityController::class, 'updateSessions']);

// Raíz — redirigir a dashboard o login
$router->get('/', [DashboardController::class, 'index']);

// Despachar
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Eliminar el prefijo del subdirectorio si aplica
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir));
}
$uri = '/' . ltrim($uri, '/');
$uri = rtrim($uri, '/') ?: '/';

$router->dispatch($method, $uri);
