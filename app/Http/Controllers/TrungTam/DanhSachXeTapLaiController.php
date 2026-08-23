<?php

namespace App\Http\Controllers\TrungTam;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\XeTapLai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachXeTapLaiController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 50);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 50;
        }

        $filters = [
            'tu_khoa' => trim((string) ($request->input('tu_khoa') ?? '')),
            'trang_thai' => (string) ($request->input('trang_thai') ?? ''),
            'per_page' => $perPage,
        ];

        $query = XeTapLai::query();

        if ($filters['tu_khoa'] !== '') {
            $keyword = $filters['tu_khoa'];
            $query->where(function ($q) use ($keyword): void {
                $q->where('BienSo', 'like', '%'.$keyword.'%')
                    ->orWhere('LoaiXe', 'like', '%'.$keyword.'%')
                    ->orWhere('HangXe', 'like', '%'.$keyword.'%');
            });
        }

        if ($filters['trang_thai'] !== '') {
            $query->where('TrangThai', (int) $filters['trang_thai']);
        }

        $items = $query
            ->orderBy('BienSo')
            ->paginate($perPage)
            ->withQueryString();

        return view('TrungTam.xe-tap-lai.danh-sach', [
            'items' => $items,
            'filters' => $filters,
        ]);
    }
}
