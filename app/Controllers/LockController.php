<?php

namespace App\Controllers;

use Core\Audit;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use App\Models\User;

class LockController
{
    public function show(): void
    {
        if (!Session::has('user_id')) {
            Redirect::to('/login');
            exit;
        }

        if (!Session::get('is_locked', false)) {
            Redirect::to('/dashboard');
            exit;
        }

        require dirname(__DIR__) . '/Views/lock.php';
    }

    /**
     * AJAX endpoint called by the client-side inactivity timer before redirecting
     * to /lock. Sets is_locked in the session so LockController::show() renders
     * the lock screen instead of redirecting back to /dashboard.
     *
     * POST /lock/session
     */
    public function lockSession(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Session::has('user_id')) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'reason' => 'unauthenticated']);
            exit;
        }

        // Idempotent — already locked
        if (Session::get('is_locked', false)) {
            echo json_encode(['ok' => true]);
            exit;
        }

        // Manual CSRF check: verify() returns bool; no token regeneration here
        // so the lock.php form token stays valid after this call.
        if (!CSRF::verify()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'reason' => 'csrf']);
            exit;
        }

        $intendedUrl = $this->sanitizeClientUrl($_POST['intended_url'] ?? '');

        Session::set('is_locked', true);
        Session::set('locked_at', time());
        if (!Session::has('intended_url')) {
            Session::set('intended_url', $intendedUrl);
        }

        Audit::log(['module' => 'auth', 'action' => 'session_locked',
            'description' => 'Sesión bloqueada por inactividad', 'status' => 'warning']);

        echo json_encode(['ok' => true]);
        exit;
    }

    public function unlock(): void
    {
        if (!Session::has('user_id') || !Session::get('is_locked', false)) {
            Redirect::to('/login');
            exit;
        }

        // validateOrFail() verifies and regenerates the CSRF token on success
        CSRF::validateOrFail();

        $password = $_POST['password'] ?? '';
        $userId   = (int) Session::get('user_id');

        $userModel = new User();
        $user      = $userModel->findById($userId);

        if (!$user || !password_verify($password, $user['password'])) {
            Audit::log(['module' => 'auth', 'action' => 'unlock_failed',
                'entity' => 'user', 'entity_id' => $userId,
                'description' => 'Intento fallido de desbloqueo de sesión', 'status' => 'failed']);
            Session::flash('lock_error', __('lock.invalid_password'));
            Redirect::to('/lock');
            exit;
        }

        Audit::log(['module' => 'auth', 'action' => 'session_unlocked',
            'entity' => 'user', 'entity_id' => $userId,
            'description' => 'Sesión desbloqueada correctamente', 'status' => 'success']);

        // Unlock: clear lock state, refresh activity timestamp, regenerate session
        Session::delete('is_locked');
        Session::delete('locked_at');
        Session::set('_last_activity', time());
        Session::regenerate();

        $intendedUrl = Session::get('intended_url', '/dashboard');
        Session::delete('intended_url');

        Session::flash('success', __('lock.session_unlocked'));
        Redirect::to($intendedUrl);
        exit;
    }

    private function sanitizeClientUrl(string $raw): string
    {
        if (!$raw) return '/dashboard';

        $uri = parse_url($raw, PHP_URL_PATH) ?: '';

        // Strip the subdirectory prefix (same logic as public/index.php)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . ltrim($uri, '/');
        $uri = rtrim($uri, '/') ?: '/';

        // Block open-redirect attempts
        if (preg_match('#^//#', $uri) || str_contains($uri, '://')) {
            return '/dashboard';
        }

        if (in_array(strtolower($uri), ['/lock', '/unlock'], true)) {
            return '/dashboard';
        }

        return $uri;
    }
}
