<?php

namespace App\Http\Controllers\DaoTao\Concerns;

use App\Models\DaoTao\GiaoVien as GiaoVienDaoTao;
use App\Models\DaoTao\KhoaDaoTao;
use App\Models\DaoTao\XeTapLai;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use Illuminate\Http\Request;

trait BuildsPhanCongTuLichFilters
{
    /**
     * @return array{
     *     filters: array{ma_kh: string, ma_gv: string, bien_so_xe: string, loai?: string},
     *     queryFilters: array<string, mixed>,
     *     listQueryParams: array<string, mixed>,
     * }
     */
    protected function phanCongTuLichFilterSets(Request $request, bool $includeLoai = true): array
    {
        $filters = [
            'ma_kh' => trim((string) $request->input('ma_kh', '')),
            'ma_gv' => trim((string) $request->input('ma_gv', '')),
            'bien_so_xe' => trim((string) $request->input('bien_so_xe', '')),
        ];

        if ($includeLoai) {
            $filters['loai'] = $this->loaiFilter($request->input('loai'));
        }

        $this->applyLegacyFilterAliases($request, $filters);

        $queryFilters = [];

        if ($filters['ma_kh'] !== '') {
            $queryFilters['ma_kh'] = [$filters['ma_kh']];
        }

        if ($filters['ma_gv'] !== '') {
            $queryFilters['ma_gv'] = $filters['ma_gv'];
        }

        if ($filters['bien_so_xe'] !== '') {
            $queryFilters['bien_so_xe'] = $filters['bien_so_xe'];
        }

        if ($includeLoai) {
            $queryFilters['loai'] = $filters['loai'];
        }

        $listQueryParams = array_filter(
            $filters,
            fn ($value) => $value !== null && $value !== '' && $value !== 'tat_ca'
        );

        return [
            'filters' => $filters,
            'queryFilters' => $queryFilters,
            'listQueryParams' => $listQueryParams,
        ];
    }

    /**
     * @param  array{ma_kh: string, ma_gv: string, bien_so_xe: string}  $filters
     */
    private function applyLegacyFilterAliases(Request $request, array &$filters): void
    {
        if ($filters['ma_kh'] === '' && $request->filled('khoa_dao_tao_id')) {
            $khoaId = $this->positiveIntOrNull($request->input('khoa_dao_tao_id'));
            if ($khoaId !== null) {
                $maKhList = (new PhanCongTuLichPmgplxQuery())->maKhForKhoaDaoTaoId($khoaId);
                if ($maKhList !== []) {
                    $filters['ma_kh'] = $maKhList[0];
                }
            }
        }

        if ($filters['ma_gv'] === '' && $request->filled('giao_vien_id')) {
            $maGv = trim((string) (GiaoVienDaoTao::query()
                ->whereKey($this->positiveIntOrNull($request->input('giao_vien_id')))
                ->value('MaGV') ?? ''));
            if ($maGv !== '') {
                $filters['ma_gv'] = $maGv;
            }
        }

        if ($filters['bien_so_xe'] === '' && $request->filled('xe_tap_lai_id')) {
            $bienSo = trim((string) (XeTapLai::query()
                ->whereKey($this->positiveIntOrNull($request->input('xe_tap_lai_id')))
                ->value('BienSo') ?? ''));
            if ($bienSo !== '') {
                $filters['bien_so_xe'] = $bienSo;
            }
        }
    }

    protected function redirectTenKhoaToMaKh(Request $request, string $routeName): ?\Illuminate\Http\RedirectResponse
    {
        if (! $request->filled('ten_khoa') || $request->filled('ma_kh')) {
            return null;
        }

        $tenNorm = KhoaDaoTao::normalizeTenKhoa((string) $request->input('ten_khoa'));

        $maKh = KhoaHoc::query()
            ->get(['MaKH', 'TenKH'])
            ->first(fn (KhoaHoc $kh): bool => KhoaDaoTao::normalizeTenKhoa((string) $kh->TenKH) === $tenNorm)
            ?->MaKH;

        if ($maKh === null) {
            return null;
        }

        return redirect()->route($routeName, array_merge(
            $request->except(['ten_khoa']),
            ['ma_kh' => $maKh]
        ));
    }

    private function loaiFilter(mixed $value): string
    {
        $loai = (string) ($value ?? 'tat_ca');

        return in_array($loai, ['tat_ca', 'ly_thuyet', 'thuc_hanh'], true) ? $loai : 'tat_ca';
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
