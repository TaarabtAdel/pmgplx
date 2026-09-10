<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\XeTap;
use App\Support\DaoTao\DatPhienLichXeThongKe;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DanhSachLichXeTapController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $query = KhoaHocXeTap::query()
            ->from('KhoaHoc_XeTap as x')
            ->leftJoin('KhoaHoc as k', 'k.MaKH', '=', 'x.MaKH')
            ->where('x.IsKhoaHocXeTap', 0)
            ->whereNotNull('x.NgayBD')
            ->select([
                'x.MaLichSD',
                'x.MaKH',
                'x.BienSoXe',
                'x.MaGV',
                'x.TenGV',
                'x.NgayBD',
                'x.NgayKT',
                'x.TrangThai',
                'x.DiaDiem',
                'k.NgayKG',
                'k.NgayBG',
            ]);

        if ($maKH = trim((string) $request->input('ma_kh'))) {
            $query->where('x.MaKH', $maKH);
        }

        if ($maGV = trim((string) $request->input('ma_gv'))) {
            $query->where('x.MaGV', $maGV);
        }

        if ($bienSo = trim((string) $request->input('bien_so_xe'))) {
            $query->where('x.BienSoXe', $bienSo);
        }

        if ($from = $request->input('tu_ngay')) {
            $query->where('x.NgayBD', '>=', $from);
        }

        if ($to = $request->input('den_ngay')) {
            $query->where('x.NgayKT', '<=', $to.' 23:59:59');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('x.TrangThai', (int) $request->input('trang_thai'));
        }

        $phienDat = trim((string) $request->input('phien_dat', ''));
        if (! in_array($phienDat, ['', 'co_phien', 'chua_co_phien'], true)) {
            $phienDat = '';
        }

        if ($phienDat !== '') {
            $allRows = $query
                ->orderByDesc('x.NgayBD')
                ->orderByDesc('x.MaLichSD')
                ->get();

            $phienStats = DatPhienLichXeThongKe::forScheduleRows($allRows);

            $filtered = $allRows->filter(function (object $row) use ($phienDat, $phienStats): bool {
                $soPhien = $phienStats[(int) $row->MaLichSD]['so_phien'] ?? 0;

                return $phienDat === 'co_phien' ? $soPhien > 0 : $soPhien === 0;
            })->values();

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageItems = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $items = new LengthAwarePaginator(
                $pageItems,
                $filtered->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $items = $query
                ->orderByDesc('x.NgayBD')
                ->orderByDesc('x.MaLichSD')
                ->paginate($perPage)
                ->withQueryString();

            $phienStats = DatPhienLichXeThongKe::forScheduleRows(collect($items->items()));
        }

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        $giaoViens = GiaoVien::query()
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'HoTenDem', 'TenGV']);

        $xeTaps = XeTap::query()
            ->orderBy('BienSoXe')
            ->pluck('BienSoXe');

        return view('PMGPLX.lich.danh-sach-lich-xe', [
            'items' => $items,
            'phienStats' => $phienStats,
            'khoaHocs' => $khoaHocs,
            'giaoViens' => $giaoViens,
            'xeTaps' => $xeTaps,
            'filters' => [
                'ma_kh' => $request->input('ma_kh', ''),
                'ma_gv' => $request->input('ma_gv', ''),
                'bien_so_xe' => $request->input('bien_so_xe', ''),
                'tu_ngay' => $request->input('tu_ngay', ''),
                'den_ngay' => $request->input('den_ngay', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'phien_dat' => $phienDat,
                'per_page' => $perPage,
            ],
        ]);
    }
}
