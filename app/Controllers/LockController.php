<?php

namespace App\Controllers;

use Core\Auth;
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

    public function unlock(): void
    {
        if (!Session::has('user_id') || !Session::get('is_locked', false)) {
            Redirect::to('/login');
            exit;
        }

        CSRF::verify();

        $password = $_POST['password'] ?? '';
        $userId   = (int) Session::get('user_id');

        $userModel = new User();
        $user      = $userModel->findById($userId);

        if (!$user || !password_verify($password, $user['password'])) {
            Session::flash('lock_error', __('lock.invalid_password'));
            Redirect::to('/lock');
            exit;
        }

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
}
