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

class GiaoVienDayThayController extends Controller
{
    public function index(Request $request): View
    {
        $selectedKhoa = trim((string) $request->query('ma_khoa_hoc', old('ma_khoa_hoc', '')));
        $khoaHocOptions = DatPhanCongHocVienBoLoc::distinctValues('MaKhoaHoc');

        $substitutesByKey = $selectedKhoa !== ''
            ? DatPhanCongGiaoVienThayResolver::groupedForCourses([$selectedKhoa])
            : [];

        $giaoVienGocRows = [];
        $maGvCodes = [];

        if ($selectedKhoa !== '') {
            $giaoVienGocRows = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $selectedKhoa)
                ->selectRaw('MaGiaoVien, COUNT(*) as so_hv')
                ->groupBy('MaGiaoVien')
                ->orderBy('MaGiaoVien')
                ->get()
                ->map(function ($row) use ($selectedKhoa, $substitutesByKey, &$maGvCodes) {
                    $maGiaoVienGoc = trim((string) ($row->MaGiaoVien ?? ''));
                    if ($maGiaoVienGoc !== '') {
                        $maGvCodes[] = $maGiaoVienGoc;
                    }

                    $key = DatPhanCongGiaoVienThayResolver::courseMainGvKey($selectedKhoa, $maGiaoVienGoc);
                    $substitutes = $substitutesByKey[$key] ?? [];

                    foreach ($substitutes as $substitute) {
                        $maGv = trim((string) ($substitute['ma_giao_vien'] ?? ''));
                        if ($maGv !== '') {
                            $maGvCodes[] = $maGv;
                        }
                    }

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

        $maGvCodes = array_values(array_unique($maGvCodes));
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

        return view('DaoTao.dat.giao-vien-day-thay', [
            'selectedKhoa' => $selectedKhoa,
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienGocRows' => $giaoVienGocRows,
            'giaoVienNames' => $giaoVienNames,
            'giaoVienSelectOptions' => $giaoVienSelectOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);

        try {
            DatPhanCongGiaoVienThaySaver::create(
                $maKhoaHoc,
                trim((string) $validated['ma_giao_vien_goc']),
                trim((string) $validated['ma_giao_vien']),
                (string) $validated['tu_ngay'],
                isset($validated['den_ngay']) ? (string) $validated['den_ngay'] : null
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->redirectToIndex($maKhoaHoc)
                    ->withInput()
                    ->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)
                ->withInput()
                ->with('error', 'Lưu giáo viên dạy thay thất bại: '.$e->getMessage());
        }

        return $this->redirectToIndex($maKhoaHoc)
            ->with('success', 'Đã thêm giáo viên dạy thay.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        try {
            DatPhanCongGiaoVienThaySaver::delete($id);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->redirectToIndex($maKhoaHoc)->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)
                ->with('error', 'Xóa giáo viên dạy thay thất bại: '.$e->getMessage());
        }

        return $this->redirectToIndex($maKhoaHoc)
            ->with('success', 'Đã xóa giáo viên dạy thay.');
    }

    private function redirectToIndex(string $maKhoaHoc): RedirectResponse
    {
        $maKhoaHoc = trim($maKhoaHoc);

        return redirect()->route(
            'daotao.pdt.dat.giao-vien-day-thay',
            $maKhoaHoc !== '' ? ['ma_khoa_hoc' => $maKhoaHoc] : []
        );
    }
}
