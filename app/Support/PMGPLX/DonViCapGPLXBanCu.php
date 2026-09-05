<?php

namespace App\Support\PMGPLX;

/**
 * Quy tắc map DonViCapGPLXDaCo sang phần mềm cũ khi đồng bộ học viên.
 */
class DonViCapGPLXBanCu
{
    /** Ngày trích xuất GPLX sau mốc này → đơn vị cấp = 00 trên bản cũ. */
    private const NGAY_TT_CUTOFF = '20250301';

    public static function mapForOldSoftware(?string $donViCap, mixed $ngayTT): string
    {
        if (self::isNgayTTAfterCutoff($ngayTT)) {
            return '00';
        }

        $donViCap = trim((string) $donViCap);
        if ($donViCap === '') {
            return '';
        }

        return substr($donViCap, 0, 2);
    }

    public static function isNgayTTAfterCutoff(mixed $ngayTT): bool
    {
        $normalized = self::normalizeNgayTT($ngayTT);

        return $normalized !== null && $normalized > self::NGAY_TT_CUTOFF;
    }

    public static function formatNgayTT(mixed $ngayTT): string
    {
        $normalized = self::normalizeNgayTT($ngayTT);

        return $normalized !== null ? NgayVn::format($normalized) : trim((string) $ngayTT);
    }

    public static function normalizeNgayTT(mixed $ngayTT): ?string
    {
        if ($ngayTT === null || $ngayTT === '') {
            return null;
        }

        if ($ngayTT instanceof \DateTimeInterface) {
            return $ngayTT->format('Ymd');
        }

        $digits = preg_replace('/\D/', '', (string) $ngayTT) ?? '';

        return strlen($digits) >= 8 ? substr($digits, 0, 8) : null;
    }
}
