<?php

namespace App\Support\DaoTao;

class Jp2PhotoConverter
{
    private static ?bool $available = null;

    public static function isJp2(string $binary): bool
    {
        return str_contains(substr($binary, 0, 32), 'jP  ');
    }

    public static function isAvailable(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        $binary = trim((string) shell_exec('command -v opj_decompress 2>/dev/null'));

        return self::$available = $binary !== '';
    }

    public static function toDataUri(string $jp2Binary): ?string
    {
        if (! self::isAvailable()) {
            return null;
        }

        $cacheFile = self::cachePath($jp2Binary);
        if (is_file($cacheFile)) {
            $cached = file_get_contents($cacheFile);

            return $cached !== false && $cached !== ''
                ? 'data:image/png;base64,'.base64_encode($cached)
                : null;
        }

        $decompress = trim((string) shell_exec('command -v opj_decompress 2>/dev/null'));
        if ($decompress === '') {
            return null;
        }

        $dir = sys_get_temp_dir().'/bang-ten-'.uniqid('', true);
        if (! @mkdir($dir) && ! is_dir($dir)) {
            return null;
        }

        $input = $dir.'/photo.jp2';
        $output = $dir.'/photo.png';

        try {
            if (file_put_contents($input, $jp2Binary) === false) {
                return null;
            }

            $command = escapeshellarg($decompress)
                .' -i '.escapeshellarg($input)
                .' -o '.escapeshellarg($output)
                .' 2>/dev/null';

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

    private static function cachePath(string $jp2Binary): string
    {
        $dir = self::cacheDir();
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir.'/'.hash('sha256', $jp2Binary).'.png';
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
