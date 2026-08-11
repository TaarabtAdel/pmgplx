<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;

use App\Models\PMGPLX\XeTap;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachXeController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $query = XeTap::query();

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('BienSoXe', 'like', '%'.$keyword.'%')
                    ->orWhere('NhanHieu', 'like', '%'.$keyword.'%')
                    ->orWhere('LoaiXe', 'like', '%'.$keyword.'%')
                    ->orWhere('HangXe', 'like', '%'.$keyword.'%')
                    ->orWhere('SoGPXTL', 'like', '%'.$keyword.'%');
            });
        }

        if ($hang = trim((string) $request->input('hang_gplx'))) {
            $query->where('HangGPLXXe', 'like', '%'.$hang.'%');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('TrangThai', (int) $request->input('trang_thai'));
        }

        $items = $query
            ->orderBy('BienSoXe')
            ->paginate($perPage)
            ->withQueryString();

        return view('PMGPLX.danh-muc.xe-tap', [
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
