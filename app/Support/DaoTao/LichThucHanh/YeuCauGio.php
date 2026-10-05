<?php

namespace App\Support\DaoTao\LichThucHanh;

use App\Models\DaoTao\DatDieuKienDat;

final class YeuCauGio
{
    /**
     * @param  array<string, mixed>  $cauHinh
     * @return array<string, array{dat: float, dem: float, tu_dong: float, cao_toc: float, label: string}>
     */
    public static function theoGiaoVien(array $cauHinh): array
    {
        $hang = (string) ($cauHinh['hang_dao_tao'] ?? 'B');
        $heSo = (float) ($cauHinh['he_so_quy_doi'] ?? 2);
        $dk = $cauHinh['dieu_kien_dat_hang'] ?? null;
        if (! is_array($dk)) {
            $row = DatDieuKienDat::forHang($hang);
            $dk = $row ? [
                'so_gio_hoc' => (float) $row->SoGioHoc,
                'tap_lai_ban_dem_gio' => (float) $row->TapLaiBanDemGio,
                'xe_so_tu_dong_gio' => (float) $row->XeSoTuDongGio,
                'gio_cao_toc_gio' => (float) $row->GioCaoTocGio,
            ] : ['so_gio_hoc' => 0, 'tap_lai_ban_dem_gio' => 0, 'xe_so_tu_dong_gio' => 0, 'gio_cao_toc_gio' => 0];
        }

        $result = [];
        foreach ($cauHinh['cap_xe'] ?? [] as $cap) {
            if (! is_array($cap)) {
                continue;
            }
            foreach (['gv_sang', 'gv_chieu'] as $key) {
                $ma = trim((string) ($cap[$key] ?? ''));
                if ($ma === '') {
                    continue;
                }
                $soHv = (float) ($cap['so_hoc_vien_'.$key] ?? $cauHinh['so_hoc_vien_mac_dinh'] ?? 5);
                $ten = trim((string) ($cap['ten_'.$key] ?? $ma));
                if (! isset($result[$ma])) {
                    $result[$ma] = [
                        'label' => $ten,
                        'dat' => 0.0,
                        'dem' => 0.0,
                        'tu_dong' => 0.0,
                        'cao_toc' => 0.0,
                    ];
                }
                if ($ten !== '' && ($result[$ma]['label'] === $ma || $result[$ma]['label'] === '')) {
                    $result[$ma]['label'] = $ten;
                }
                $result[$ma]['dat'] += (float) $dk['so_gio_hoc'] * $soHv * $heSo;
                $result[$ma]['dem'] += (float) $dk['tap_lai_ban_dem_gio'] * $soHv * $heSo;
                $result[$ma]['tu_dong'] += (float) $dk['xe_so_tu_dong_gio'] * $soHv * $heSo;
                $result[$ma]['cao_toc'] += (float) $dk['gio_cao_toc_gio'] * $soHv * $heSo;
            }
        }

        return $result;
    }
}
