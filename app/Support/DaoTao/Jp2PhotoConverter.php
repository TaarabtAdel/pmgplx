<?php

namespace App\Support\DaoTao;

class Jp2PhotoConverter
{
    private static ?bool $available = null;

    private static ?string $binary = null;

    public static function isJp2(string $binary): bool
    {
        return str_contains(substr($binary, 0, 32), 'jP  ');
    }

    public static function isAvailable(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        return self::$available = self::resolveBinary() !== null;
    }

    public static function toDataUri(string $jp2Binary): ?string
    {
        $decompress = self::resolveBinary();
        if ($decompress === null) {
            return null;
        }

        $cacheFile = self::cachePath($jp2Binary);
        if (is_file($cacheFile)) {
            $cached = file_get_contents($cacheFile);

            return $cached !== false && $cached !== ''
                ? 'data:image/png;base64,'.base64_encode($cached)
                : null;
        }

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bang-ten-'.uniqid('', true);
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

            return 'data:image/png;base64,'.base64_encode($png);
        } finally {
            @unlink($input);
            @unlink($output);
            @rmdir($dir);
        }
    }

    /**
     * Tìm opj_decompress theo thứ tự:
     * 1. JP2_DECOMPRESS_BIN (.env)
     * 2. bin/opj_decompress.exe (Windows) hoặc bin/opj_decompress (Linux)
     * 3. PATH hệ thống (where / command -v)
     */
    private static function resolveBinary(): ?string
    {
        if (self::$binary !== null) {
            return self::$binary !== '' ? self::$binary : null;
        }

        $candidates = [];

        $configured = trim((string) config('services.jp2.decompress_bin', ''));
        if ($configured !== '') {
            $candidates[] = $configured;
        }

        if (function_exists('base_path')) {
            try {
                if (PHP_OS_FAMILY === 'Windows') {
                    $candidates[] = base_path('bin/opj_decompress.exe');
                }
                $candidates[] = base_path('bin/opj_decompress');
            } catch (\Throwable) {
                // CLI chưa bootstrap Laravel
            }
        }

        $candidates[] = dirname(__DIR__, 3).'/bin/opj_decompress.exe';
        $candidates[] = dirname(__DIR__, 3).'/bin/opj_decompress';

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return self::$binary = $path;
            }
        }

        $fromPath = self::findOnSystemPath();
        if ($fromPath !== null) {
            return self::$binary = $fromPath;
        }

        self::$binary = '';

        return null;
    }

    private static function findOnSystemPath(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('where opj_decompress 2>nul');
            if (! is_string($output) || trim($output) === '') {
                return null;
            }

            foreach (preg_split('/\R/u', trim($output)) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '' && is_file($line)) {
                    return $line;
                }
            }

            return null;
        }

        $output = shell_exec('command -v opj_decompress 2>/dev/null');
        $path = is_string($output) ? trim($output) : '';

        return $path !== '' && is_executable($path) ? $path : null;
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
        try {
            if (function_exists('storage_path') && app()->bound('path.storage')) {
                return storage_path('app/temp/jp2-cache');
            }
        } catch (\Throwable) {
            // CLI / chưa bootstrap Laravel
        }

        return dirname(__DIR__, 3).'/storage/app/temp/jp2-cache';
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
