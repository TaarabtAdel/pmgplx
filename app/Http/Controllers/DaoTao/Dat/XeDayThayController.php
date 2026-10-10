<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\DaoTao\DatPhanCongXeThay;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\XeTap;
use App\Support\DaoTao\DatGiaoVienKhoaTeachingSpan;
use App\Support\DaoTao\DatPhanCongHocVienBoLoc;
use App\Support\DaoTao\DatPhanCongXeThayLichApplier;
use App\Support\DaoTao\DatPhanCongXeThayResolver;
use App\Support\DaoTao\DatPhanCongXeThaySaver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class XeDayThayController extends Controller
{
    public function index(Request $request): View
    {
        $selectedKhoa = trim((string) $request->query('ma_khoa_hoc', old('ma_khoa_hoc', '')));
        $khoaHocOptions = DatPhanCongHocVienBoLoc::khoaHocOptions();

        $substitutesByKey = $selectedKhoa !== ''
            ? DatPhanCongXeThayResolver::groupedForCourses([$selectedKhoa])
            : [];

        $xeGocRows = [];
        $maGvCodes = [];

        if ($selectedKhoa !== '') {
            $xeGocRows = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $selectedKhoa)
                ->selectRaw('MaGiaoVien, BienSoXe, COUNT(*) as so_hv')
                ->groupBy('MaGiaoVien', 'BienSoXe')
                ->orderBy('MaGiaoVien')
                ->orderBy('BienSoXe')
                ->get()
                ->map(function ($row) use ($selectedKhoa, $substitutesByKey, &$maGvCodes) {
                    $maGiaoVienGoc = trim((string) ($row->MaGiaoVien ?? ''));
                    $bienSoXeGoc = trim((string) ($row->BienSoXe ?? ''));
                    if ($maGiaoVienGoc !== '') {
                        $maGvCodes[] = $maGiaoVienGoc;
                    }

                    $key = DatPhanCongXeThayResolver::courseMainGvXeKey($selectedKhoa, $maGiaoVienGoc, $bienSoXeGoc);
                    $substitutes = $substitutesByKey[$key] ?? [];

                    return [
                        'ma_khoa_hoc' => $selectedKhoa,
                        'ma_giao_vien_goc' => $maGiaoVienGoc,
                        'bien_so_xe_goc' => $bienSoXeGoc,
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

        $xeSelectOptions = self::collectXeSelectOptions($selectedKhoa);

        $coKhaiBaoThay = $selectedKhoa !== ''
            && DatPhanCongXeThay::query()->where('MaKhoaHoc', $selectedKhoa)->exists();

        return view('DaoTao.dat.xe-day-thay', [
            'selectedKhoa' => $selectedKhoa,
            'khoaHocOptions' => $khoaHocOptions,
            'xeGocRows' => $xeGocRows,
            'giaoVienNames' => $giaoVienNames,
            'xeSelectOptions' => $xeSelectOptions,
            'coKhaiBaoThay' => $coKhaiBaoThay,
        ]);
    }

    public function previewApplyLich(Request $request): View|RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        try {
            $preview = (new DatPhanCongXeThayLichApplier())->preview($maKhoaHoc);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->redirectToIndex($maKhoaHoc)->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)->with('error', $e->getMessage());
        }

        return view('DaoTao.dat.xe-day-thay-ap-dung-lich', [
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
            $result = (new DatPhanCongXeThayLichApplier())->apply($maKhoaHoc);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return redirect()
                    ->route('daotao.pdt.dat.xe-day-thay.preview-apply-lich', ['ma_khoa_hoc' => $maKhoaHoc])
                    ->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)->with('error', 'Áp dụng lịch thất bại: '.$e->getMessage());
        }

        return redirect()
            ->route('daotao.pdt.dat.xe-day-thay.preview-apply-lich', ['ma_khoa_hoc' => $maKhoaHoc])
            ->with(
                'success',
                'Đã cập nhật lịch PMGPLX: '
                .number_format($result['gv_bien_updated']).' dòng GV (cột BienSoXe), '
                .number_format($result['xe_updated']).' dòng xe (KhoaHoc_XeTap).'
            );
    }

    public function applyKhaiBaoLich(Request $request, int $id): RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        $item = DatPhanCongXeThay::query()->find($id);
        if ($item === null) {
            return $this->redirectToIndex($maKhoaHoc)->with('error', 'Khai báo xe thay không còn tồn tại.');
        }

        try {
            $result = (new DatPhanCongXeThayLichApplier())->applyKhaiBao($item);
        } catch (Throwable $e) {
            return $this->redirectToIndex($maKhoaHoc)->with('error', 'Áp dụng lịch thất bại: '.$e->getMessage());
        }

        $gvBien = (int) ($result['gv_bien_updated'] ?? 0);
        $xeLich = (int) ($result['xe_updated'] ?? 0);
        $message = 'Đã áp dụng khai báo vào lịch PMGPLX: '
            .number_format($gvBien).' dòng GV (biển), '
            .number_format($xeLich).' dòng xe.';
        if ($gvBien + $xeLich === 0) {
            $message = 'Không có dòng lịch nào cập nhật (có thể đã áp dụng hoặc không trùng ngày / xe gốc trên lịch).';
        }

        return $this->redirectToIndex($maKhoaHoc)->with('success', $message);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ma_giao_vien_goc' => ['required', 'string', 'max:50'],
            'bien_so_xe_goc' => ['required', 'string', 'max:50'],
            'bien_so_xe' => ['required', 'string', 'max:50'],
            'tu_ngay' => ['required', 'date'],
            'den_ngay' => ['nullable', 'date'],
        ], [
            'ma_khoa_hoc.required' => 'Chọn mã khóa học.',
            'ma_giao_vien_goc.required' => 'Chọn giáo viên chính.',
            'bien_so_xe_goc.required' => 'Chọn xe gốc.',
            'bien_so_xe.required' => 'Nhập biển số xe thay.',
            'tu_ngay.required' => 'Nhập từ ngày.',
        ]);

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);

        try {
            $created = DatPhanCongXeThaySaver::create(
                $maKhoaHoc,
                trim((string) $validated['ma_giao_vien_goc']),
                trim((string) $validated['bien_so_xe_goc']),
                trim((string) $validated['bien_so_xe']),
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
                ->with('error', 'Lưu xe thay thất bại: '.$e->getMessage());
        }

        $message = 'Đã thêm xe thay.';
        if ($request->boolean('ap_dung_lich')) {
            $lich = (new DatPhanCongXeThayLichApplier())->applyKhaiBao($created['item']);
            $gvBien = (int) ($lich['gv_bien_updated'] ?? 0);
            $xeLich = (int) ($lich['xe_updated'] ?? 0);
            if ($gvBien + $xeLich > 0) {
                $message .= ' Đã áp dụng lịch PMGPLX: '
                    .number_format($gvBien).' dòng GV (biển), '
                    .number_format($xeLich).' dòng xe.';
            } else {
                $message .= ' Chưa cập nhật dòng lịch nào (kiểm tra ngày / xe gốc trên lịch hoặc dùng Áp dụng vào lịch).';
            }
        }

        return $this->redirectToIndex($maKhoaHoc)->with('success', $message);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $maKhoaHoc = trim((string) $request->query('ma_khoa_hoc', ''));

        try {
            $result = DatPhanCongXeThaySaver::delete($id);
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->redirectToIndex($maKhoaHoc)->withErrors($e->errors());
            }

            return $this->redirectToIndex($maKhoaHoc)
                ->with('error', 'Xóa xe thay thất bại: '.$e->getMessage());
        }

        $message = 'Đã xóa xe thay.';
        $gvBien = (int) ($result['gv_bien_lich_reverted'] ?? 0);
        $xeLich = (int) ($result['xe_lich_reverted'] ?? 0);
        if ($gvBien + $xeLich > 0) {
            $message .= ' Hoàn lịch PMGPLX: '
                .number_format($gvBien).' dòng GV (biển), '
                .number_format($xeLich).' dòng xe.';
        }

        return $this->redirectToIndex($maKhoaHoc)
            ->with('success', $message);
    }

    private function redirectToIndex(string $maKhoaHoc): RedirectResponse
    {
        $maKhoaHoc = trim($maKhoaHoc);

        return redirect()->route(
            'daotao.pdt.dat.xe-day-thay',
            $maKhoaHoc !== '' ? ['ma_khoa_hoc' => $maKhoaHoc] : []
        );
    }

    /**
     * @return list<string>
     */
    private static function collectXeSelectOptions(string $maKhoaHoc): array
    {
        $plates = [];

        foreach (XeTap::query()->orderBy('BienSoXe')->pluck('BienSoXe') as $bienSo) {
            $bienSo = trim((string) $bienSo);
            if ($bienSo !== '') {
                $plates[$bienSo] = true;
            }
        }

        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc !== '') {
            $rows = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $maKhoaHoc)
                ->get(['BienSoXe', 'BienSoXeTuDong']);

            foreach ($rows as $row) {
                foreach (['BienSoXe', 'BienSoXeTuDong'] as $field) {
                    $bienSo = trim((string) ($row->{$field} ?? ''));
                    if ($bienSo !== '') {
                        $plates[$bienSo] = true;
                    }
                }
            }
        }

        $list = array_keys($plates);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }
}
