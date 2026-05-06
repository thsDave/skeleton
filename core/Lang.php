<?php

namespace Core;

class Lang
{
    private static array  $loaded = [];
    private static string $locale = 'es';

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function load(string $locale): void
    {
        if (isset(self::$loaded[$locale])) {
            return;
        }
        $safe = preg_replace('/[^a-z0-9_-]/i', '', $locale);
        $file = dirname(__DIR__) . '/lang/' . $safe . '.php';
        self::$loaded[$locale] = file_exists($file) ? (require $file) : [];
    }

    public static function get(string $key, array $replace = []): string
    {
        self::load(self::$locale);
        $value = self::$loaded[self::$locale][$key] ?? null;

        if ($value === null && self::$locale !== 'es') {
            self::load('es');
            $value = self::$loaded['es'][$key] ?? null;
        }

        if ($value === null) {
            return $key;
        }

        foreach ($replace as $k => $v) {
            $value = str_replace(':' . $k, $v, $value);
        }

        return $value;
    }
}

