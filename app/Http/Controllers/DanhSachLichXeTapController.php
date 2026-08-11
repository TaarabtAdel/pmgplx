<?php

namespace App\Http\Controllers;

use App\Models\KhoaHocXeTap;
use Illuminate\Http\Request;
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
            $query->where('x.MaKH', 'like', '%'.$maKH.'%');
        }

        if ($bienSo = trim((string) $request->input('bien_so_xe'))) {
            $query->where('x.BienSoXe', 'like', '%'.$bienSo.'%');
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

        $items = $query
            ->orderByDesc('x.NgayBD')
            ->orderByDesc('x.MaLichSD')
            ->paginate($perPage)
            ->withQueryString();

        return view('lich.danh-sach-lich-xe', [
            'items' => $items,
            'filters' => [
                'ma_kh' => $request->input('ma_kh', ''),
                'bien_so_xe' => $request->input('bien_so_xe', ''),
                'tu_ngay' => $request->input('tu_ngay', ''),
                'den_ngay' => $request->input('den_ngay', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }
}
