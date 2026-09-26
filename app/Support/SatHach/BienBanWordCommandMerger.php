<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * Gộp DOCX bằng Microsoft Word trên Windows (PowerShell COM InsertFile).
 * Mac/Docker không gọi Word — dùng gộp XML.
 */
class BienBanWordCommandMerger
{
    public function isAvailable(): bool
    {
        return $this->driver() !== null;
    }

    /**
     * @param  list<string>  $pageDocxPaths
     */
    public function merge(array $pageDocxPaths, string $outputPath): void
    {
        $driver = $this->driver();
        if ($driver === null) {
            throw new RuntimeException('Máy này không có Microsoft Word để gộp file.');
        }

        @mkdir(dirname($outputPath), 0755, true);
        @unlink($outputPath);

        if ($driver !== 'windows') {
            throw new RuntimeException('Máy này không có Microsoft Word để gộp file.');
        }

        $this->mergeWindows($pageDocxPaths, $outputPath);
    }

    private function driver(): ?string
    {
        if (filter_var(config('services.word_merge.disable', false), FILTER_VALIDATE_BOOL)) {
            return null;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->powershellPath() !== null ? 'windows' : null;
        }

        return null;
    }

    /**
     * @param  list<string>  $files
     */
    private function mergeWindows(array $files, string $outputPath): void
    {
        $ps1 = base_path('bin/merge-docx.ps1');
        if (! is_file($ps1)) {
            throw new RuntimeException('Thiếu script gộp Word: '.$ps1);
        }

        $list = dirname($outputPath).DIRECTORY_SEPARATOR.'word-merge-files.txt';
        file_put_contents($list, "\xEF\xBB\xBF".implode("\r\n", $files)."\r\n");

        $cmd = escapeshellarg((string) $this->powershellPath())
            .' -NoProfile -ExecutionPolicy Bypass -File '.escapeshellarg($ps1)
            .' '.escapeshellarg($list)
            .' '.escapeshellarg($outputPath);

        $output = [];
        $code = 0;
        exec($cmd.' 2>&1', $output, $code);
        @unlink($list);

        if ($code !== 0 || ! is_file($outputPath)) {
            $detail = Utf8::sanitize(implode(' ', $output));
            throw new RuntimeException(
                'Gộp bằng Microsoft Word thất bại'.($detail !== '' ? ': '.$detail : '.')
            );
        }
    }

    private function powershellPath(): ?string
    {
        $configured = trim((string) config('services.word_merge.powershell', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        foreach (['powershell.exe', 'pwsh.exe'] as $bin) {
            $out = [];
            $code = 0;
            exec('where '.$bin.' 2>nul', $out, $code);
            if ($code === 0 && isset($out[0]) && trim($out[0]) !== '') {
                return trim($out[0]);
            }
        }

        $systemRoot = getenv('SystemRoot') ?: 'C:\\Windows';
        $fallback = $systemRoot.'\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';

        return is_file($fallback) ? $fallback : null;
    }
}
