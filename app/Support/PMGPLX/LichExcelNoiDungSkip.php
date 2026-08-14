<?php

namespace App\Support\PMGPLX;

class LichExcelNoiDungSkip
{
    public static function format(string $noiDung, string $chiTiet = ''): string
    {
        $noiDung = trim($noiDung);
        $chiTiet = trim($chiTiet);

        if ($noiDung === '' && $chiTiet === '') {
            return '—';
        }

        if ($chiTiet === '') {
            return $noiDung;
        }

        if ($noiDung === '') {
            return $chiTiet;
        }

        return $noiDung.'-'.$chiTiet;
    }

    public static function isBoSung(string $noiDung, string $chiTiet = ''): bool
    {
        if (self::textContainsBoSung($noiDung) || self::textContainsBoSung($chiTiet)) {
            return true;
        }

        $combined = self::format($noiDung, $chiTiet);

        return $combined !== '—' && self::textContainsBoSung($combined);
    }

    public static function isGvSkip(string $noiDung, string $chiTiet = ''): bool
    {
        return self::isBoSung($noiDung, $chiTiet);
    }

    public static function isXeSkip(string $noiDung, string $chiTiet = ''): bool
    {
        return LichExcelDiaDiem::isCabinSkip($noiDung, $chiTiet)
            || self::isBoSung($noiDung, $chiTiet);
    }

    public static function gvSkipLabel(): string
    {
        return 'Bỏ qua';
    }

    public static function xeSkipLabel(string $noiDung, string $chiTiet = ''): string
    {
        return 'Bỏ qua';
    }

    private static function textContainsBoSung(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        $normalized = self::normalize($text);

        if ($normalized === 'BO SUNG') {
            return true;
        }

        if (preg_match('/(?:^|[-\s])BO SUNG(?:$|[-\s])/u', $normalized)) {
            return true;
        }

        return str_contains($normalized, 'BO SUNG');
    }

    private static function normalize(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', mb_strtoupper(trim($text)));
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
