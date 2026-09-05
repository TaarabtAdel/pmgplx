<?php

namespace App\Support\PMGPLX;

/**
 * Quy tắc map DonViCapGPLXDaCo sang phần mềm cũ khi đồng bộ học viên.
 *
 * Nguồn: 2 số đầu của SoGPLXDaCo (vd. 450190001488 → 45, 440155002955 → 44).
 * NgayTTGPLXDaCo sau 01/03/2025 → DonViCapGPLXDaCo = 00.
 */
class DonViCapGPLXBanCu
{
    /** Ngày trích xuất GPLX sau mốc này → đơn vị cấp = 00 trên bản cũ. */
    private const NGAY_TT_CUTOFF = '20250301';

    public static function prefixFromSoGPLX(?string $soGPLX): string
    {
        $soGPLX = trim((string) $soGPLX);

        return $soGPLX !== '' ? substr($soGPLX, 0, 2) : '';
    }

    public static function mapForOldSoftware(?string $soGPLX, mixed $ngayTT): string
    {
        if (self::isNgayTTAfterCutoff($ngayTT)) {
            return '00';
        }

        return self::prefixFromSoGPLX($soGPLX);
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
