<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\XeTap;
use Illuminate\Http\RedirectResponse;
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

        if ($bienSo = trim((string) $request->input('bien_so_xe'))) {
            $query->where('GhiChu', $bienSo);
        }

        $items = $query
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->paginate($perPage)
            ->withQueryString();

        $xeTaps = XeTap::query()
            ->orderBy('BienSoXe')
            ->pluck('BienSoXe');

        return view('PMGPLX.danh-muc.giao-vien', [
            'items' => $items,
            'xeTaps' => $xeTaps,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'hang_gplx' => $request->input('hang_gplx', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'bien_so_xe' => $request->input('bien_so_xe', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function ganXe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_gv' => ['required', 'string', 'max:8'],
            'bien_so_xe' => ['required', 'string', 'max:10'],
        ], [
            'ma_gv.required' => 'Thiếu mã giáo viên.',
            'bien_so_xe.required' => 'Vui lòng chọn xe.',
        ]);

        $gv = GiaoVien::query()->where('MaGV', $validated['ma_gv'])->first();
        if (! $gv) {
            return back()->with('error', 'Không tìm thấy giáo viên.');
        }

        $xeExists = XeTap::query()->where('BienSoXe', $validated['bien_so_xe'])->exists();
        if (! $xeExists) {
            return back()->with('error', 'Biển số xe không hợp lệ.');
        }

        GiaoVien::query()
            ->where('MaGV', $validated['ma_gv'])
            ->update([
                'GhiChu' => $validated['bien_so_xe'],
                'NgaySua' => now(),
            ]);

        return back()->with('success', "Đã gắn xe {$validated['bien_so_xe']} cho giáo viên {$validated['ma_gv']}.");
    }
}
