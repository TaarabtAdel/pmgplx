<?php

namespace App\Support\PMGPLX;

class LichExcelBienSo
{
    /**
     * Chuẩn hóa biển số Excel → dạng XeTap: 74A-452.04 → 74A45204
     */
    public static function normalize(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        return strtoupper((string) preg_replace('/[\s\-\.]/', '', $raw));
    }

    /**
     * Nếu nội dung có "TỰ ĐỘNG 74A-452.04" → trả về biển số đã chuẩn hóa.
     */
    public static function extractFromTuDong(string $noiDung, string $chiTiet = ''): ?string
    {
        $text = trim($noiDung.' '.$chiTiet);
        if ($text === '') {
            return null;
        }

        $plain = self::unaccent(mb_strtoupper($text));
        if (! str_contains($plain, 'TU DONG')) {
            return null;
        }

        if (preg_match('/(\d{2}\s*[A-Z]\s*-?\s*\d+(?:\.\d+)?)/iu', $text, $m)) {
            return self::normalize($m[1]);
        }

        return null;
    }

    private static function unaccent(string $text): string
    {
        $map = [
            'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
            'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
            'Đ' => 'D',
        ];

        return strtr($text, $map);
    }
}
