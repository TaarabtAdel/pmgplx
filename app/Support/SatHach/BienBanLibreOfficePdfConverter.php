<?php

namespace App\Support\SatHach;

use App\Support\EnvPath;
use App\Support\ShellProcess;
use RuntimeException;

class BienBanLibreOfficePdfConverter
{
    private static ?string $binary = null;

    public static function isAvailable(): bool
    {
        return self::resolveBinary() !== null;
    }

    public function convert(string $docxPath, string $outputDir): string
    {
        $pdfs = $this->convertMany([$docxPath], $outputDir);
        if ($pdfs === []) {
            throw new RuntimeException('Không chuyển được file Word sang PDF.');
        }

        return $pdfs[0];
    }

    /**
     * Một lần gọi soffice — nhiều DOCX → nhiều PDF (cùng thư mục out).
     *
     * @param  list<string>  $docxPaths
     * @return list<string>  đường dẫn PDF theo thứ tự input
     */
    public function convertMany(array $docxPaths, string $outputDir): array
    {
        $bin = self::resolveBinary();
        if ($bin === null) {
            throw new RuntimeException('Không tìm thấy LibreOffice (soffice). Cài LibreOffice hoặc đặt LIBREOFFICE_BIN.');
        }

        $files = [];
        foreach ($docxPaths as $path) {
            if (is_string($path) && is_file($path)) {
                $files[] = $path;
            }
        }
        if ($files === []) {
            return [];
        }

        if (! is_dir($outputDir) && ! @mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
            throw new RuntimeException('Không tạo được thư mục PDF tạm.');
        }

        $profileRoot = storage_path('app/temp/lo-profile');
        @mkdir($profileRoot, 0755, true);
        $profile = $profileRoot.DIRECTORY_SEPARATOR.'batch-'.substr(md5(implode("\0", $files).microtime(true)), 0, 14);
        @mkdir($profile, 0755, true);

        $profileUri = 'file:///'.$this->pathToUri($profile);
        $command = [
            $bin,
            '--headless',
            '--norestore',
            '--nologo',
            '--nodefault',
            '-env:UserInstallation='.$profileUri,
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            ...$files,
        ];

        $timeout = min(3600, 90 + count($files) * 40);
        $result = ShellProcess::run($command, dirname($bin), $timeout);
        $this->deleteDirectory($profile);

        $pdfs = [];
        $missing = [];
        foreach ($files as $docx) {
            $expected = $outputDir.DIRECTORY_SEPARATOR.pathinfo($docx, PATHINFO_FILENAME).'.pdf';
            if (is_file($expected)) {
                $pdfs[] = $expected;
            } else {
                $missing[] = basename($docx);
            }
        }

        if ($missing !== []) {
            $detail = $result['output'];
            throw new RuntimeException(
                'LibreOffice không tạo PDF cho: '.implode(', ', $missing)
                .($detail !== '' ? '. '.$detail : '')
            );
        }

        if ($result['code'] !== 0 && $pdfs === []) {
            throw new RuntimeException(
                'Chuyển Word sang PDF thất bại'.($result['output'] !== '' ? ': '.$result['output'] : '.')
            );
        }

        return $pdfs;
    }

    private function pathToUri(string $path): string
    {
        $path = str_replace('\\', '/', realpath($path) ?: $path);

        return str_replace(' ', '%20', $path);
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private static function resolveBinary(): ?string
    {
        if (self::$binary !== null && self::$binary !== '') {
            return self::$binary;
        }

        $candidates = [];
        $configured = EnvPath::fromEnv(config('services.libreoffice.bin'));
        if ($configured !== null) {
            $candidates[] = $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
            $candidates[] = 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe';
        } else {
            $candidates[] = '/usr/bin/soffice';
            $candidates[] = '/usr/bin/libreoffice';
        }

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return self::$binary = $path;
            }
        }

        foreach (PHP_OS_FAMILY === 'Windows' ? ['soffice.exe'] : ['soffice', 'libreoffice'] as $name) {
            $found = self::findOnPath($name);
            if ($found !== null) {
                return self::$binary = $found;
            }
        }

        return null;
    }

    private static function findOnPath(string $name): ?string
    {
        $null = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where '.$name : 'command -v '.$name;
        $out = [];
        $code = 0;
        exec($cmd.' '.$null, $out, $code);
        if ($code === 0 && isset($out[0]) && trim($out[0]) !== '') {
            return trim($out[0]);
        }

        return null;
    }
}
