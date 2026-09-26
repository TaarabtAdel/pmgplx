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
        $bin = self::resolveBinary();
        if ($bin === null) {
            throw new RuntimeException('Không tìm thấy LibreOffice (soffice). Cài LibreOffice hoặc đặt LIBREOFFICE_BIN.');
        }

        if (! is_file($docxPath)) {
            throw new RuntimeException('Không tìm thấy file Word: '.$docxPath);
        }

        if (! is_dir($outputDir) && ! @mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
            throw new RuntimeException('Không tạo được thư mục PDF tạm.');
        }

        $profileRoot = storage_path('app/temp/lo-profile');
        @mkdir($profileRoot, 0755, true);
        $profile = $profileRoot.DIRECTORY_SEPARATOR.'run-'.substr(md5($docxPath.microtime(true)), 0, 12);
        @mkdir($profile, 0755, true);

        $profileUri = 'file:///'.$this->pathToUri($profile);
        $command = [
            $bin,
            '--headless',
            '-env:UserInstallation='.$profileUri,
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $docxPath,
        ];
        $result = ShellProcess::run($command, dirname($bin), 900);
        $this->deleteDirectory($profile);

        $expected = $outputDir.DIRECTORY_SEPARATOR.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if ($result['code'] !== 0 || ! is_file($expected)) {
            $detail = $result['output'];
            throw new RuntimeException(
                'Chuyển Word sang PDF thất bại'.($detail !== '' ? ': '.$detail : '.')
                .' (kiểm tra LIBREOFFICE_BIN trong .env — đường dẫn có dấu cách phải đặt trong dấu ngoặc kép, dùng / thay \\).'
            );
        }

        return $expected;
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
