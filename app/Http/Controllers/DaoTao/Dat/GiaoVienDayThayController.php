<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\GiaoVien;
use App\Support\DaoTao\DatGiaoVienKhoaTeachingSpan;
use App\Support\DaoTao\DatPhanCongGiaoVienThayResolver;
use App\Support\DaoTao\DatPhanCongGiaoVienThaySaver;
use App\Support\DaoTao\DatPhanCongGiaoVienThayLichApplier;
use App\Support\DaoTao\DatPhanCongHocVienBoLoc;
use App\Models\DaoTao\DatPhanCongGiaoVienThay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class GiaoVienDayThayController extends Controller
{
    public function index(Request $request): View
    {
        $selectedKhoa = trim((string) $request->query('ma_khoa_hoc', old('ma_khoa_hoc', '')));
        $khoaHocOptions = DatPhanCongHocVienBoLoc::khoaHocOptions();

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
                        'teaching_spans' => DatGiaoVienKhoaTeachingSpan::spansForCourseGiaoVien(
                            $selectedKhoa,
                            $maGiaoVienGoc
                        ),
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

        $coKhaiBaoThay = $selectedKhoa !== ''
            && DatPhanCongGiaoVienThay::query()->where('MaKhoaHoc', $selectedKhoa)->exists();

        return view('DaoTao.dat.giao-vien-day-thay', [
            'selectedKhoa' => $selectedKhoa,
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienGocRows' => $giaoVienGocRows,
            'giaoVienNames' => $giaoVienNames,
            'giaoVienSelectOptions' => $giaoVienSelectOptions,
            'coKhaiBaoThay' => $coKhaiBaoThay,
        ]);
    }

    public function previewApplyLich(Request $request): View|RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        try {
            $preview = (new DatPhanCongGiaoVienThayLichApplier())->preview($maKhoaHoc);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->redirectToIndex($maKhoaHoc)->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)->with('error', $e->getMessage());
        }

        return view('DaoTao.dat.giao-vien-day-thay-ap-dung-lich', [
            'preview' => $preview,
        ]);
    }

    public function applyLich(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
        ]);

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);

        try {
            $result = (new DatPhanCongGiaoVienThayLichApplier())->apply($maKhoaHoc);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return redirect()
                    ->route('daotao.pdt.dat.giao-vien-day-thay.preview-apply-lich', ['ma_khoa_hoc' => $maKhoaHoc])
                    ->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)->with('error', 'Áp dụng lịch thất bại: '.$e->getMessage());
        }

        return redirect()
            ->route('daotao.pdt.dat.giao-vien-day-thay.preview-apply-lich', ['ma_khoa_hoc' => $maKhoaHoc])
            ->with(
                'success',
                'Đã cập nhật lịch PMGPLX: '
                .number_format($result['gv_updated']).' dòng GV (KhoaHoc_GiaoVien), '
                .number_format($result['xe_updated']).' dòng xe (KhoaHoc_XeTap).'
            );
    }

    public function applyKhaiBaoLich(Request $request, int $id): RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        $item = DatPhanCongGiaoVienThay::query()->find($id);
        if ($item === null) {
            return $this->redirectToIndex($maKhoaHoc)->with('error', 'Khai báo dạy thay không còn tồn tại.');
        }

        try {
            $result = (new DatPhanCongGiaoVienThayLichApplier())->applyKhaiBao($item);
        } catch (Throwable $e) {
            return $this->redirectToIndex($maKhoaHoc)->with('error', 'Áp dụng lịch thất bại: '.$e->getMessage());
        }

        $gvLich = (int) ($result['gv_updated'] ?? 0);
        $xeLich = (int) ($result['xe_updated'] ?? 0);
        $message = 'Đã áp dụng khai báo vào lịch PMGPLX: '
            .number_format($gvLich).' dòng GV, '
            .number_format($xeLich).' dòng xe.';
        if ($gvLich + $xeLich === 0) {
            $message = 'Không có dòng lịch nào cập nhật (có thể đã áp dụng hoặc không trùng ngày / GV gốc trên lịch).';
        }

        return $this->redirectToIndex($maKhoaHoc)->with('success', $message);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ma_giao_vien_goc' => ['required', 'string', 'max:50'],
            'ma_giao_vien' => ['required', 'string', 'max:50'],
            'tu_ngay' => ['required', 'date'],
            'den_ngay' => ['nullable', 'date'],
            'tu_gio' => ['nullable', 'date_format:H:i'],
            'den_gio' => ['nullable', 'date_format:H:i'],
        ], [
            'ma_khoa_hoc.required' => 'Chọn mã khóa học.',
            'ma_giao_vien_goc.required' => 'Chọn giáo viên được dạy thay.',
            'ma_giao_vien.required' => 'Nhập mã giáo viên dạy thay.',
            'tu_ngay.required' => 'Nhập từ ngày.',
            'tu_gio.date_format' => 'Giờ bắt đầu không hợp lệ (HH:MM).',
            'den_gio.date_format' => 'Giờ kết thúc không hợp lệ (HH:MM).',
        ]);

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);

        try {
            $created = DatPhanCongGiaoVienThaySaver::create(
                $maKhoaHoc,
                trim((string) $validated['ma_giao_vien_goc']),
                trim((string) $validated['ma_giao_vien']),
                (string) $validated['tu_ngay'],
                isset($validated['den_ngay']) ? (string) $validated['den_ngay'] : null,
                $validated['tu_gio'] ?? null,
                $validated['den_gio'] ?? null
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

        $message = 'Đã thêm giáo viên dạy thay.';
        if ($request->boolean('ap_dung_lich')) {
            $lich = (new DatPhanCongGiaoVienThayLichApplier())->applyKhaiBao($created['item']);
            $gvLich = (int) ($lich['gv_updated'] ?? 0);
            $xeLich = (int) ($lich['xe_updated'] ?? 0);
            if ($gvLich + $xeLich > 0) {
                $message .= ' Đã áp dụng lịch PMGPLX: '
                    .number_format($gvLich).' dòng GV, '
                    .number_format($xeLich).' dòng xe.';
            } else {
                $message .= ' Chưa cập nhật dòng lịch nào (kiểm tra ngày / GV gốc trên lịch hoặc dùng Áp dụng vào lịch).';
            }
        }

        return $this->redirectToIndex($maKhoaHoc)->with('success', $message);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        try {
            $result = DatPhanCongGiaoVienThaySaver::delete($id);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->redirectToIndex($maKhoaHoc)->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)
                ->with('error', 'Xóa giáo viên dạy thay thất bại: '.$e->getMessage());
        }

        $message = 'Đã xóa giáo viên dạy thay.';
        $gvLich = (int) ($result['gv_lich_reverted'] ?? 0);
        $xeLich = (int) ($result['xe_lich_reverted'] ?? 0);
        if ($gvLich + $xeLich > 0) {
            $message .= ' Hoàn lịch PMGPLX: '
                .number_format($gvLich).' dòng GV, '
                .number_format($xeLich).' dòng xe.';
        }

        return $this->redirectToIndex($maKhoaHoc)
            ->with('success', $message);
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
