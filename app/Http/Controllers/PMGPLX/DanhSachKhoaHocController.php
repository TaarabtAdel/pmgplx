<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DanhSachKhoaHocController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $soHocVien = DB::connection('sqlsrv')
            ->table('NguoiLX_HoSo')
            ->selectRaw('COUNT(*)')
            ->whereColumn('NguoiLX_HoSo.MaKhoaHoc', 'KhoaHoc.MaKH');

        $query = KhoaHoc::query()
            ->select('KhoaHoc.*')
            ->selectSub($soHocVien, 'so_hoc_vien');

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $like = '%'.$keyword.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('MaKH', 'like', $like)
                    ->orWhere('TenKH', 'like', $like)
                    ->orWhere('SoQD_KhaiGiang', 'like', $like);
            });
        }

        if ($hang = trim((string) $request->input('hang_dt'))) {
            $query->where('HangDT', $hang);
        }

        if ($request->filled('trang_thai')) {
            $query->where('TrangThai', (int) $request->input('trang_thai'));
        }

        if ($tuNgay = trim((string) $request->input('tu_ngay'))) {
            $query->whereDate('NgayKG', '>=', $tuNgay);
        }

        if ($denNgay = trim((string) $request->input('den_ngay'))) {
            $query->whereDate('NgayKG', '<=', $denNgay);
        }

        $items = $query
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->paginate($perPage)
            ->withQueryString();

        $hangOptions = KhoaHoc::query()
            ->whereNotNull('HangDT')
            ->where('HangDT', '!=', '')
            ->distinct()
            ->orderBy('HangDT')
            ->pluck('HangDT');

        return view('PMGPLX.danh-muc.khoa-hoc', [
            'items' => $items,
            'hangOptions' => $hangOptions,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'hang_dt' => $request->input('hang_dt', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'tu_ngay' => $request->input('tu_ngay', ''),
                'den_ngay' => $request->input('den_ngay', ''),
                'per_page' => $perPage,
            ],
        ]);
    }
}
