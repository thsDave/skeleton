<?php

namespace Core;

class CSRF
{
    private const TOKEN_KEY = '_csrf_token';

    public static function generate(): string
    {
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(self::TOKEN_KEY);
    }

    public static function token(): string
    {
        return self::generate();
    }

    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        $stored = Session::get(self::TOKEN_KEY, '');

        if (empty($token) || empty($stored)) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public static function validateOrFail(): void
    {
        if (!self::verify()) {
            http_response_code(403);
            die('Token CSRF inválido. Por favor recarga la página e intenta de nuevo.');
        }
        // Regenerate token after successful validation
        Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
    }
}
