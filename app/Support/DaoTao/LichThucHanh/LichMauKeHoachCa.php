<?php

namespace App\Support\DaoTao\LichThucHanh;

/**
 * Kế hoạch mẫu theo ca (sáng/chiều) cho hạng B — dùng khi chưa import ke_hoach_theo_gv từ Excel.
 * Dữ liệu gốc trích từ một file Excel tham chiếu; áp dụng cho mọi dự án hạng B cùng lịch biểu.
 */
final class LichMauKeHoachCa
{
    public const ANCHOR = '2026-08-22';

    /** @var array<string, array<string, mixed>> */
    private static array $payloadByCa = [];

    /**
     * @return array<string, array<string, array<string, mixed>>> cap_stt => iso => slot
     */
    public static function slotsByCapFromAnchor(string $ca): array
    {
        $payload = self::load($ca);
        /** @var array<string, array<string, array<string, mixed>>> $ke */
        $ke = $payload['ke_hoach'] ?? [];

        return $ke;
    }

    public static function anchor(string $ca): string
    {
        $payload = self::load($ca);

        return (string) ($payload['anchor'] ?? self::ANCHOR);
    }

    /** @return array<string, mixed> */
    private static function load(string $ca): array
    {
        if (isset(self::$payloadByCa[$ca])) {
            return self::$payloadByCa[$ca];
        }

        $paths = [
            __DIR__."/Data/mau_hang_b_{$ca}_ke_hoach.json",
            __DIR__."/Data/bk54_{$ca}_ke_hoach.json",
        ];
        foreach ($paths as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                self::$payloadByCa[$ca] = $decoded;

                return self::$payloadByCa[$ca];
            }
        }

        self::$payloadByCa[$ca] = ['anchor' => self::ANCHOR, 'ke_hoach' => []];

        return self::$payloadByCa[$ca];
    }
}
