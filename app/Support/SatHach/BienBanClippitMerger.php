<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * Gộp DOCX bằng Clippit (Open XML DocumentBuilder), không cần Microsoft Word.
 */
class BienBanClippitMerger
{
    private static ?string $binary = null;

    public static function isAvailable(): bool
    {
        return self::resolveBinary() !== null;
    }

    /**
     * @param  list<string>  $pageDocxPaths
     */
    public function merge(array $pageDocxPaths, string $outputPath): void
    {
        $bin = self::resolveBinary();
        if ($bin === null) {
            throw new RuntimeException('Không tìm thấy clippit.exe (đặt vào laravel/bin hoặc CLIPPIT_BIN).');
        }

        @mkdir(dirname($outputPath), 0755, true);
        @unlink($outputPath);

        $resolved = [];
        foreach ($pageDocxPaths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $absolute = realpath($path) ?: $path;
            if (! is_file($absolute)) {
                throw new RuntimeException('Không tìm thấy file Word: '.$path);
            }
            $resolved[] = $absolute;
        }

        if (count($resolved) < 2) {
            throw new RuntimeException('Cần ít nhất 2 file Word để gộp.');
        }

        $workDir = dirname($outputPath);
        $manifestPath = $workDir.DIRECTORY_SEPARATOR.'word-build.json';
        $entries = [];
        foreach ($resolved as $absolute) {
            $rel = $this->relativePath($workDir, $absolute);
            $entries[] = ['file' => str_replace('\\', '/', $rel)];
        }

        $manifest = [
            '$schema' => 'https://sergey-tihon.github.io/Clippit/schemas/word-build-manifest.v1.json',
            'output' => basename($outputPath),
            'entries' => $entries,
        ];

        $json = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (! is_string($json) || file_put_contents($manifestPath, $json) === false) {
            throw new RuntimeException('Không ghi được manifest Clippit.');
        }

        $cmd = escapeshellarg($bin)
            .' word build run '.escapeshellarg($manifestPath)
            .' --force';

        $output = [];
        $code = 0;
        exec($cmd.' 2>&1', $output, $code);

        @unlink($manifestPath);

        if ($code !== 0 || ! is_file($outputPath)) {
            $detail = Utf8::sanitize(implode(' ', $output));
            throw new RuntimeException(
                'Clippit gộp Word thất bại'.($detail !== '' ? ': '.$detail : '.')
            );
        }
    }

    private function relativePath(string $baseDir, string $absolutePath): string
    {
        $base = realpath($baseDir) ?: $baseDir;
        $target = realpath($absolutePath) ?: $absolutePath;
        $base = rtrim(str_replace('\\', '/', $base), '/');
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, $base.'/')) {
            return substr($target, strlen($base) + 1);
        }

        return $target;
    }

    private static function resolveBinary(): ?string
    {
        if (self::$binary !== null && self::$binary !== '') {
            return self::$binary;
        }

        $candidates = [];
        $configured = trim((string) config('services.clippit.bin', ''));
        if ($configured !== '') {
            $candidates[] = $configured;
        }

        if (function_exists('base_path')) {
            try {
                $candidates[] = base_path('bin/clippit.exe');
                $candidates[] = base_path('bin/clippit');
            } catch (\Throwable) {
            }
        }

        $candidates[] = dirname(__DIR__, 3).'/bin/clippit.exe';

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return self::$binary = $path;
            }
        }

        return null;
    }
}
