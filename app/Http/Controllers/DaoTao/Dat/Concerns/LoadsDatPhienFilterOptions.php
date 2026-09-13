<?php

namespace App\Http\Controllers\DaoTao\Dat\Concerns;

use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;

trait LoadsDatPhienFilterOptions
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     khoaHocOptions: \Illuminate\Support\Collection,
     *     giaoVienOptions: \Illuminate\Support\Collection,
     *     loaiKhoaHocOptions: \Illuminate\Support\Collection,
     *     selectedHocVienOption: ?array{id: string, text: string}
     * }
     */
    protected function loadDatPhienFilterOptions(array $filters): array
    {
        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $giaoVienOptions = DatDSPhien::query()
            ->selectRaw('MaGiaoVien, MAX(HoTenGiaoVien) as HoTenGiaoVien')
            ->whereNotNull('MaGiaoVien')
            ->where('MaGiaoVien', '!=', '')
            ->groupBy('MaGiaoVien')
            ->orderBy('MaGiaoVien')
            ->get();

        $loaiKhoaHocOptions = DatDSPhien::query()
            ->whereNotNull('LoaiKhoaHoc')
            ->where('LoaiKhoaHoc', '!=', '')
            ->distinct()
            ->orderBy('LoaiKhoaHoc')
            ->pluck('LoaiKhoaHoc');

        $selectedHocVienOption = null;
        if ($filters['ma_hoc_vien'] !== '') {
            [$maHv, $maKh] = DatDSPhienBoLoc::parseMaHocVienFilter($filters['ma_hoc_vien']);
            $selectedQuery = DatDSPhien::query()
                ->selectRaw('MaHocVien, MaKhoaHoc, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc')
                ->where('MaHocVien', $maHv)
                ->groupBy('MaHocVien', 'MaKhoaHoc');

            if ($maKh !== '') {
                $selectedQuery->where('MaKhoaHoc', $maKh);
            }

            $selectedRow = $selectedQuery->first();
            if ($selectedRow) {
                $selectedHocVienOption = $this->hocVienOptionFromRow($selectedRow);
            }
        }

        return [
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienOptions' => $giaoVienOptions,
            'loaiKhoaHocOptions' => $loaiKhoaHocOptions,
            'selectedHocVienOption' => $selectedHocVienOption,
        ];
    }

    /**
     * @return array{id: string, text: string}
     */
    protected function hocVienOptionFromRow(object $row): array
    {
        $maHv = (string) ($row->MaHocVien ?? '');
        $maKh = trim((string) ($row->MaKhoaHoc ?? ''));

        return [
            'id' => DatDSPhienBoLoc::composeMaHocVienValue($maHv, $maKh),
            'text' => $this->formatHocVienOptionText(
                trim((string) ($row->HoTenHocVien ?? '')),
                $maHv,
                trim((string) ($row->TenKhoaHoc ?? '')),
                $maKh
            ),
        ];
    }

    protected function formatHocVienOptionText(string $hoTen, string $maHocVien, string $tenKhoaHoc, string $maKhoaHoc): string
    {
        $label = $hoTen !== '' ? $hoTen.' ('.$maHocVien.')' : $maHocVien;
        $khoa = $tenKhoaHoc !== '' ? $tenKhoaHoc : $maKhoaHoc;

        if ($khoa !== '') {
            $label .= ' — '.$khoa;
        }

        return $label;
    }
}
