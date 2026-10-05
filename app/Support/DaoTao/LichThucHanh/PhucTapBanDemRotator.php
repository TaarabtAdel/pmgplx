<?php

namespace App\Support\DaoTao\LichThucHanh;

final class PhucTapBanDemRotator
{
    /**
     * @param  array<string, mixed>  $mauCa
     * @param  list<string>  $phucTapDaysForGv ordered ISO dates including current
     * @return array{range: list<string>, noi_dung_phu: string, mau: string, so_gio: float}
     */
    public function cellMeta(
        string $iso,
        array $gv,
        array $mauCa,
        int $gioNgay,
        LichTimeline $timeline,
        array $phucTapDaysForGv
    ): array {
        $idx = count($phucTapDaysForGv) - 1;
        $tplKey = $timeline->mauPhucTapForDate($iso);
        $tpl = $mauCa[$tplKey] ?? $mauCa['phuc_tap_sau'] ?? [];

        if ($tplKey === 'phuc_tap_giai_doan_dau' && $idx < 5) {
            $chieu = $tpl['chieu'] ?? ['13:59', '17:59', '18:00', '22:00'];
            $soGio = MauCa::soGioTuKhoang($chieu);
            if ($soGio <= 0) {
                $soGio = 8.0;
            }

            return [
                'range' => $chieu,
                'noi_dung_phu' => 'BAN ĐÊM',
                'mau' => BaiGiang::PHUC_TAP,
                'so_gio' => $soGio,
            ];
        }

        if ($tplKey === 'phuc_tap_sau') {
            $sangParts = $tpl['sang'] ?? ['05:59', '11:59', '20:01', '22:01'];
            if ($gv['ca'] === 'sang' && count($sangParts) >= 4 && $idx % 2 === 1) {
                $range = [$sangParts[0], $sangParts[3]];
                $soGio = MauCa::soGioTuKhoang([$sangParts[0], $sangParts[1]]) + MauCa::soGioTuKhoang([$sangParts[2], $sangParts[3]]);

                return [
                    'range' => $range,
                    'noi_dung_phu' => 'BAN ĐÊM',
                    'mau' => BaiGiang::PHUC_TAP,
                    'so_gio' => $soGio > 0 ? $soGio : (float) $gioNgay,
                ];
            }
        }

        $range = $gv['ca'] === 'sang'
            ? array_slice($tpl['sang'] ?? ['05:59', '11:59'], 0, 2)
            : ($tpl['chieu'] ?? ['12:00', '20:00']);
        $soGio = MauCa::soGioTuKhoang($range);
        if ($soGio <= 0) {
            $soGio = (float) $gioNgay;
        }

        return [
            'range' => $range,
            'noi_dung_phu' => '',
            'mau' => BaiGiang::PHUC_TAP,
            'so_gio' => $soGio,
        ];
    }
}
