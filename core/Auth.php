<?php

namespace Core;

class Auth
{
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['nombres'] . ' ' . $user['apellidos']);
        Session::set('user_nombres', $user['nombres']);
        Session::set('user_apellidos', $user['apellidos']);
        Session::set('_last_activity', time());
    }

    public static function check(): bool
    {
        if (!Session::has('user_id')) {
            return false;
        }
        return Session::checkTimeout();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Redirect::to('/login');
            exit;
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            Redirect::to('/dashboard');
            exit;
        }
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): array
    {
        return [
            'id'        => Session::get('user_id'),
            'email'     => Session::get('user_email'),
            'name'      => Session::get('user_name'),
            'nombres'   => Session::get('user_nombres'),
            'apellidos' => Session::get('user_apellidos'),
        ];
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function updateSession(array $data): void
    {
        if (isset($data['email'])) {
            Session::set('user_email', $data['email']);
        }
        if (isset($data['nombres'])) {
            Session::set('user_nombres', $data['nombres']);
            Session::set('user_name', $data['nombres'] . ' ' . Session::get('user_apellidos'));
        }
        if (isset($data['apellidos'])) {
            Session::set('user_apellidos', $data['apellidos']);
            Session::set('user_name', Session::get('user_nombres') . ' ' . $data['apellidos']);
        }
    }
}
