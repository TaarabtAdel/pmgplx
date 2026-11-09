<?php

namespace App\Support\PMGPLX;

/**
 * Mã đơn vị GTVT trên bản cũ (DM_DonViGTVT) theo khóa đích — không copy mã CSDT phần mềm mới (vd. 44016).
 */
class DonViGtvtBanCu
{
    /**
     * @param  array<string, mixed>  $nguoiPayload
     * @param  array<string, mixed>|null  $hoSoPayload
     * @param  object{MaCSDT?: mixed, MaSoGTVT?: mixed}|null  $khoaHocBanCu
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public static function applyFromKhoaHocDich(
        array $nguoiPayload,
        ?array $hoSoPayload,
        ?object $khoaHocBanCu
    ): array {
        if ($khoaHocBanCu === null) {
            return [$nguoiPayload, $hoSoPayload];
        }

        $maCsdt = trim((string) ($khoaHocBanCu->MaCSDT ?? ''));
        $maSoGtvt = trim((string) ($khoaHocBanCu->MaSoGTVT ?? ''));

        if ($maCsdt !== '' && array_key_exists('DonViNhanHSo', $nguoiPayload)) {
            $nguoiPayload['DonViNhanHSo'] = $maCsdt;
        }

        if ($hoSoPayload !== null) {
            if ($maCsdt !== '') {
                foreach (['MaCSDT', 'MaDVNhanHSo'] as $col) {
                    if (array_key_exists($col, $hoSoPayload)) {
                        $hoSoPayload[$col] = $maCsdt;
                    }
                }
            }
            if ($maSoGtvt !== '' && array_key_exists('MaSoGTVT', $hoSoPayload)) {
                $hoSoPayload['MaSoGTVT'] = $maSoGtvt;
            }
        }

        return [$nguoiPayload, $hoSoPayload];
    }
}
