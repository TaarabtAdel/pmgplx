<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\KhoaHoc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DatPhanCongHocVienBoLoc
{
    /**
     * @return array<string, mixed>
     */
    public static function parseFilters(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 50);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 50;
        }

        return [
            'ma_khoa_hoc' => trim((string) $request->input('ma_khoa_hoc', '')),
            'ma_giao_vien' => trim((string) $request->input('ma_giao_vien', '')),
            'bien_so_xe' => trim((string) $request->input('bien_so_xe', '')),
            'tu_khoa' => trim((string) $request->input('tu_khoa', '')),
            'per_page' => $perPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function filteredQuery(array $filters): Builder
    {
        $query = DatPhanCongHocVien::query()
            ->orderBy('MaKhoaHoc')
            ->orderBy('MaGiaoVien')
            ->orderBy('HoTenHocVien')
            ->orderBy('Id');

        if ($filters['ma_khoa_hoc'] !== '') {
            $query->where('MaKhoaHoc', $filters['ma_khoa_hoc']);
        }

        if ($filters['ma_giao_vien'] !== '') {
            $query->where('MaGiaoVien', $filters['ma_giao_vien']);
        }

        if ($filters['bien_so_xe'] !== '') {
            $query->where('BienSoXe', $filters['bien_so_xe']);
        }

        if ($filters['tu_khoa'] !== '') {
            $like = '%'.$filters['tu_khoa'].'%';
            $query->where(function (Builder $sub) use ($like): void {
                $sub->where('MaHocVien', 'like', $like)
                    ->orWhere('HoTenHocVien', 'like', $like);
            });
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public static function distinctValues(string $column): array
    {
        return DatPhanCongHocVien::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, object{MaKhoaHoc: string, TenKhoaHoc: string}>
     */
    public static function khoaHocOptions(): Collection
    {
        $codes = self::distinctValues('MaKhoaHoc');
        if ($codes === []) {
            return collect();
        }

        $namesFromPhien = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereIn('MaKhoaHoc', $codes)
            ->groupBy('MaKhoaHoc')
            ->pluck('TenKhoaHoc', 'MaKhoaHoc');

        $missing = array_values(array_filter(
            $codes,
            static fn (string $ma): bool => trim((string) ($namesFromPhien[$ma] ?? '')) === ''
        ));

        $namesFromPmgplx = $missing === []
            ? collect()
            : KhoaHoc::query()
                ->whereIn('MaKH', $missing)
                ->pluck('TenKH', 'MaKH');

        return collect($codes)->map(static function (string $ma) use ($namesFromPhien, $namesFromPmgplx) {
            $ten = trim((string) ($namesFromPhien[$ma] ?? ''));
            if ($ten === '') {
                $ten = trim((string) ($namesFromPmgplx[$ma] ?? ''));
            }

            return (object) [
                'MaKhoaHoc' => $ma,
                'TenKhoaHoc' => $ten,
            ];
        });
    }
}
