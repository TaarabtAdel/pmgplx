<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLXOLD\KhoaHoc as KhoaHocOld;
use App\Support\PMGPLX\KhoaHocBanCuTaoMoi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DanhSachKhoaHocBanCuController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $soHocVienOld = DB::connection('sqlsrv_old')
            ->table('NguoiLX_HoSo')
            ->selectRaw('COUNT(*)')
            ->whereColumn('NguoiLX_HoSo.MaKhoaHoc', 'KhoaHoc.MaKH');

        $query = KhoaHocOld::query()
            ->select('KhoaHoc.*')
            ->selectSub($soHocVienOld, 'so_hoc_vien');

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

        $items = $query
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->paginate($perPage)
            ->withQueryString();

        $hangOptions = KhoaHocOld::query()
            ->whereNotNull('HangDT')
            ->where('HangDT', '!=', '')
            ->distinct()
            ->orderBy('HangDT')
            ->pluck('HangDT');

        return view('PMGPLX.danh-muc.khoa-hoc-ban-cu', [
            'items' => $items,
            'hangOptions' => $hangOptions,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'hang_dt' => $request->input('hang_dt', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request, KhoaHocBanCuTaoMoi $taoMoi): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ten_kh' => ['required', 'string', 'max:255'],
            'hang_gplx' => ['nullable', 'string', 'max:20'],
            'hang_dt' => ['nullable', 'string', 'max:20'],
            'ngay_kg' => ['nullable', 'date'],
            'ngay_bg' => ['nullable', 'date'],
        ], [
            'ma_kh.required' => 'Nhập mã khoá (bản cũ không tự sinh mã).',
            'ten_kh.required' => 'Nhập tên khoá.',
        ]);

        $validated['trang_thai'] = $request->has('trang_thai');

        try {
            $taoMoi->tao($validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không tạo được khoá bản cũ: '.$e->getMessage());
        }

        return redirect()
            ->route('pmgplx.dm.khoa-hoc-ban-cu.index', ['tu_khoa' => $validated['ma_kh']])
            ->with('success', 'Đã tạo khoá bản cũ «'.$validated['ten_kh'].'» — mã '.$validated['ma_kh'].'.');
    }
}