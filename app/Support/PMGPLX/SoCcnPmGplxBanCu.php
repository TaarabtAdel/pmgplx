<?php

namespace App\Support\PMGPLX;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Số CCN (GCN PM GPLX cũ) trên DB bản cũ — map MaDK theo 5 ký tự đầu. */
final class SoCcnPmGplxBanCu
{
    /**
     * Gán `so_ccn` từ PM bản cũ; không có hoặc lỗi DB thì để rỗng (không dùng PM mới).
     *
     * @param  list<array{ma_dk: string, so_ccn?: string, ...}>  $hocVien
     */
    public static function ganSoCcnTuPmBanCu(string $maKhMoi, array &$hocVien): void
    {
        foreach ($hocVien as &$hv) {
            $hv['so_ccn'] = '';
        }
        unset($hv);

        $maKhCu = MaDkBanCu::resolveMaKhoaHocBanCu($maKhMoi);
        if ($maKhCu === null) {
            return;
        }

        if (! Schema::connection('sqlsrv_old')->hasTable('NguoiLX_HoSo')
            || ! Schema::connection('sqlsrv_old')->hasColumn('NguoiLX_HoSo', 'SoCCN')) {
            return;
        }

        $mapMoiSangCu = [];
        foreach ($hocVien as $hv) {
            $maDk = trim((string) ($hv['ma_dk'] ?? ''));
            if ($maDk === '') {
                continue;
            }
            $mapMoiSangCu[$maDk] = MaDkBanCu::mapFromSource($maDk, $maKhCu);
        }
        if ($mapMoiSangCu === []) {
            return;
        }

        try {
            $rows = DB::connection('sqlsrv_old')
                ->table('NguoiLX_HoSo')
                ->whereIn('MaDK', array_values(array_unique($mapMoiSangCu)))
                ->get(['MaDK', 'SoCCN']);
        } catch (\Throwable) {
            return;
        }

        $theoMaCu = [];
        foreach ($rows as $row) {
            $so = trim((string) ($row->SoCCN ?? ''));
            if ($so !== '') {
                $theoMaCu[(string) $row->MaDK] = $so;
            }
        }

        foreach ($hocVien as &$hv) {
            $maCu = $mapMoiSangCu[$hv['ma_dk']] ?? null;
            if ($maCu === null) {
                continue;
            }
            $hv['so_ccn'] = $theoMaCu[$maCu] ?? '';
        }
        unset($hv);
    }
}