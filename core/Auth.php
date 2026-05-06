<?php

namespace Core;

class Auth
{
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id',            $user['id']);
        Session::set('user_email',         $user['email']);
        Session::set('user_name',          $user['nombres'] . ' ' . $user['apellidos']);
        Session::set('user_nombres',       $user['nombres']);
        Session::set('user_apellidos',     $user['apellidos']);
        Session::set('user_role_slug',     $user['role_slug']   ?? 'user');
        Session::set('user_role_name',     $user['role_name']   ?? 'Usuario');
        Session::set('user_profile_image', $user['profile_image'] ?? null);
        Session::set('user_theme',         $user['theme_preference'] ?? 'light');
        Session::set('user_lang',          $user['lang_code'] ?? 'es');
        Session::set('user_lang_id',       $user['language_id'] ?? null);
        Session::set('_last_activity',     time());
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

    public static function isAdmin(): bool
    {
        return Session::get('user_role_slug') === 'administrator';
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            Session::flash('error', 'Acceso denegado. No tienes permisos para acceder a esa sección.');
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
            'id'            => Session::get('user_id'),
            'email'         => Session::get('user_email'),
            'name'          => Session::get('user_name'),
            'nombres'       => Session::get('user_nombres'),
            'apellidos'     => Session::get('user_apellidos'),
            'role_slug'     => Session::get('user_role_slug'),
            'role_name'     => Session::get('user_role_name'),
            'profile_image' => Session::get('user_profile_image'),
            'theme'         => Session::get('user_theme', 'light'),
            'lang'          => Session::get('user_lang', 'es'),
            'lang_id'       => Session::get('user_lang_id'),
        ];
    }

    public static function theme(): string
    {
        return Session::get('user_theme', 'light');
    }

    public static function lang(): string
    {
        return Session::get('user_lang', 'es');
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
        if (array_key_exists('profile_image', $data)) {
            Session::set('user_profile_image', $data['profile_image']);
        }
        if (isset($data['theme'])) {
            Session::set('user_theme', $data['theme']);
        }
        if (isset($data['lang'])) {
            Session::set('user_lang', $data['lang']);
        }
        if (array_key_exists('lang_id', $data)) {
            Session::set('user_lang_id', $data['lang_id']);
        }
    }
}
