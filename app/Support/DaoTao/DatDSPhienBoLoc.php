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
        if (in_array($dat, ['1', 'true', 'yes'], true)) {
            $dat = 'dat';
        } elseif (in_array($dat, ['0', 'false', 'no'], true)) {
            $dat = 'chua_dat';
        }
        $datCt = trim((string) $request->input('dat_ct', ''));
        $ngay = trim((string) $request->input('ngay', ''));
        $tuNgay = trim((string) $request->input('tu_ngay', ''));
        $denNgay = trim((string) $request->input('den_ngay', ''));

        if ($ngay === '' && $tuNgay !== '' && ($denNgay === '' || $denNgay === $tuNgay)) {
            $ngay = $tuNgay;
        }

        return [
            'ma_phien' => trim((string) $request->input('ma_phien', '')),
            'ma_hoc_vien' => trim((string) $request->input('ma_hoc_vien', '')),
            'ma_khoa_hoc' => trim((string) $request->input('ma_khoa_hoc', '')),
            'loai_khoa_hoc' => trim((string) $request->input('loai_khoa_hoc', '')),
            'ma_giao_vien' => trim((string) $request->input('ma_giao_vien', '')),
            'bien_so_xe' => trim((string) $request->input('bien_so_xe', '')),
            'ngay' => $ngay,
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'loi' => array_values(array_filter((array) $request->input('loi', []))),
            'phan_loai' => array_values(array_unique(array_map('intval', array_filter((array) $request->input('phan_loai', []))))),
            'chua_phan_loai' => $request->boolean('chua_phan_loai'),
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

        $maHocVien = trim((string) ($filters['ma_hoc_vien'] ?? ''));
        if ($maHocVien !== '') {
            [$maHv, $maKhFromHv] = self::parseMaHocVienFilter($maHocVien);
            $query->where('MaHocVien', $maHv);
            if ($maKhFromHv !== '') {
                $query->where('MaKhoaHoc', $maKhFromHv);
            }
        }

        $maPhien = trim((string) ($filters['ma_phien'] ?? ''));
        if ($maPhien !== '') {
            $query->where('MaPhienHoc', 'like', '%'.$maPhien.'%');
        }

        $maKhoaHoc = trim((string) ($filters['ma_khoa_hoc'] ?? ''));
        if ($maKhoaHoc !== '') {
            $query->where('MaKhoaHoc', $maKhoaHoc);
        }

        $loaiKhoaHoc = trim((string) ($filters['loai_khoa_hoc'] ?? ''));
        if ($loaiKhoaHoc !== '') {
            $query->where('LoaiKhoaHoc', $loaiKhoaHoc);
        }

        $maGiaoVien = trim((string) ($filters['ma_giao_vien'] ?? ''));
        if ($maGiaoVien !== '') {
            $query->where('MaGiaoVien', $maGiaoVien);
        }

        $bienSoXe = trim((string) ($filters['bien_so_xe'] ?? ''));
        if ($bienSoXe !== '') {
            $query->where('BienSoXe', $bienSoXe);
        }

        $ngay = trim((string) ($filters['ngay'] ?? ''));
        $tuNgay = trim((string) ($filters['tu_ngay'] ?? ''));
        $denNgay = trim((string) ($filters['den_ngay'] ?? ''));
        if ($ngay !== '') {
            $query->whereDate('ThoiGianBatDauPhienHoc', $ngay);
        } else {
            if ($tuNgay !== '') {
                $query->whereDate('ThoiGianBatDauPhienHoc', '>=', $tuNgay);
            }

            if ($denNgay !== '') {
                $query->whereDate('ThoiGianBatDauPhienHoc', '<=', $denNgay);
            }
        }

        $chuaPhanLoai = (bool) ($filters['chua_phan_loai'] ?? false);
        $phanLoaiIds = $filters['phan_loai'] ?? [];

        if ($chuaPhanLoai && $phanLoaiIds !== []) {
            $query->where(function (Builder $group) use ($phanLoaiIds): void {
                $group->whereDoesntHave('phanLoai')
                    ->orWhereHas('phanLoai', function ($sub) use ($phanLoaiIds): void {
                        $sub->whereIn('DatPhanLoaiPhien.Id', $phanLoaiIds);
                    });
            });
        } elseif ($chuaPhanLoai) {
            $query->whereDoesntHave('phanLoai');
        } elseif ($phanLoaiIds !== []) {
            $query->whereHas('phanLoai', function ($sub) use ($phanLoaiIds): void {
                $sub->whereIn('DatPhanLoaiPhien.Id', $phanLoaiIds);
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
