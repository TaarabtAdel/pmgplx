<?php

namespace App\Support\PMGPLX;

class MaDkBanCu
{
    /**
     * Tiền tố mã ĐK (5 ký tự đầu mã khóa học).
     */
    public static function prefixFromMaKhoaHoc(string $maKhoaHoc): string
    {
        return substr(trim($maKhoaHoc), 0, 5);
    }

    /**
     * Lấy mã ĐK bản cũ từ mã ĐK phần mềm mới — chỉ thay tiền tố mã khóa (vd. 44007 → 45003),
     * giữ nguyên phần sau dấu - đầu (vd. 44007-20260831-000002 → 45003-20260831-000002).
     */
    public static function mapFromSource(string $sourceMaDk, string $maKhoaHocDich): string
    {
        $targetPrefix = self::prefixFromMaKhoaHoc($maKhoaHocDich);
        $dashPos = strpos($sourceMaDk, '-');

        if ($dashPos === false) {
            return $targetPrefix;
        }

        $suffix = substr($sourceMaDk, $dashPos + 1);

        return $targetPrefix.'-'.$suffix;
    }
}
