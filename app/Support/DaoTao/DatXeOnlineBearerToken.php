<?php

namespace App\Support\DaoTao;

class DatXeOnlineBearerToken
{
    public const SESSION_KEY = 'dat.xeonline_bearer_token';

    public static function get(): string
    {
        return trim((string) session(self::SESSION_KEY, ''));
    }

    public static function store(string $token): void
    {
        $token = trim($token);
        if (str_starts_with(strtolower($token), 'bearer ')) {
            $token = trim(substr($token, 7));
        }

        session([self::SESSION_KEY => $token]);
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function has(): bool
    {
        return self::get() !== '';
    }

    public static function maskedPreview(): string
    {
        $token = self::get();
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return '***';
        }

        return substr($token, 0, 8).'…'.substr($token, -4);
    }
}
