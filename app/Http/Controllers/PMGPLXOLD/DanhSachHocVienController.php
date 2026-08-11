<?php

namespace App\Http\Controllers\PMGPLXOLD;

use App\Http\Controllers\Controller;
use App\Models\PMGPLXOLD\KhoaHoc;
use App\Models\PMGPLXOLD\NguoiLX;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
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
                'h.HangDaoTao',
            ]);

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('n.MaDK', 'like', '%'.$keyword.'%')
                    ->orWhere('n.HoVaTen', 'like', '%'.$keyword.'%')
                    ->orWhere('n.HoDemNLX', 'like', '%'.$keyword.'%')
                    ->orWhere('n.TenNLX', 'like', '%'.$keyword.'%')
                    ->orWhere('n.SoCMT', 'like', '%'.$keyword.'%')
                    ->orWhere('h.SoHoSo', 'like', '%'.$keyword.'%');
            });
        }

        if ($maKH = trim((string) $request->input('ma_kh'))) {
            $query->where('h.MaKhoaHoc', $maKH);
        }

        if ($hang = trim((string) $request->input('hang_gplx'))) {
            $query->where('h.HangGPLX', 'like', '%'.$hang.'%');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('n.TrangThai', (string) (int) $request->input('trang_thai'));
        }

        $items = $query
            ->orderByDesc('n.NgayTao')
            ->orderBy('n.MaDK')
            ->paginate($perPage)
            ->withQueryString();

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        return view('PMGPLXOLD.danh-muc.hoc-vien', [
            'items' => $items,
            'khoaHocs' => $khoaHocs,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'ma_kh' => $request->input('ma_kh', ''),
                'hang_gplx' => $request->input('hang_gplx', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }
}
