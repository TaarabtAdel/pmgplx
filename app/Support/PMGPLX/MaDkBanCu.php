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

    /**
     * Mã khoá PMGPLX bản cũ tương ứng khoá PM mới (cùng TenKH, hoặc đổi 5 ký tự đầu MaKH).
     */
    public static function resolveMaKhoaHocBanCu(string $maKhMoi): ?string
    {
        $maKhMoi = trim($maKhMoi);
        if ($maKhMoi === '') {
            return null;
        }

        $tenKh = \App\Models\PMGPLX\KhoaHoc::query()->where('MaKH', $maKhMoi)->value('TenKH');
        $tenKh = trim((string) ($tenKh ?? ''));
        if ($tenKh !== '') {
            $maCu = \App\Models\PMGPLXOLD\KhoaHoc::query()->where('TenKH', $tenKh)->value('MaKH');
            if (is_string($maCu) && trim($maCu) !== '') {
                return trim($maCu);
            }
        }

        $suffix = strlen($maKhMoi) > 5 ? substr($maKhMoi, 5) : '';
        foreach (self::tienToMaKhoaBanCu() as $prefix) {
            $candidate = $prefix.$suffix;
            if ($suffix !== '' && \App\Models\PMGPLXOLD\KhoaHoc::query()->where('MaKH', $candidate)->exists()) {
                return $candidate;
            }
        }

        $prefix = self::tienToMaKhoaBanCu()[0] ?? null;
        if ($prefix !== null && $suffix !== '') {
            return $prefix.$suffix;
        }

        return null;
    }

    /** @return list<string> */
    public static function tienToMaKhoaBanCu(): array
    {
        $fromEnv = trim((string) config('pmgplx.ban_cu.ma_csdt_prefix', '45003'));

        return $fromEnv !== '' ? [$fromEnv] : ['45003'];
    }
}
