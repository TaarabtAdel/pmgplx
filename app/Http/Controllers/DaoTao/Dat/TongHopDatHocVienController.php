<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatHocVienTongHop;
use App\Support\DaoTao\DatTongHopHocVienExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TongHopDatHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatDSPhienBoLoc::parseFilters($request);
        $canTongHop = ($filters['ma_khoa_hoc'] ?? '') !== '';

        $items = null;
        $dieuKienByHang = DatDieuKienDat::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get()
            ->keyBy('Hang');

        if ($canTongHop) {
            $aggregated = DatHocVienTongHop::aggregatedHocVien($filters, $dieuKienByHang);

            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 50;
            $items = new LengthAwarePaginator(
                $aggregated->forPage($page, $perPage)->values(),
                $aggregated->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $loaiKhoaHocOptions = DatDSPhien::query()
            ->when($canTongHop, fn ($q) => $q->where('MaKhoaHoc', $filters['ma_khoa_hoc']))
            ->whereNotNull('LoaiKhoaHoc')
            ->where('LoaiKhoaHoc', '!=', '')
            ->distinct()
            ->orderBy('LoaiKhoaHoc')
            ->pluck('LoaiKhoaHoc');

        $selectedHocVienOption = null;
        if ($filters['ma_hoc_vien'] !== '') {
            [$maHv, $maKh] = DatDSPhienBoLoc::parseMaHocVienFilter($filters['ma_hoc_vien']);
            $selectedQuery = DatDSPhien::query()
                ->selectRaw('MaHocVien, MaKhoaHoc, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc')
                ->where('MaHocVien', $maHv)
                ->groupBy('MaHocVien', 'MaKhoaHoc');

            if ($maKh !== '') {
                $selectedQuery->where('MaKhoaHoc', $maKh);
            }

            $selectedRow = $selectedQuery->first();
            if ($selectedRow) {
                $selectedHocVienOption = [
                    'id' => DatDSPhienBoLoc::composeMaHocVienValue(
                        (string) $selectedRow->MaHocVien,
                        (string) ($selectedRow->MaKhoaHoc ?? '')
                    ),
                    'text' => self::formatHocVienOptionText($selectedRow),
                ];
            }
        }

        return view('DaoTao.dat.tong-hop-hoc-vien', [
            'items' => $items,
            'canTongHop' => $canTongHop,
            'filters' => $filters,
            'exportQuery' => array_filter([
                'ma_hoc_vien' => $filters['ma_hoc_vien'] ?? '',
                'ma_khoa_hoc' => $filters['ma_khoa_hoc'] ?? '',
                'loai_khoa_hoc' => $filters['loai_khoa_hoc'] ?? '',
                'tu_ngay' => $filters['tu_ngay'] ?? '',
                'den_ngay' => $filters['den_ngay'] ?? '',
                'dat_ct' => $filters['dat_ct'] ?? '',
            ], fn ($v) => $v !== null && $v !== ''),
            'khoaHocOptions' => $khoaHocOptions,
            'loaiKhoaHocOptions' => $loaiKhoaHocOptions,
            'selectedHocVienOption' => $selectedHocVienOption,
            'dieuKienByHang' => $dieuKienByHang,
            'apDungTuNgay' => DatDieuKienDat::AP_DUNG_TU_NGAY,
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $filters = DatDSPhienBoLoc::parseFilters($request);

        if (($filters['ma_khoa_hoc'] ?? '') === '') {
            return redirect()
                ->route('daotao.pdt.dat.tong-hop-hoc-vien')
                ->with('error', 'Chọn mã khóa học trước khi xuất Excel.');
        }

        $dieuKienByHang = DatDieuKienDat::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get()
            ->keyBy('Hang');

        $rows = DatHocVienTongHop::aggregatedHocVien($filters, $dieuKienByHang);

        return DatTongHopHocVienExcelExporter::download($rows, $filters, $dieuKienByHang);
    }

    private static function formatHocVienOptionText(object $row): string
    {
        $maHv = (string) ($row->MaHocVien ?? '');
        $ten = trim((string) ($row->HoTenHocVien ?? ''));
        $tenKh = trim((string) ($row->TenKhoaHoc ?? ''));
        $maKh = trim((string) ($row->MaKhoaHoc ?? ''));

        $label = $ten !== '' ? $ten.' ('.$maHv.')' : $maHv;
        $khoa = $tenKh !== '' ? $tenKh : $maKh;

        if ($khoa !== '') {
            $label .= ' — '.$khoa;
        }

        return $label;
    }
}
