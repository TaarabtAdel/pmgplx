<?php

namespace App\Http\Controllers\TrungTam;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\GiaoVien;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachGiaoVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 50);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 50;
        }

        $filters = [
            'tu_khoa' => trim((string) ($request->input('tu_khoa') ?? '')),
            'loai_gv' => trim((string) ($request->input('loai_gv') ?? '')),
            'trang_thai' => (string) ($request->input('trang_thai') ?? ''),
            'per_page' => $perPage,
        ];

        $items = GiaoVien::filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return view('TrungTam.giao-vien.danh-sach', [
            'items' => $items,
            'filters' => $filters,
            'loaiGvOptions' => ['GVLT', 'GVTH'],
        ]);
    }
}
