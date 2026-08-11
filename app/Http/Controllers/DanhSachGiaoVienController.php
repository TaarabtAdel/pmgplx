<?php

namespace App\Http\Controllers;

use App\Models\GiaoVien;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachGiaoVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $query = GiaoVien::query();

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('MaGV', 'like', '%'.$keyword.'%')
                    ->orWhere('TenGV', 'like', '%'.$keyword.'%')
                    ->orWhere('HoTenDem', 'like', '%'.$keyword.'%')
                    ->orWhere('SoCMT', 'like', '%'.$keyword.'%')
                    ->orWhere('DienThoai', 'like', '%'.$keyword.'%');
            });
        }

        if ($hang = trim((string) $request->input('hang_gplx'))) {
            $query->where('HangGPLX', 'like', '%'.$hang.'%');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('TrangThai', (int) $request->input('trang_thai'));
        }

        $items = $query
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->paginate($perPage)
            ->withQueryString();

        return view('danh-muc.giao-vien', [
            'items' => $items,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'hang_gplx' => $request->input('hang_gplx', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }
}
