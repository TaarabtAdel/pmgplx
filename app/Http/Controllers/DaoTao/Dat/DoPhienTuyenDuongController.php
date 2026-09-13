<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DaoTao\Dat\Concerns\LoadsDatPhienFilterOptions;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatTuyenDuongTuXeOnline;
use App\Support\DaoTao\DatXeOnlineBearerToken;
use App\Support\PMGPLX\LichExcelDiaDiem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoPhienTuyenDuongController extends Controller
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

        return view('DaoTao.dat.do-phien-tuyen-duong', [
            'items' => $items,
            'filters' => $filters,
            'canAnalyze' => $canAnalyze,
            'selectedKhoaHocLabel' => $selectedKhoaHocLabel,
            'khoaHocOptions' => $filterOptions['khoaHocOptions'],
            'giaoVienOptions' => $filterOptions['giaoVienOptions'],
            'loaiKhoaHocOptions' => $filterOptions['loaiKhoaHocOptions'],
            'selectedHocVienOption' => $filterOptions['selectedHocVienOption'],
            'diaDiemTuyenDuong' => LichExcelDiaDiem::TUYEN_DUONG,
            'hasXeOnlineBearer' => DatXeOnlineBearerToken::has(),
            'xeOnlineBearerPreview' => DatXeOnlineBearerToken::maskedPreview(),
            'xeOnlineApiBaseUrl' => config('services.xeonline.base_url'),
        ]);
    }

    public function luuBearerToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bearer_token' => ['required', 'string', 'min:20'],
        ], [
            'bearer_token.required' => 'Nhập Bearer token.',
            'bearer_token.min' => 'Bearer token quá ngắn.',
        ]);

        DatXeOnlineBearerToken::store($validated['bearer_token']);

        return back()->with('success', 'Đã lưu Bearer token cho phiên làm việc hiện tại.');
    }

    public function xoaBearerToken(): RedirectResponse
    {
        DatXeOnlineBearerToken::forget();

        return back()->with('success', 'Đã xóa Bearer token khỏi phiên làm việc.');
    }

    public function tienHanhDo(int $id, DatTuyenDuongTuXeOnline $resolver): JsonResponse
    {
        $phien = DatDSPhien::query()->find($id);
        if ($phien === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Phiên không tồn tại.',
            ], 404);
        }

        $result = $resolver->forPhien($phien);
        $status = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json($result, $status);
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
