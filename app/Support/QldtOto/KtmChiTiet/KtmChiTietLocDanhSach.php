<?php

namespace App\Support\QldtOto\KtmChiTiet;

/** Lọc thí sinh cho các danh sách xuất (vắng / đạt / không đạt). */
final class KtmChiTietLocDanhSach
{
    /** @param  array<string, mixed>  $ts */
    public static function laVang(array $ts): bool
    {
        foreach (['lt', 'mp', 'h', 'cc', 'duong'] as $field) {
            $v = (string) ($ts[$field] ?? '');
            if ($v !== '' && str_starts_with($v, 'V')) {
                return true;
            }
        }

        return ($ts['tongHop'] ?? '') === '-';
    }

    /**
     * @param  list<array<string, mixed>>  $thiSinh
     * @return list<array<string, mixed>>
     */
    public static function locDat(array $thiSinh): array
    {
        return array_values(array_filter($thiSinh, fn (array $ts) => ($ts['tongHop'] ?? '') === 'Đ'));
    }

    /**
     * @param  list<array<string, mixed>>  $thiSinh
     * @return list<array<string, mixed>>
     */
    public static function locKhongDat(array $thiSinh): array
    {
        return array_values(array_filter(
            $thiSinh,
            fn (array $ts) => ($ts['tongHop'] ?? '') === 'KĐ' && ! self::laVang($ts),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $thiSinh
     * @return list<array<string, mixed>>
     */
    public static function locVang(array $thiSinh): array
    {
        return array_values(array_filter($thiSinh, fn (array $ts) => self::laVang($ts)));
    }
}