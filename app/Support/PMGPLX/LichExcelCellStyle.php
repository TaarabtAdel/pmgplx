<?php

namespace App\Support\PMGPLX;

class LichExcelCellStyle
{
    public static function classFor(?string $text): string
    {
        $raw = trim((string) $text);
        if ($raw === '') {
            return '';
        }

        $t = mb_strtoupper($raw);
        // Bỏ dấu để match ổn định hơn
        $plain = self::unaccent($t);

        if (str_contains($plain, 'PHUC TAP')) {
            return 'cell-phuc-tap';
        }
        if (str_contains($plain, 'BAN DEM')) {
            return 'cell-ban-dem';
        }
        if (str_contains($plain, 'CO TAI')) {
            return 'cell-co-tai';
        }
        if (str_contains($plain, 'DOC') && str_contains($plain, 'QC')) {
            return 'cell-doc-qc';
        }
        if (str_contains($plain, 'TU DONG')) {
            return 'cell-tu-dong';
        }
        if (str_contains($plain, 'CAO TOC')) {
            return 'cell-cao-toc';
        }

        return '';
    }

    /** Cột Nội dung có chứa "Tự động" (không phân biệt dấu/hoa thường). */
    public static function containsTuDong(?string $text): bool
    {
        $raw = trim((string) $text);
        if ($raw === '') {
            return false;
        }

        $plain = self::unaccent(mb_strtoupper($raw));

        return str_contains($plain, 'TU DONG');
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
