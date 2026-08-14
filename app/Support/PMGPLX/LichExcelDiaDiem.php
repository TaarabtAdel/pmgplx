<?php

namespace App\Support\PMGPLX;

class LichExcelDiaDiem
{
    public const SAN_TAP = 'Sân tập lái và sân sát hạch';

    public const TRUNG_TAM = 'Trung tâm';

    public const TUYEN_DUONG = 'Các tuyến đường giao thông';

    /** @return list<string> */
    public static function options(): array
    {
        return [
            self::SAN_TAP,
            self::TRUNG_TAM,
            self::TUYEN_DUONG,
            '',
        ];
    }

    public static function resolve(string $noiDung, string $chiTiet = ''): string
    {
        $text = self::unaccent(mb_strtoupper(trim($noiDung.' '.$chiTiet)));

        if ($text === '') {
            return '';
        }

        if (str_contains($text, 'ON LUYEN STL')) {
            return self::SAN_TAP;
        }

        if (str_contains($text, 'ON LUYEN TD')) {
            return self::TUYEN_DUONG;
        }

        // Hình / Ôn luyện STL
        if (
            preg_match('/\bHINH\b/u', $text)
            || str_contains($text, 'ON LUYEN STL')
            || str_contains($text, 'ON LUYEN')
        ) {
            return self::SAN_TAP;
        }

        if (str_contains($text, 'CABIN')) {
            return self::TRUNG_TAM;
        }

        // "Tự động 1" — để trống (user chưa chỉ định địa điểm)
        if (preg_match('/\bTU DONG\s*1\b/u', $text)) {
            return self::TUYEN_DUONG;
        }

        return self::TUYEN_DUONG;
    }

    /** Bỏ qua lưu lịch xe khi nội dung–chi tiết bắt đầu bằng CABIN */
    public static function isCabinSkip(string $noiDung, string $chiTiet = ''): bool
    {
        $noiDung = trim($noiDung);
        $chiTiet = trim($chiTiet);

        if ($noiDung === '' && $chiTiet === '') {
            return false;
        }

        $text = $noiDung;
        if ($chiTiet !== '') {
            $text = $noiDung !== '' ? $noiDung.'-'.$chiTiet : $chiTiet;
        }

        return str_starts_with(self::unaccent(mb_strtoupper($text)), 'CABIN');
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
