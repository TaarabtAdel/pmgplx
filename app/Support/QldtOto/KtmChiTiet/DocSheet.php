<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Đọc ô Excel / quét tiêu đề. */
final class DocSheet
{
    public static function mo(string $path): Worksheet
    {
        return IOFactory::load($path)->getActiveSheet();
    }

    public static function cellStr(Worksheet $sheet, string $coord): string
    {
        $v = $sheet->getCell($coord)->getCalculatedValue();

        return trim((string) ($v ?? ''));
    }

    /** @return list<int> */
    public static function timHangTieuDe(Worksheet $sheet, array $keywords, int $maxRow = 40): ?int
    {
        foreach ($keywords as $kw) {
            $kw = mb_strtolower($kw);
        }
        for ($r = 1; $r <= $maxRow; $r++) {
            $maxCol = min(20, (int) $sheet->getHighestDataColumn($r) ?: 10);
            for ($c = 1; $c <= $maxCol; $c++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $text = mb_strtolower(self::cellStr($sheet, $col.$r));
                if ($text === '') {
                    continue;
                }
                foreach ($keywords as $kw) {
                    if (str_contains($text, $kw)) {
                        return $r;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, int> cột chữ => index 1-based
     */
    public static function mapCotTuHang(Worksheet $sheet, int $headerRow, int $maxCol = 25): array
    {
        $map = [];
        for ($c = 1; $c <= $maxCol; $c++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $label = mb_strtolower(trim(self::cellStr($sheet, $col.$headerRow)));
            if ($label === '') {
                continue;
            }
            $map[$label] = $c;
        }

        return $map;
    }

    public static function normalizeSbd(mixed $sbd): string
    {
        $s = trim((string) $sbd);
        if ($s === '') {
            return '';
        }
        if (ctype_digit($s)) {
            return (string) (int) $s;
        }

        return $s;
    }

    public static function normalizeCccd(string $s): string
    {
        return preg_replace('/\D/', '', trim($s)) ?? '';
    }

    public static function chuanHoaKetQua(string $raw): string
    {
        $t = mb_strtoupper(trim($raw));
        if ($t === '') {
            return '';
        }
        if (str_contains($t, 'KHÔNG') || $t === 'KĐ' || $t === 'TRƯỢT' || $t === 'ROT') {
            return 'KĐ';
        }
        if (str_contains($t, 'ĐẠT') || $t === 'Đ' || $t === 'D') {
            return 'Đ';
        }

        return '';
    }
}