<?php

namespace App\Support\DaoTao\LichThucHanh;

final class LichThucHanhGeneratedMetrics
{
    /**
     * @param  array<string, mixed>  $lich
     * @return array<string, array<string, mixed>>
     */
    public static function fromLich(array $lich): array
    {
        $metrics = [];
        foreach ($lich['giao_viens'] ?? [] as $gv) {
            $ma = $gv['ma_gv'];
            $metrics[$ma] = [
                'ma_gv' => $ma,
                'cap_stt' => (int) ($gv['cap_stt'] ?? 0),
                'so_ngay_lam' => 0,
                'gio_theo_loai' => [],
                'cabin_ngay' => null,
                'auto_block' => [],
                'ngay_cuoi' => null,
            ];
        }

        foreach ($lich['cells'] ?? [] as $cell) {
            if (! is_array($cell)) {
                continue;
            }
            $ma = (string) ($cell['ma_gv'] ?? '');
            if (! isset($metrics[$ma])) {
                continue;
            }
            if (($cell['nghi_ca_khoa'] ?? false) || ($cell['bai'] ?? '') === 'NGHỈ') {
                continue;
            }
            $gio = (float) ($cell['so_gio'] ?? 0);
            $mau = (string) ($cell['mau'] ?? '');
            if ($mau === '' || ($gio <= 0 && $mau !== BaiGiang::KIEM_TRA)) {
                if ($mau === BaiGiang::KIEM_TRA) {
                    $iso = substr((string) ($cell['thoi_gian'] ?? ''), 0, 10);
                    $metrics[$ma]['ngay_cuoi'] = $iso;
                    $metrics[$ma]['so_ngay_lam']++;
                }

                continue;
            }
            $iso = substr((string) ($cell['thoi_gian'] ?? ''), 0, 10);
            $metrics[$ma]['so_ngay_lam']++;
            $bucket = $mau === BaiGiang::BAN_DEM ? BaiGiang::PHUC_TAP : $mau;
            $metrics[$ma]['gio_theo_loai'][$bucket] = ($metrics[$ma]['gio_theo_loai'][$bucket] ?? 0) + $gio;
            if ($mau === BaiGiang::CABIN && $metrics[$ma]['cabin_ngay'] === null) {
                $metrics[$ma]['cabin_ngay'] = $iso;
            }
            if ($mau === BaiGiang::TU_DONG) {
                $metrics[$ma]['auto_block'][] = $iso;
            }
            $metrics[$ma]['ngay_cuoi'] = $iso;
        }

        foreach ($metrics as $ma => $_) {
            $metrics[$ma]['auto_block'] = array_values(array_unique($metrics[$ma]['auto_block']));
            sort($metrics[$ma]['auto_block']);
        }

        return $metrics;
    }
}
