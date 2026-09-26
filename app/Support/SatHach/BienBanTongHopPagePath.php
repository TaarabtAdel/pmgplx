<?php

namespace App\Support\SatHach;

use App\Models\DaoTao\SatHachBienBan;

final class BienBanTongHopPagePath
{
    public static function pagesDir(string $jobDir): string
    {
        $dir = $jobDir.DIRECTORY_SEPARATOR.'tung-file';
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Không tạo được thư mục từng biên bản.');
        }

        return $dir;
    }

    public static function docxPath(string $jobDir, int $ordinal, SatHachBienBan $row): string
    {
        $pagesDir = self::pagesDir($jobDir);
        $sbd = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($row->SoBaoDanh ?: 'sbd')) ?: 'sbd';
        $ten = Utf8::sanitize((string) ($row->HoVaTen ?: ''));
        $ten = trim(preg_replace('/[\s\\\\\\/:*?"<>|]+/u', '-', $ten) ?? '') ?: ('hv'.$row->Id);
        $ten = mb_substr($ten, 0, 48);
        $name = sprintf('%04d-%s-%s.docx', $ordinal, $sbd, $ten);

        return $pagesDir.DIRECTORY_SEPARATOR.$name;
    }
}
