<?php

namespace App\Support\DaoTao\LichThucHanh;

use Carbon\Carbon;

final class LichNgay
{
    /**
     * @param  array<string, mixed>  $cauHinh
     */
    public static function laNgayNghi(Carbon $ngay, array $cauHinh, ?string $maGv = null, ?int $capStt = null): bool
    {
        $iso = $ngay->toDateString();

        foreach ($cauHinh['nghi_co_dinh'] ?? [] as $d) {
            if (self::normalizeDate($d) === $iso) {
                return true;
            }
        }

        if ($maGv !== null || $capStt !== null) {
            foreach ($cauHinh['nghi_rieng'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ($maGv !== null && ($row['ma_gv'] ?? '') === $maGv && in_array($iso, self::datesInRow($row), true)) {
                    return true;
                }
                if ($capStt !== null && (int) ($row['cap_stt'] ?? 0) === $capStt && in_array($iso, self::datesInRow($row), true)) {
                    return true;
                }
            }
        }

        $dk = $cauHinh['nghi_dinh_ky'] ?? [];
        if (is_array($dk) && ($dk['thu_trong_tuan'] ?? '') !== '') {
            $thu = (int) $dk['thu_trong_tuan'];
            $tu = self::normalizeDate($dk['tu_ngay'] ?? '');
            $den = self::normalizeDate($dk['den_ngay'] ?? '');
            if ($tu !== '' && $den !== '' && $iso >= $tu && $iso <= $den && $ngay->dayOfWeekIso === $thu) {
                $ngoaiLe = array_map(fn ($d) => self::normalizeDate($d), $dk['ngoai_le_van_day'] ?? []);
                if (! in_array($iso, $ngoaiLe, true)) {
                    foreach ($dk['nghi_bu'] ?? [] as $bu) {
                        if (! is_array($bu)) {
                            continue;
                        }
                        if (self::normalizeDate($bu['tu'] ?? '') === $iso) {
                            return false;
                        }
                        if (self::normalizeDate($bu['den'] ?? '') === $iso) {
                            return true;
                        }
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    public static function datesInRow(array $row): array
    {
        $out = [];
        foreach ($row['ngay'] ?? [] as $d) {
            $n = self::normalizeDate($d);
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return $out;
    }

    public static function normalizeDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    /** Hiển thị ngày cột THỜI GIAN (dd/mm/yyyy) — khớp lưới Excel mẫu. */
    public static function hienThiNgay(string $iso): string
    {
        $n = self::normalizeDate($iso);
        if ($n === '') {
            return '';
        }

        return Carbon::parse($n)->format('d/m/Y');
    }

    public static function thuLabel(Carbon $ngay): string
    {
        return match ($ngay->dayOfWeekIso) {
            1 => 'THỨ 2',
            2 => 'THỨ 3',
            3 => 'THỨ 4',
            4 => 'THỨ 5',
            5 => 'THỨ 6',
            6 => 'THỨ 7',
            7 => 'CHỦ NHẬT',
            default => '',
        };
    }
}
