<?php

namespace App\Support\DaoTao;

/**
 * Đối chiếu hạng file sát hạch với PMGPLX (HangGPLX ↔ HangDaoTao):
 *
 * | HangGPLX | HangDaoTao |
 * | B1       | B          |
 * | B11      | B.01       |
 *
 * File sát hạch thường ghi B (sàn) hoặc B01 (tự động).
 */
class HangSatHachMatch
{
    /** @var list<list<string>> */
    private const ALIAS_GROUPS = [
        ['B', 'B1'],
        ['B01', 'B11'],
    ];

    /**
     * Chuẩn hoá hạng: bỏ dấu chấm, khoảng trắng, gạch; in hoa.
     */
    public static function normalize(?string $hang): string
    {
        $v = mb_strtoupper(trim((string) $hang));
        $v = str_replace(['.', ' ', '-', '_'], '', $v);

        return $v;
    }

    /**
     * @return list<string>
     */
    public static function equivalenceKeys(?string $hang): array
    {
        $n = self::normalize($hang);
        if ($n === '') {
            return [];
        }

        $keys = [$n];
        foreach (self::ALIAS_GROUPS as $group) {
            if (in_array($n, $group, true)) {
                $keys = array_merge($keys, $group);
            }
        }

        return array_values(array_unique($keys));
    }

    public static function matches(?string $fileHang, ?string $hangGplx, ?string $hangDaoTao): bool
    {
        $fileKeys = self::equivalenceKeys($fileHang);
        if ($fileKeys === []) {
            return false;
        }

        $dbKeys = array_merge(
            self::equivalenceKeys($hangGplx),
            self::equivalenceKeys($hangDaoTao)
        );

        return count(array_intersect($fileKeys, $dbKeys)) > 0;
    }

    /** Nhãn gợi ý trên màn xem trước (file sát hạch ↔ HangGPLX). */
    public static function labelForFileHang(?string $fileHang): string
    {
        $n = self::normalize($fileHang);
        if (in_array($n, ['B', 'B1'], true)) {
            return 'B ↔ HangGPLX B1 (Hang ĐT B)';
        }
        if (in_array($n, ['B01', 'B11'], true)) {
            return 'B01 ↔ HangGPLX B11 (Hang ĐT B.01)';
        }

        return trim((string) $fileHang);
    }
}
