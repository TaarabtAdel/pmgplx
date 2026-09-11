<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\GiaoVien;
use App\Support\DaoTao\DatPhanCongHocVienBoLoc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachPhanCongHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatPhanCongHocVienBoLoc::parseFilters($request);

        $items = DatPhanCongHocVienBoLoc::filteredQuery($filters)
            ->paginate($filters['per_page'])
            ->withQueryString();

        $giaoVienOptions = DatPhanCongHocVienBoLoc::distinctValues('MaGiaoVien');
        $maGvCodes = array_values(array_unique(array_merge(
            $giaoVienOptions,
            $items->pluck('MaGiaoVien')->filter()->all()
        )));

        $giaoVienNames = $maGvCodes === []
            ? collect()
            : GiaoVien::query()
                ->whereIn('MaGV', $maGvCodes)
                ->get(['MaGV', 'HoTenDem', 'TenGV'])
                ->keyBy('MaGV');

        return view('DaoTao.dat.phan-cong-hoc-vien', [
            'items' => $items,
            'filters' => $filters,
            'khoaHocOptions' => DatPhanCongHocVienBoLoc::distinctValues('MaKhoaHoc'),
            'khoaHocCounts' => DatPhanCongHocVien::query()
                ->selectRaw('MaKhoaHoc, COUNT(*) as cnt')
                ->groupBy('MaKhoaHoc')
                ->orderBy('MaKhoaHoc')
                ->pluck('cnt', 'MaKhoaHoc'),
            'giaoVienOptions' => $giaoVienOptions,
            'bienSoXeOptions' => DatPhanCongHocVienBoLoc::distinctValues('BienSoXe'),
            'giaoVienNames' => $giaoVienNames,
        ]);
    }

    public function destroyByCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
        ], [
            'ma_khoa_hoc.required' => 'Chọn mã khóa học cần xóa.',
        ]);

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);
        $deleted = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'Không có phân công nào cho khóa '.$maKhoaHoc.'.');
        }

        return redirect()
            ->route('daotao.pdt.dat.phan-cong-hoc-vien')
            ->with('success', 'Đã xóa '.number_format($deleted).' phân công của khóa '.$maKhoaHoc.'.');
    }
}
