<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;

use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachLichGiaoVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $query = KhoaHocGiaoVien::query();

        if ($maKH = trim((string) $request->input('ma_kh'))) {
            $query->where('MaKH', $maKH);
        }

        if ($maGV = trim((string) $request->input('ma_gv'))) {
            $query->where('MaGV', $maGV);
        }

        if ($from = $request->input('tu_ngay')) {
            $query->where('NgayBD', '>=', $from);
        }

        if ($to = $request->input('den_ngay')) {
            $query->where('NgayKT', '<=', $to.' 23:59:59');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('TrangThai', (int) $request->input('trang_thai'));
        }

        $items = $query
            ->orderByDesc('NgayBD')
            ->orderByDesc('MaLichLV')
            ->paginate($perPage)
            ->withQueryString();

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        $giaoViens = GiaoVien::query()
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'HoTenDem', 'TenGV']);

        return view('PMGPLX.lich.danh-sach-lich-gv', [
            'items' => $items,
            'khoaHocs' => $khoaHocs,
            'giaoViens' => $giaoViens,
            'monMap' => DmMonHoc::query()->pluck('TenMH', 'MaMH'),
            'filters' => [
                'ma_kh' => $request->input('ma_kh', ''),
                'ma_gv' => $request->input('ma_gv', ''),
                'tu_ngay' => $request->input('tu_ngay', ''),
                'den_ngay' => $request->input('den_ngay', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }
}
