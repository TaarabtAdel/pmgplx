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
            ->get(['Id', 'MaGiaoVien', 'MaHocVien', 'HoTenHocVien', 'TuNgay', 'DenNgay']);

        /** @var array<string, list<array{id: int, ma_hoc_vien: string, ho_ten: string, khoang_ngay: string}>> $hvByGv */
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
                'khoang_ngay' => $row->nhanKhoangNgay(),
            ];
        }

        $allMaGv = DatPhanCongHocVienSaver::maGiaoVienQueryValues(array_merge($gvFromLich, array_keys($hvByGv)));
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
            ->get(['Id', 'MaKhoaHoc', 'MaHocVien', 'MaGiaoVien', 'BienSoXe', 'BienSoXeTuDong', 'TuNgay', 'DenNgay']);

        $maGvCodes = DatPhanCongHocVienSaver::maGiaoVienQueryValues($rows->pluck('MaGiaoVien'));
        $gvNames = $maGvCodes === []
            ? collect()
            : GiaoVien::query()
                ->whereIn('MaGV', $maGvCodes)
                ->get(['MaGV', 'HoTenDem', 'TenGV'])
                ->keyBy('MaGV');
        $tenGv = static function (string $maGv) use ($gvNames): string {
            $gv = $gvNames->get($maGv);

            return $gv ? trim($gv->ho_ten) : $maGv;
        };

        $out = [];
        foreach (DatPhanCongHocVienTheoNgay::nhomTheoHocVien($rows) as $hvRows) {
            $pc = DatPhanCongHocVienTheoNgay::hienTai($hvRows);
            $maGv = trim((string) ($pc->MaGiaoVien ?? ''));

            $out[DatPhanCongHocVienSaver::rowKeyForPhanCong(
                (string) $pc->MaKhoaHoc,
                (string) $pc->MaHocVien
            )] = [
                'ma_giao_vien' => $maGv,
                'ten_giao_vien' => $tenGv($maGv),
                'bien_so_xe' => trim((string) ($pc->BienSoXe ?? '')),
                'bien_so_xe_tu_dong' => trim((string) ($pc->BienSoXeTuDong ?? '')),
                'khoang_ngay' => $pc->nhanKhoangNgay(),
                'lich_su' => count($hvRows) > 1
                    ? array_map(static fn (DatPhanCongHocVien $r): array => [
                        'ten_giao_vien' => $tenGv(trim((string) $r->MaGiaoVien)),
                        'ma_giao_vien' => trim((string) $r->MaGiaoVien),
                        'khoang_ngay' => $r->nhanKhoangNgay(),
                        'hien_tai' => $r->Id === $pc->Id,
                    ], $hvRows)
                    : [],
            ];
        }

        return $out;
    }

    public const LOC_CHUA_PHAN_CONG = '__chua__';

    /**
     * MaGiaoVien (đang áp dụng) => danh sách MaHocVien.
     *
     * @return array<string, list<string>>
     */
    public static function hocVienTheoGvHienTai(string $maKhoaHoc): array
    {
        $rows = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', trim($maKhoaHoc))
            ->get(['Id', 'MaHocVien', 'MaGiaoVien', 'TuNgay', 'DenNgay']);

        $out = [];
        foreach (DatPhanCongHocVienTheoNgay::nhomTheoHocVien($rows) as $maHv => $hvRows) {
            $maGv = trim((string) (DatPhanCongHocVienTheoNgay::hienTai($hvRows)?->MaGiaoVien ?? ''));
            if ($maGv !== '') {
                $out[$maGv][] = (string) $maHv;
            }
        }

        return $out;
    }

    public static function hocVienQuery(
        string $maKhoaHoc,
        string $tuKhoa = '',
        bool $anDaPhanCong = false,
        string $gvPhanCong = ''
    ): Builder {
        $maKh = trim($maKhoaHoc);
        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('h.MaKhoaHoc', $maKh)
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

        $gvPhanCong = trim($gvPhanCong);
        if ($gvPhanCong !== '' && $gvPhanCong !== self::LOC_CHUA_PHAN_CONG && $maKh !== '') {
            $theoGv = self::hocVienTheoGvHienTai($maKh);
            $gvNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($gvPhanCong);
            $maHvList = [];
            foreach ($theoGv as $maGv => $list) {
                if (DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $maGv) === $gvNorm) {
                    array_push($maHvList, ...$list);
                }
            }
            if ($maHvList === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('n.MaDK', array_values(array_unique($maHvList)));
            }
        }

        if (($anDaPhanCong || $gvPhanCong === self::LOC_CHUA_PHAN_CONG) && $maKh !== '') {
            $assignedMa = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $maKh)
                ->pluck('MaHocVien')
                ->map(static fn ($ma): string => trim((string) $ma))
                ->filter(static fn (string $ma): bool => $ma !== '')
                ->unique()
                ->values()
                ->all();

            if ($assignedMa !== []) {
                $query->whereNotIn('n.MaDK', $assignedMa);
            }
        }

        return $query
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK');
    }

    /**
     * @return array{per_page: int, tu_khoa: string, an_da_phan_cong: bool, gv_phan_cong: string}
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
            'an_da_phan_cong' => $request->boolean('an_da_phan_cong'),
            'gv_phan_cong' => trim((string) $request->input('gv_phan_cong', '')),
        ];
    }
}
