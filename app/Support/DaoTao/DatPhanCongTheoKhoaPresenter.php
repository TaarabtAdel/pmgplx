<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\NguoiLX;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DatPhanCongTheoKhoaPresenter
{
    /**
     * Giáo viên trên lịch xe tập khóa + phân công hiện có (GV chưa có HV vẫn hiện).
     *
     * @return list<array{
     *     ma_giao_vien: string,
     *     ten_giao_vien: string,
     *     hoc_vien: list<array{id: int, ma_hoc_vien: string, ho_ten: string}>
     * }>
     */
    public static function groupsByGiaoVien(string $maKhoaHoc): array
    {
        $maKh = trim($maKhoaHoc);
        if ($maKh === '') {
            return [];
        }

        $gvFromLich = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('MaGV')
            ->where('MaGV', '!=', '')
            ->distinct()
            ->orderBy('MaGV')
            ->pluck('MaGV')
            ->map(static fn ($v): string => trim((string) $v))
            ->filter(static fn (string $v): bool => $v !== '')
            ->unique()
            ->values()
            ->all();

        $assignments = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKh)
            ->orderBy('MaGiaoVien')
            ->orderBy('HoTenHocVien')
            ->orderBy('MaHocVien')
            ->get(['Id', 'MaGiaoVien', 'MaHocVien', 'HoTenHocVien']);

        /** @var array<string, list<array{id: int, ma_hoc_vien: string, ho_ten: string}>> $hvByGv */
        $hvByGv = [];
        foreach ($assignments as $row) {
            $maGv = trim((string) ($row->MaGiaoVien ?? ''));
            if ($maGv === '') {
                continue;
            }
            $hoTen = trim((string) ($row->HoTenHocVien ?? ''));
            $maHv = trim((string) ($row->MaHocVien ?? ''));
            $hvByGv[$maGv][] = [
                'id' => (int) $row->Id,
                'ma_hoc_vien' => $maHv,
                'ho_ten' => $hoTen !== '' ? $hoTen : $maHv,
            ];
        }

        $allMaGv = array_values(array_unique(array_merge($gvFromLich, array_keys($hvByGv))));
        if ($allMaGv === []) {
            return [];
        }

        $gvNames = GiaoVien::query()
            ->whereIn('MaGV', $allMaGv)
            ->get(['MaGV', 'HoTenDem', 'TenGV'])
            ->keyBy('MaGV');

        $groups = [];
        foreach ($allMaGv as $maGv) {
            $gv = $gvNames->get($maGv);
            $tenGv = $gv ? trim($gv->ho_ten) : '';
            $groups[] = [
                'ma_giao_vien' => $maGv,
                'ten_giao_vien' => $tenGv !== '' ? $tenGv : $maGv,
                'hoc_vien' => $hvByGv[$maGv] ?? [],
            ];
        }

        usort($groups, static fn (array $a, array $b): int => strcasecmp($a['ten_giao_vien'], $b['ten_giao_vien']));

        return $groups;
    }

    /**
     * @return array<string, array{ma_giao_vien: string, ten_giao_vien: string, bien_so_xe: string, bien_so_xe_tu_dong: string}>
     */
    public static function phanCongByRowKeyForPaginator(LengthAwarePaginator $items): array
    {
        $maKh = '';
        $maDkList = [];
        foreach ($items as $row) {
            $maKh = trim((string) ($row->MaKhoaHoc ?? ''));
            $maDk = trim((string) ($row->MaDK ?? ''));
            if ($maDk === '') {
                continue;
            }
            $maDkList[DatPhanCongHocVienSaver::normalizeMaHocVien($maDk)] = true;
            $maDkList[$maDk] = true;
        }

        if ($maKh === '' || $maDkList === []) {
            return [];
        }

        $rows = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKh)
            ->whereIn('MaHocVien', array_keys($maDkList))
            ->get(['MaKhoaHoc', 'MaHocVien', 'MaGiaoVien', 'BienSoXe', 'BienSoXeTuDong']);

        $maGvCodes = $rows->pluck('MaGiaoVien')->filter()->unique()->values()->all();
        $gvNames = $maGvCodes === []
            ? collect()
            : GiaoVien::query()
                ->whereIn('MaGV', $maGvCodes)
                ->get(['MaGV', 'HoTenDem', 'TenGV'])
                ->keyBy('MaGV');

        $out = [];
        foreach ($rows as $pc) {
            $maGv = trim((string) ($pc->MaGiaoVien ?? ''));
            $gv = $gvNames->get($maGv);
            $tenGv = $gv ? trim($gv->ho_ten) : ($maGv !== '' ? $maGv : '');

            $out[DatPhanCongHocVienSaver::rowKeyForPhanCong(
                (string) $pc->MaKhoaHoc,
                (string) $pc->MaHocVien
            )] = [
                'ma_giao_vien' => $maGv,
                'ten_giao_vien' => $tenGv,
                'bien_so_xe' => trim((string) ($pc->BienSoXe ?? '')),
                'bien_so_xe_tu_dong' => trim((string) ($pc->BienSoXeTuDong ?? '')),
            ];
        }

        return $out;
    }

    public static function hocVienQuery(string $maKhoaHoc, string $tuKhoa = ''): Builder
    {
        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('h.MaKhoaHoc', trim($maKhoaHoc))
            ->select([
                'n.MaDK',
                'n.HoDemNLX',
                'n.TenNLX',
                'n.HoVaTen',
                'n.NgaySinh',
                'n.GioiTinh',
                'n.SoCMT',
                'n.TrangThai',
                'h.SoHoSo',
                'h.MaKhoaHoc',
                'h.HangGPLX',
            ]);

        $tuKhoa = trim($tuKhoa);
        if ($tuKhoa !== '') {
            $query->where(function ($q) use ($tuKhoa): void {
                $q->where('n.MaDK', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('n.HoVaTen', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('n.HoDemNLX', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('n.TenNLX', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('n.SoCMT', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('h.SoHoSo', 'like', '%'.$tuKhoa.'%');
            });
        }

        return $query
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK');
    }

    /**
     * @return array{per_page: int, tu_khoa: string}
     */
    public static function parseHocVienFilters(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 50);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 50;
        }

        return [
            'tu_khoa' => trim((string) $request->input('tu_khoa', '')),
            'per_page' => $perPage,
        ];
    }
}
