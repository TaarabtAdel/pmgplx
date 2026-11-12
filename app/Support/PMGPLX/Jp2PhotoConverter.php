<?php

namespace App\Support\PMGPLX;

class Jp2PhotoConverter
{
    private static ?string $binary = null;

    public static function isJp2(string $binary): bool
    {
        return str_contains(substr($binary, 0, 32), 'jP  ');
    }

    public static function isAvailable(): bool
    {
        return self::resolveBinary() !== null;
    }

    public static function toPngBinary(string $jp2Binary): ?string
    {
        $decompress = self::resolveBinary();
        if ($decompress === null) {
            return null;
        }

        $cacheFile = self::cachePath($jp2Binary);
        if (is_file($cacheFile)) {
            $cached = file_get_contents($cacheFile);

            return $cached !== false && $cached !== '' ? $cached : null;
        }

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gplx-anh-'.uniqid('', true);
        if (! @mkdir($dir) && ! is_dir($dir)) {
            return null;
        }

        $input = $dir.DIRECTORY_SEPARATOR.'photo.jp2';
        $output = $dir.DIRECTORY_SEPARATOR.'photo.png';

        try {
            if (file_put_contents($input, $jp2Binary) === false) {
                return null;
            }

            $nullDevice = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
            $command = escapeshellarg($decompress)
                .' -i '.escapeshellarg($input)
                .' -o '.escapeshellarg($output)
                .' '.$nullDevice;

            exec($command, $unused, $exitCode);
            unset($unused);

            if ($exitCode !== 0 || ! is_file($output)) {
                return null;
            }

            $png = file_get_contents($output);
            if ($png === false || $png === '') {
                return null;
            }

            self::writeCache($cacheFile, $png);

            return $png;
        } finally {
            @unlink($input);
            @unlink($output);
            @rmdir($dir);
        }
    }

    private static function resolveBinary(): ?string
    {
        if (self::$binary !== null && self::$binary !== '') {
            return self::$binary;
        }

        $candidates = [];
        $configured = trim((string) config('services.jp2.decompress_bin', ''));
        if ($configured !== '') {
            $candidates[] = $configured;
        }

        if (function_exists('base_path')) {
            $candidates[] = base_path('bin/opj_decompress.exe');
            $candidates[] = base_path('bin/opj_decompress');
        }

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return self::$binary = $path;
            }
        }

        return null;
    }

    private static function cachePath(string $jp2Binary): string
    {
        $dir = self::cacheDir();
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir.DIRECTORY_SEPARATOR.hash('sha256', $jp2Binary).'.png';
    }

    private static function cacheDir(): string
    {
        return storage_path('app/temp/jp2-cache');
    }

    private static function writeCache(string $path, string $png): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($path, $png, LOCK_EX);
    }
}
