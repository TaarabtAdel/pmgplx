<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DatDSPhienBoLoc
{
    /**
     * @return array<string, mixed>
     */
    public static function parseFilters(Request $request): array
    {
        $dat = trim((string) $request->input('dat', $request->input('dat_anh', '')));
        $datCt = trim((string) $request->input('dat_ct', ''));

        return [
            'ma_phien' => trim((string) $request->input('ma_phien', '')),
            'ma_hoc_vien' => trim((string) $request->input('ma_hoc_vien', '')),
            'ma_khoa_hoc' => trim((string) $request->input('ma_khoa_hoc', '')),
            'loai_khoa_hoc' => trim((string) $request->input('loai_khoa_hoc', '')),
            'ma_giao_vien' => trim((string) $request->input('ma_giao_vien', '')),
            'tu_ngay' => trim((string) $request->input('tu_ngay', '')),
            'den_ngay' => trim((string) $request->input('den_ngay', '')),
            'loi' => array_values(array_filter((array) $request->input('loi', []))),
            'phan_loai' => array_values(array_unique(array_map('intval', array_filter((array) $request->input('phan_loai', []))))),
            'dat' => in_array($dat, ['dat', 'chua_dat'], true) ? $dat : '',
            'dat_ct' => in_array($datCt, ['dat', 'chua_dat'], true) ? $datCt : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function filteredQuery(array $filters, ?string $orderBy = 'sessions'): Builder
    {
        $query = DatDSPhien::query();

        if ($orderBy === 'sessions') {
            $query->orderByDesc('ThoiGianBatDauPhienHoc')->orderByDesc('Id');
        }

        if ($filters['ma_hoc_vien'] !== '') {
            [$maHv, $maKhFromHv] = self::parseMaHocVienFilter($filters['ma_hoc_vien']);
            $query->where('MaHocVien', $maHv);
            if ($maKhFromHv !== '') {
                $query->where('MaKhoaHoc', $maKhFromHv);
            }
        }

        if ($filters['ma_phien'] !== '') {
            $query->where('MaPhienHoc', 'like', '%'.$filters['ma_phien'].'%');
        }

        if ($filters['ma_khoa_hoc'] !== '') {
            $query->where('MaKhoaHoc', $filters['ma_khoa_hoc']);
        }

        if ($filters['loai_khoa_hoc'] !== '') {
            $query->where('LoaiKhoaHoc', $filters['loai_khoa_hoc']);
        }

        if ($filters['ma_giao_vien'] !== '') {
            $query->where('MaGiaoVien', $filters['ma_giao_vien']);
        }

        if ($filters['tu_ngay'] !== '') {
            $query->whereDate('ThoiGianBatDauPhienHoc', '>=', $filters['tu_ngay']);
        }

        if ($filters['den_ngay'] !== '') {
            $query->whereDate('ThoiGianBatDauPhienHoc', '<=', $filters['den_ngay']);
        }

        if ($filters['phan_loai'] !== []) {
            $query->whereHas('phanLoai', function ($sub) use ($filters): void {
                $sub->whereIn('DatPhanLoaiPhien.Id', $filters['phan_loai']);
            });
        }

        return $query;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function parseMaHocVienFilter(string $value): array
    {
        if (str_contains($value, '|')) {
            [$maHv, $maKh] = explode('|', $value, 2);

            return [trim($maHv), trim($maKh)];
        }

        return [trim($value), ''];
    }

    public static function composeMaHocVienValue(string $maHocVien, string $maKhoaHoc): string
    {
        if ($maKhoaHoc !== '') {
            return $maHocVien.'|'.$maKhoaHoc;
        }

        return $maHocVien;
    }
}
