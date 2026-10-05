<?php

namespace App\Support\DaoTao\LichThucHanh;

final class MauCa
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            '8' => self::khuon8h(),
            '9' => self::khuon9h(),
            '10' => self::khuon10h(),
        ];
    }

    /** @return array<string, mixed> */
    public static function khuon8h(): array
    {
        return [
            'hinh' => ['sang' => ['05:59', '13:59'], 'chieu' => ['14:00', '22:00']],
            'cabin' => ['ca' => ['07:00', '17:00']],
            'tu_dong' => ['sang' => ['07:00', '12:00'], 'chieu' => ['13:00', '18:00']],
            'phuc_tap_giai_doan_dau' => [
                'sang' => ['05:58', '13:58'],
                'chieu' => ['13:59', '17:59', '18:00', '22:00'],
            ],
            'phuc_tap_sau' => [
                'sang' => ['05:59', '11:59', '20:01', '22:01'],
                'chieu' => ['12:00', '20:00'],
            ],
            'phuc_tap_2' => ['sang' => ['05:59', '11:59'], 'chieu' => ['12:00', '18:00']],
        ];
    }

    /** @return array<string, mixed> */
    public static function khuon9h(): array
    {
        return [
            'hinh' => ['sang' => ['05:59', '14:59'], 'chieu' => ['15:00', '22:00']],
            'cabin' => ['ca' => ['07:00', '17:00']],
            'tu_dong' => ['sang' => ['07:00', '12:00'], 'chieu' => ['13:00', '18:00']],
            'phuc_tap_giai_doan_dau' => [
                'sang' => ['05:58', '14:58'],
                'chieu' => ['14:59', '22:00'],
            ],
            'phuc_tap_sau' => [
                'sang' => ['05:59', '12:59', '20:01', '22:01'],
                'chieu' => ['13:00', '20:00'],
            ],
            'phuc_tap_2' => ['sang' => ['05:59', '11:59'], 'chieu' => ['12:00', '18:00']],
        ];
    }

    /** @return array<string, mixed> */
    public static function khuon10h(): array
    {
        return [
            'hinh' => ['sang' => ['05:59', '15:59'], 'chieu' => ['16:00', '22:00']],
            'cabin' => ['ca' => ['07:00', '17:00']],
            'tu_dong' => ['sang' => ['07:00', '12:00'], 'chieu' => ['13:00', '18:00']],
            'phuc_tap_giai_doan_dau' => [
                'sang' => ['05:58', '15:58'],
                'chieu' => ['15:59', '22:00'],
            ],
            'phuc_tap_sau' => [
                'sang' => ['05:59', '13:59', '20:01', '22:01'],
                'chieu' => ['14:00', '20:00'],
            ],
            'phuc_tap_2' => ['sang' => ['05:59', '11:59'], 'chieu' => ['12:00', '18:00']],
        ];
    }

    public static function formatGioExcel(string $time): string
    {
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        if ($m === 0) {
            return $h.'H';
        }

        return $h.'H'.str_pad((string) $m, 2, '0', STR_PAD_LEFT)."'";
    }

    public static function soGioTuKhoang(array $khoang): float
    {
        if (count($khoang) < 2) {
            return 0.0;
        }
        $start = self::minutes($khoang[0]);
        $end = self::minutes($khoang[count($khoang) - 1]);
        if ($end <= $start) {
            return 0.0;
        }

        return round(($end - $start) / 60, 2);
    }

    private static function minutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }
}
