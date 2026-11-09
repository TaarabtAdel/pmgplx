<?php

namespace App\Support\PMGPLX;

/**
 * Số giấy chứng nhận tạm (NguoiLX_HoSo.SoGiayCNTN) — bản mới dài hơn cột bản cũ (20 ký tự).
 * Bản cũ thường dạng B-00|{stt} (lấy từ 6 số thứ tự trong mã ĐK).
 */
class SoGiayCntnBanCu
{
    public static function mapForOldSoftware(?string $sourceSoGiay, string $targetMaDk): ?string
    {
        $sourceSoGiay = trim((string) $sourceSoGiay);
        if ($sourceSoGiay === '') {
            return $sourceSoGiay;
        }

        if (strlen($sourceSoGiay) <= 20 && ! preg_match('/^\d{5}-/', $sourceSoGiay)) {
            return $sourceSoGiay;
        }

        $seq = self::sequenceFromMaDk($targetMaDk);
        if ($seq !== null) {
            return 'B-00|'.$seq;
        }

        if (preg_match('/-(\d{6})-[^-]+$/', $sourceSoGiay, $m)) {
            return 'B-00|'.((int) $m[1]);
        }

        return strlen($sourceSoGiay) <= 20 ? $sourceSoGiay : null;
    }

    private static function sequenceFromMaDk(string $maDk): ?int
    {
        if (preg_match('/-(\d{6})(?:-|$)/', $maDk, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
