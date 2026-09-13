<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DaoTao\Dat\Concerns\LoadsDatPhienFilterOptions;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatAnhDuongDan;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatPhienAnhLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DoPhienAnhController extends Controller
{
    use LoadsDatPhienFilterOptions;

    public function index(Request $request): View
    {
        $filters = DatDSPhienBoLoc::parseFilters($request);
        $maKhoaHoc = $filters['ma_khoa_hoc'];
        $canAnalyze = $maKhoaHoc !== '';
        $filterOptions = $this->loadDatPhienFilterOptions($filters);
        $selectedKhoaHocLabel = '';

        $items = null;

        if ($canAnalyze) {
            $query = DatDSPhienBoLoc::filteredQuery($filters);
            $items = $query->paginate(50)->withQueryString();
            $selectedKhoa = $filterOptions['khoaHocOptions']->firstWhere('MaKhoaHoc', $maKhoaHoc);
            $selectedKhoaHocLabel = $this->formatKhoaHocLabel($selectedKhoa);
        }

        return view('DaoTao.dat.do-phien-anh', [
            'items' => $items,
            'filters' => $filters,
            'canAnalyze' => $canAnalyze,
            'selectedKhoaHocLabel' => $selectedKhoaHocLabel,
            'khoaHocOptions' => $filterOptions['khoaHocOptions'],
            'giaoVienOptions' => $filterOptions['giaoVienOptions'],
            'loaiKhoaHocOptions' => $filterOptions['loaiKhoaHocOptions'],
            'selectedHocVienOption' => $filterOptions['selectedHocVienOption'],
            'hasAnhDuongDan' => DatAnhDuongDan::has(),
            'anhDuongDan' => DatAnhDuongDan::get(),
            'anhDuongDanPreview' => DatAnhDuongDan::preview(),
        ]);
    }

    public function luuDuongDan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'duong_dan_anh' => ['required', 'string', 'min:3', 'max:2048'],
        ], [
            'duong_dan_anh.required' => 'Nhập đường dẫn ảnh.',
            'duong_dan_anh.min' => 'Đường dẫn ảnh quá ngắn.',
        ]);

        DatAnhDuongDan::store($validated['duong_dan_anh']);

        return back()->with('success', 'Đã lưu đường dẫn ảnh cho phiên làm việc hiện tại.');
    }

    public function xoaDuongDan(): RedirectResponse
    {
        DatAnhDuongDan::forget();

        return back()->with('success', 'Đã xóa đường dẫn ảnh khỏi phiên làm việc.');
    }

    public function taiAnh(int $id, DatPhienAnhLocator $locator): JsonResponse
    {
        if (! DatAnhDuongDan::has()) {
            return response()->json([
                'ok' => false,
                'message' => 'Chưa cấu hình đường dẫn ảnh.',
            ], 422);
        }

        $phien = DatDSPhien::query()->find($id);
        if ($phien === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Phiên không tồn tại.',
            ], 404);
        }

        $result = $locator->forPhien($phien, DatAnhDuongDan::get());
        $status = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json($result, $status);
    }

    public function xemAnh(int $id, string $thang, string $ngay, string $ten, DatPhienAnhLocator $locator): BinaryFileResponse
    {
        abort_unless(DatAnhDuongDan::has(), 404);

        $phien = DatDSPhien::query()->find($id);
        abort_if($phien === null, 404);

        $path = $locator->absoluteFilePath($phien, DatAnhDuongDan::get(), $thang, $ngay, $ten);
        abort_if($path === null, 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function formatKhoaHocLabel(?object $khoa): string
    {
        if ($khoa === null) {
            return '';
        }

        $ma = trim((string) ($khoa->MaKhoaHoc ?? ''));
        $ten = trim((string) ($khoa->TenKhoaHoc ?? ''));

        if ($ten !== '') {
            return $ten.' ('.$ma.')';
        }

        return $ma;
    }
}
