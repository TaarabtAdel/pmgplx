<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;

/**
 * Cặp GV sáng/chiều theo cùng biển số xe trong một khóa (lịch GV + lịch xe tập).
 */
final class GiaoVienDoTrongCapXeTrongKhoa
{
    public static function normalizeMaKh(string $maKh): string
    {
        return strtoupper(trim($maKh));
    }

    /**
     * @param  list<string>  $maKhList
     * @return array<string, array<string, string>> ma_kh (upper) → ma_gv_sang → ma_gv_chieu
     */
    public static function chieuPartnerMapByKhoa(array $maKhList): array
    {
        $maKhList = array_values(array_filter(array_unique(array_map(
            static fn ($v): string => self::normalizeMaKh((string) $v),
            $maKhList
        ))));
        if ($maKhList === []) {
            return [];
        }

        /** @var array<string, array<string, list<string>>> $gvsByKhPlate */
        $gvsByKhPlate = [];

        $add = function (string $maKh, string $maGv, string $bienSoRaw) use (&$gvsByKhPlate): void {
            $maKh = self::normalizeMaKh($maKh);
            $maGv = trim($maGv);
            $plate = LichExcelBienSo::normalize($bienSoRaw);
            if ($maKh === '' || $maGv === '' || $plate === '') {
                return;
            }
            if (! isset($gvsByKhPlate[$maKh][$plate])) {
                $gvsByKhPlate[$maKh][$plate] = [];
            }
            if (! in_array($maGv, $gvsByKhPlate[$maKh][$plate], true)) {
                $gvsByKhPlate[$maKh][$plate][] = $maGv;
            }
        };

        KhoaHocGiaoVien::query()
            ->whereIn('MaKH', $maKhList)
            ->where('IsKhoaHocGiaoVien', 0)
            ->get(['MaKH', 'MaGV', 'BienSoXe'])
            ->each(function (KhoaHocGiaoVien $row) use ($add): void {
                $add((string) $row->MaKH, (string) $row->MaGV, (string) ($row->BienSoXe ?? ''));
            });

        KhoaHocXeTap::query()
            ->whereIn('MaKH', $maKhList)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('MaGV')
            ->get(['MaKH', 'MaGV', 'BienSoXe'])
            ->each(function (KhoaHocXeTap $row) use ($add): void {
                $add((string) $row->MaKH, (string) $row->MaGV, (string) ($row->BienSoXe ?? ''));
            });

        $out = [];
        foreach ($gvsByKhPlate as $maKh => $byPlate) {
            $out[$maKh] = [];
            foreach ($byPlate as $gvs) {
                sort($gvs, SORT_STRING);
                foreach ($gvs as $sang) {
                    if (isset($out[$maKh][$sang])) {
                        continue;
                    }
                    $partners = array_values(array_filter(
                        $gvs,
                        static fn (string $g): bool => $g !== $sang
                    ));
                    $out[$maKh][$sang] = $partners === [] ? $sang : $partners[0];
                }
            }
        }

        return $out;
    }
}
