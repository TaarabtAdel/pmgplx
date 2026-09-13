<?php

namespace App\Support\DaoTao;

class DatAnhDuongDan
{
    public const SESSION_KEY = 'dat.anh_duong_dan';

    public static function get(): string
    {
        return trim((string) session(self::SESSION_KEY, ''));
    }

    public static function store(string $path): void
    {
        session([self::SESSION_KEY => rtrim(trim($path), '/\\')]);
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function has(): bool
    {
        return self::get() !== '';
    }

    public static function preview(): string
    {
        $path = self::get();
        if ($path === '') {
            return '';
        }

        if (mb_strlen($path) <= 80) {
            return $path;
        }

        return mb_substr($path, 0, 40).'…'.mb_substr($path, -20);
    }
}
