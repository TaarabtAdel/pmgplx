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
     * Lấy mã ĐK bản cũ từ mã ĐK phần mềm mới — thay tiền tố (vd. 44007 → 45003),
     * phần số sau gộp liền không dấu - (vd. 44007-20260622-090221 → 45003-20260622090221).
     */
    public static function mapFromSource(string $sourceMaDk, string $maKhoaHocDich): string
    {
        $targetPrefix = self::prefixFromMaKhoaHoc($maKhoaHocDich);
        $dashPos = strpos($sourceMaDk, '-');

        if ($dashPos === false) {
            return $targetPrefix;
        }

        $suffix = str_replace('-', '', substr($sourceMaDk, $dashPos + 1));

        return $targetPrefix.'-'.$suffix;
    }
}
