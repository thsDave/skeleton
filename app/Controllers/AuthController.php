<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\CSRF;
use Core\Redirect;
use Core\Session;
use Core\Validator;
use Core\Logger;
use App\Models\User;
use App\Models\LoginLog;

class AuthController extends Controller
{
    private User $userModel;
    private LoginLog $logModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->logModel  = new LoginLog();
    }

    public function loginForm(): void
    {
        Auth::requireGuest();
        $this->view('auth.login');
    }

    public function loginProcess(): void
    {
        Auth::requireGuest();
        CSRF::validateOrFail();

        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');
        $config   = require dirname(__DIR__, 2) . '/config/app.php';

        $validator = new Validator();
        $validator->required('email', $email, 'Correo electrónico')
                  ->email('email', $email)
                  ->required('password', $password, 'Contraseña');

        if ($validator->fails()) {
            Redirect::withErrors('/login', $validator->errors(), ['email' => $email]);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $this->logModel->record(null, $email, 'failed', 'Email no encontrado');
            Logger::security("Login fallido - email no existe: {$email}");
            Redirect::withErrors('/login', ['general' => 'Las credenciales ingresadas no son válidas.'], ['email' => $email]);
        }

        // Verificar bloqueo
        if ($this->userModel->isLocked($user)) {
            $this->logModel->record($user['id'], $email, 'blocked', 'Cuenta bloqueada temporalmente');
            Logger::security("Login bloqueado para usuario ID {$user['id']}");
            Redirect::withErrors('/login', ['general' => 'La cuenta está bloqueada temporalmente. Intenta en 15 minutos.'], ['email' => $email]);
        }

        // Verificar status
        if ($user['status'] !== 'active') {
            $this->logModel->record($user['id'], $email, 'failed', 'Cuenta inactiva o bloqueada');
            Logger::security("Login fallido - cuenta inactiva ID {$user['id']}");
            Redirect::withErrors('/login', ['general' => 'Las credenciales ingresadas no son válidas.'], ['email' => $email]);
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            $this->userModel->incrementFailedAttempts($user['id']);

            $newAttempts = $user['failed_login_attempts'] + 1;
            if ($newAttempts >= $config['max_login_attempts']) {
                $this->userModel->lockAccount($user['id'], $config['lockout_minutes']);
                $this->logModel->record($user['id'], $email, 'blocked', 'Máximo de intentos alcanzado');
                Logger::security("Cuenta bloqueada por intentos fallidos - ID {$user['id']}");
                Redirect::withErrors('/login', ['general' => 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.'], ['email' => $email]);
            }

            $this->logModel->record($user['id'], $email, 'failed', 'Contraseña incorrecta');
            Logger::security("Login fallido - contraseña incorrecta ID {$user['id']}");
            Redirect::withErrors('/login', ['general' => 'Las credenciales ingresadas no son válidas.'], ['email' => $email]);
        }

        // Login exitoso
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->userModel->updateLastLogin($user['id'], $ip);
        $this->logModel->record($user['id'], $email, 'success', 'Login exitoso');
        Logger::security("Login exitoso - ID {$user['id']} desde {$ip}");

        Auth::login($user);
        Redirect::to('/dashboard');
    }

    public function logout(): void
    {
        if (!$this->isPost()) {
            Redirect::to('/dashboard');
        }

        CSRF::validateOrFail();

        $userId = Auth::id();
        Logger::security("Logout - ID {$userId}");
        Auth::logout();
        Redirect::to('/login');
    }
}
