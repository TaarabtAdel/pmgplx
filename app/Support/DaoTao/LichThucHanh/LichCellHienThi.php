<?php

namespace App\Support\DaoTao\LichThucHanh;

/** Nhãn cột phụ (vd. Ban đêm) trên lưới xem trước / Excel. */
final class LichCellHienThi
{
    /** @param array<string, mixed> $cell */
    public static function noiDungPhu(array $cell): string
    {
        return self::resolve(
            (string) ($cell['bat_dau'] ?? ''),
            (string) ($cell['ket_thuc'] ?? ''),
            (string) ($cell['noi_dung_phu'] ?? ''),
        );
    }

    public static function resolve(string $batDau, string $ketThuc, string $existing = ''): string
    {
        if (self::coKhoang20H01Den22H01($batDau.' '.$ketThuc)) {
            return '';
        }

        $existing = trim($existing);
        if ($existing !== '') {
            return self::isBanDemText($existing) ? 'Ban đêm' : $existing;
        }
        if (self::coKhoang18Den22($ketThuc) || self::coKhoang18Den22($batDau)) {
            return 'Ban đêm';
        }

        return '';
    }

    /** Ca tối 20H01–22H01 (split sáng+tối) — không ghi nhãn Ban đêm. */
    private static function coKhoang20H01Den22H01(string $raw): bool
    {
        $s = strtoupper(str_replace(["\u{2019}", "'", ' '], '', $raw));

        return (bool) preg_match('/20H01.*22H01/u', $s);
    }

    private static function coKhoang18Den22(string $raw): bool
    {
        if (self::coKhoang20H01Den22H01($raw)) {
            return false;
        }
        $s = strtoupper(str_replace(["\u{2019}", "'", ' '], '', $raw));

        return (bool) preg_match('/18H.*22H/u', $s);
    }

    private static function isBanDemText(string $text): bool
    {
        $u = mb_strtoupper($text);

        return str_contains($u, 'BAN') && (str_contains($u, 'ĐÊM') || str_contains($u, 'DEM'));
    }
}
