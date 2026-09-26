<?php

namespace App\Support\SatHach;

use RuntimeException;
use ZipArchive;

class BienBanTongHopZip
{
    public function createFromDirectory(string $sourceDir, string $zipPath): void
    {
        if (! is_dir($sourceDir)) {
            throw new RuntimeException('Không có thư mục file Word để nén.');
        }

        @mkdir(dirname($zipPath), 0755, true);
        @unlink($zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file ZIP.');
        }

        $count = 0;
        foreach (scandir($sourceDir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $sourceDir.DIRECTORY_SEPARATOR.$name;
            if (! is_file($path) || ! str_ends_with(strtolower($name), '.docx')) {
                continue;
            }
            $zip->addFile($path, $name);
            $count++;
        }

        $zip->close();

        if ($count === 0 || ! is_file($zipPath)) {
            throw new RuntimeException('Không có file Word trong thư mục từng biên bản.');
        }
    }
}
