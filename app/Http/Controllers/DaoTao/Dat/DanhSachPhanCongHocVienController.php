<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\GiaoVien;
use App\Support\DaoTao\DatPhanCongGiaoVienThayResolver;
use App\Support\DaoTao\DatPhanCongGiaoVienThaySaver;
use App\Support\DaoTao\DatPhanCongHocVienBoLoc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DanhSachPhanCongHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatPhanCongHocVienBoLoc::parseFilters($request);

        $items = DatPhanCongHocVienBoLoc::filteredQuery($filters)
            ->paginate($filters['per_page'])
            ->withQueryString();

        $selectedKhoa = trim((string) ($filters['ma_khoa_hoc'] ?? ''));
        $substitutesByKey = $selectedKhoa !== ''
            ? DatPhanCongGiaoVienThayResolver::groupedForCourses([$selectedKhoa])
            : [];

        $giaoVienOptions = DatPhanCongHocVienBoLoc::distinctValues('MaGiaoVien');
        $maGvCodes = array_values(array_unique(array_merge(
            $giaoVienOptions,
            $items->pluck('MaGiaoVien')->filter()->all(),
            collect($substitutesByKey)
                ->flatten(1)
                ->pluck('ma_giao_vien')
                ->filter()
                ->all()
        )));

        $giaoVienNames = $maGvCodes === []
            ? collect()
            : GiaoVien::query()
                ->whereIn('MaGV', $maGvCodes)
                ->get(['MaGV', 'HoTenDem', 'TenGV'])
                ->keyBy('MaGV');

        $giaoVienSelectOptions = GiaoVien::query()
            ->orderBy('HoTenDem')
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'HoTenDem', 'TenGV']);

        $giaoVienGocRows = [];
        if ($selectedKhoa !== '') {
            $giaoVienGocRows = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $selectedKhoa)
                ->selectRaw('MaGiaoVien, COUNT(*) as so_hv')
                ->groupBy('MaGiaoVien')
                ->orderBy('MaGiaoVien')
                ->get()
                ->map(function ($row) use ($selectedKhoa, $substitutesByKey) {
                    $maGiaoVienGoc = trim((string) ($row->MaGiaoVien ?? ''));
                    $key = DatPhanCongGiaoVienThayResolver::courseMainGvKey($selectedKhoa, $maGiaoVienGoc);
                    $substitutes = $substitutesByKey[$key] ?? [];

                    return [
                        'ma_khoa_hoc' => $selectedKhoa,
                        'ma_giao_vien_goc' => $maGiaoVienGoc,
                        'so_hv' => (int) ($row->so_hv ?? 0),
                        'so_khai_bao' => count($substitutes),
                        'substitutes' => $substitutes,
                    ];
                })
                ->all();
        }

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
            'giaoVienSelectOptions' => $giaoVienSelectOptions,
            'selectedKhoa' => $selectedKhoa,
            'giaoVienGocRows' => $giaoVienGocRows,
        ]);
    }

    public function storeGiaoVienThay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ma_giao_vien_goc' => ['required', 'string', 'max:50'],
            'ma_giao_vien' => ['required', 'string', 'max:50'],
            'tu_ngay' => ['required', 'date'],
            'den_ngay' => ['nullable', 'date'],
        ], [
            'ma_khoa_hoc.required' => 'Chọn mã khóa học.',
            'ma_giao_vien_goc.required' => 'Chọn giáo viên được dạy thay.',
            'ma_giao_vien.required' => 'Nhập mã giáo viên dạy thay.',
            'tu_ngay.required' => 'Nhập từ ngày.',
        ]);

        try {
            DatPhanCongGiaoVienThaySaver::create(
                trim((string) $validated['ma_khoa_hoc']),
                trim((string) $validated['ma_giao_vien_goc']),
                trim((string) $validated['ma_giao_vien']),
                (string) $validated['tu_ngay'],
                isset($validated['den_ngay']) ? (string) $validated['den_ngay'] : null
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return back()->withInput()->withErrors($e->errors());
            }

            return back()->withInput()->with('error', 'Lưu giáo viên dạy thay thất bại: '.$e->getMessage());
        }

        return back()->with('success', 'Đã thêm giáo viên dạy thay.');
    }

    public function destroyGiaoVienThay(Request $request, int $id): RedirectResponse
    {
        try {
            DatPhanCongGiaoVienThaySaver::delete($id);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return back()->withErrors($e->errors());
            }

            return back()->with('error', 'Xóa giáo viên dạy thay thất bại: '.$e->getMessage());
        }

        return back()->with('success', 'Đã xóa giáo viên dạy thay.');
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

        DatPhanCongGiaoVienThaySaver::deleteByKhoaHoc($maKhoaHoc);

        return redirect()
            ->route('daotao.pdt.dat.phan-cong-hoc-vien')
            ->with('success', 'Đã xóa '.number_format($deleted).' phân công của khóa '.$maKhoaHoc.'.');
    }
}
