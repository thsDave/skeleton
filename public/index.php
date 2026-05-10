<?php

declare(strict_types=1);

ob_start();

// Cargar Composer — muestra error amigable si falta vendor/
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Dependencias faltantes</title></head>'
        . '<body style="font-family:sans-serif;padding:2rem;max-width:600px;margin:auto;">'
        . '<h2 style="color:#c0392b;">Faltan dependencias del proyecto</h2>'
        . '<p>Ejecuta el siguiente comando en la raíz del sistema y recarga esta página:</p>'
        . '<pre style="background:#f4f4f4;padding:1rem;border-radius:4px;">composer install</pre>'
        . '</body></html>';
    exit;
}
require $autoload;

// Cargar variables de entorno desde .env (safeLoad no falla si el archivo no existe)
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Helpers globales — define __(), can(), env() en el espacio global (sin namespace)
require dirname(__DIR__) . '/core/helpers.php';

// Configuración de la aplicación
$appConfig = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

// ErrorHandler manages all output; never display raw PHP errors
ini_set('display_errors', '0');
error_reporting(E_ALL);

\Core\ErrorHandler::register();

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
use App\Controllers\RolesPermissionsController;
use App\Controllers\AuditLogsController;
use App\Controllers\PasswordResetController;
use App\Controllers\SmtpSettingsController;
use App\Controllers\MfaSettingsController;
use App\Controllers\LoginAttemptsSettingsController;
use App\Controllers\AuthenticationController;
use App\Controllers\ExternalAuthController;
use App\Controllers\TwoFactorController;
use App\Controllers\TwoFactorChallengeController;
use App\Controllers\AppearanceController;

$router = new Router();

// Auth
$router->get('/login',  [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'loginProcess']);
$router->post('/logout',[AuthController::class, 'logout']);

// Password reset
$router->get('/forgot-password',         [PasswordResetController::class, 'showForgotForm']);
$router->post('/forgot-password',        [PasswordResetController::class, 'sendResetLink']);
$router->get('/reset-password/{token}',  [PasswordResetController::class, 'showResetForm']);
$router->post('/reset-password',         [PasswordResetController::class, 'resetPassword']);

// Session lock
$router->get('/lock',          [LockController::class, 'show']);
$router->post('/lock/session', [LockController::class, 'lockSession']); // AJAX: mark session as locked
$router->post('/unlock',       [LockController::class, 'unlock']);

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
$router->post('/users/unlock/{id}',       [UsersController::class, 'unlock']);

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

// Security
$router->get('/security/sessions',        [SecurityController::class, 'sessions']);
$router->post('/security/sessions/update',[SecurityController::class, 'updateSessions']);

// SMTP Settings
$router->get('/security/smtp',            [SmtpSettingsController::class, 'index']);
$router->post('/security/smtp/update',    [SmtpSettingsController::class, 'update']);
$router->post('/security/smtp/test',      [SmtpSettingsController::class, 'test']);

// MFA (módulo separado, sin cambios)
$router->get('/security/mfa',         [MfaSettingsController::class, 'index']);
$router->post('/security/mfa/update', [MfaSettingsController::class, 'update']);

// Intentos fallidos (módulo nuevo independiente)
$router->get('/security/attempts',         [LoginAttemptsSettingsController::class, 'index']);
$router->post('/security/attempts/update', [LoginAttemptsSettingsController::class, 'update']);

// Autenticación — métodos de login y proveedores OAuth
$router->get('/security/authentication',                                    [AuthenticationController::class, 'index']);
$router->post('/security/authentication/settings/update',                   [AuthenticationController::class, 'updateSettings']);
$router->get('/security/authentication/providers/edit/{id}',                [AuthenticationController::class, 'editProvider']);
$router->post('/security/authentication/providers/update/{id}',             [AuthenticationController::class, 'updateProvider']);
$router->post('/security/authentication/providers/toggle/{id}',             [AuthenticationController::class, 'toggleProvider']);
$router->post('/security/authentication/providers/test/{id}',               [AuthenticationController::class, 'testProvider']);

// OAuth externo — flujo público
$router->get('/auth/external/{provider}/redirect',  [ExternalAuthController::class, 'redirect']);
$router->get('/auth/external/{provider}/callback',  [ExternalAuthController::class, 'callback']);

// Vinculación de cuentas externas desde Mi Cuenta
$router->get('/account/external/link/{provider}',   [AccountController::class, 'initiateLink']);
$router->post('/account/external/unlink/{id}',      [AccountController::class, 'unlinkAccount']);

// Roles y Permisos
$router->get('/roles-permissions',                    [RolesPermissionsController::class, 'index']);
$router->get('/roles-permissions/edit/{id}',          [RolesPermissionsController::class, 'edit']);
$router->post('/roles-permissions/update/{id}',       [RolesPermissionsController::class, 'update']);

// Audit Logs
$router->get('/audit-logs',             [AuditLogsController::class, 'index']);
$router->get('/audit-logs/show/{id}',   [AuditLogsController::class, 'show']);

// Two-Factor Authentication — profile management
$router->get('/profile/two-factor',                        [TwoFactorController::class, 'show']);
$router->post('/profile/two-factor/enable-email',          [TwoFactorController::class, 'enableEmail']);
$router->get('/profile/two-factor/confirm',                [TwoFactorController::class, 'confirmForm']);
$router->post('/profile/two-factor/confirm',               [TwoFactorController::class, 'confirm']);
$router->post('/profile/two-factor/resend',                [TwoFactorController::class, 'resend']);
$router->get('/profile/two-factor/setup-authenticator',    [TwoFactorController::class, 'setupAuthenticator']);
$router->post('/profile/two-factor/confirm-authenticator', [TwoFactorController::class, 'confirmAuthenticator']);
$router->post('/profile/two-factor/disable',               [TwoFactorController::class, 'disable']);

// Two-Factor Authentication — login challenge
$router->get('/two-factor/challenge',  [TwoFactorChallengeController::class, 'show']);
$router->post('/two-factor/challenge', [TwoFactorChallengeController::class, 'verify']);
$router->post('/two-factor/resend',    [TwoFactorChallengeController::class, 'resend']);

// Appearance
$router->get('/appearance',                         [AppearanceController::class, 'index']);
$router->post('/appearance/update',                 [AppearanceController::class, 'update']);
$router->post('/appearance/reset-logo',             [AppearanceController::class, 'resetLogo']);
$router->post('/appearance/reset-favicon',          [AppearanceController::class, 'resetFavicon']);
$router->post('/appearance/reset-login-background', [AppearanceController::class, 'resetLoginBackground']);
$router->post('/appearance/reset-colors',           [AppearanceController::class, 'resetColors']);

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
