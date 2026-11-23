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

    /** LT 30 câu: số câu trả lời đúng ≥ 26 là Đạt. */
    public static function ketQuaTuSoCauDungLt(mixed $soCau, int $nguong = 26): string
    {
        $s = str_replace(',', '.', trim((string) $soCau));
        if ($s === '' || ! is_numeric($s)) {
            return '';
        }

        return (int) round((float) $s) >= $nguong ? 'Đ' : 'KĐ';
    }

    /**
     * Tìm CCCD/CMT trong các cột lân cận khi cột «Số CMT» trống (file LT thi lại hay lệch cột).
     */
    public static function timCccdTrongHang(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        int $colDau,
        int $colCuoi,
    ): string {
        $colDau = max(1, $colDau);
        $colCuoi = max($colDau, $colCuoi);
        for ($c = $colDau; $c <= $colCuoi; $c++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $digits = self::normalizeCccd(self::cellStr($sheet, $letter.$row));
            if ($digits !== '' && strlen($digits) >= 9) {
                return $digits;
            }
        }

        return '';
    }

    /** Ngưỡng điểm CC cabin: >= 5 là Đạt. */
    public static function ketQuaTuDiemCc(mixed $raw, float $nguong = 5.0): string
    {
        $s = str_replace(',', '.', trim((string) $raw));
        if ($s === '' || ! is_numeric($s)) {
            return self::chuanHoaKetQua((string) $raw);
        }

        return (float) $s >= $nguong ? 'Đ' : 'KĐ';
    }

    public static function chuanNgaySinh(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $raw, $m)) {
            return sprintf('%02d/%02d/%04d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return $raw;
    }

    public static function giongNgaySinh(string $raw): bool
    {
        $raw = trim($raw);

        return $raw !== '' && (bool) preg_match('/^\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4}$/', $raw);
    }

    public static function khoaHoTenNgaySinh(string $hoTen, string $ngaySinh): string
    {
        $ten = mb_strtoupper(trim($hoTen));
        $ns = self::chuanNgaySinh($ngaySinh);
        if ($ten === '' || $ns === '') {
            return '';
        }

        return $ten.'|'.$ns;
    }
}