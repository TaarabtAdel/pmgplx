<?php

namespace App\Support;

use Collator;
use Illuminate\Support\Str;

class HoTenVietNam
{
    /**
     * Lấy tên gọi (từ cuối) để sắp xếp kiểu danh sách Việt Nam.
     * VD: "Trương Văn An" → "An", "Hồ Văn Bôn" → "Bôn".
     */
    public static function givenName(string $hoTen): string
    {
        $hoTen = preg_replace('/\s+/u', ' ', trim($hoTen)) ?? trim($hoTen);
        if ($hoTen === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $hoTen, -1, PREG_SPLIT_NO_EMPTY);

        return $parts === false || $parts === [] ? $hoTen : (string) end($parts);
    }

    public static function compare(string $a, string $b): int
    {
        $cmp = self::compareStrings(self::givenName($a), self::givenName($b));
        if ($cmp !== 0) {
            return $cmp;
        }

        return self::compareStrings($a, $b);
    }

    private static function compareStrings(string $a, string $b): int
    {
        if (class_exists(Collator::class)) {
            static $collator = new Collator('vi_VN');

            return $collator->compare($a, $b);
        }

        return strcmp(self::fold($a), self::fold($b));
    }

    private static function fold(string $value): string
    {
        return Str::ascii(mb_strtolower($value, 'UTF-8'));
    }
}
