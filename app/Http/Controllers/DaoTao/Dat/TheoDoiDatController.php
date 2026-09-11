<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatTheoDoiDat;
use App\Support\DaoTao\DatTheoDoiDatExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TheoDoiDatController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatTheoDoiDat::parseFilters($request);
        $canShowReport = $filters['ma_khoa_hoc'] !== '';
        $hasNgayFilter = $filters['ngay'] !== '';

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $courseFilterOptions = DatTheoDoiDat::courseFilterOptions($filters['ma_khoa_hoc']);

        $groups = $canShowReport
            ? DatTheoDoiDat::buildGroups($filters)
            : [];

        $hasPhanCong = $canShowReport && DatTheoDoiDat::hasPhanCong($filters['ma_khoa_hoc']);

        $tenKhoaHoc = '';
        if ($filters['ma_khoa_hoc'] !== '') {
            $tenKhoaHoc = (string) ($khoaHocOptions->firstWhere('MaKhoaHoc', $filters['ma_khoa_hoc'])?->TenKhoaHoc ?? '');
        }

        return view('DaoTao.dat.theo-doi-dat', [
            'filters' => $filters,
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienOptions' => $courseFilterOptions['giao_vien_options'],
            'bienSoXeOptions' => $courseFilterOptions['bien_so_xe_options'],
            'giaoVienNames' => $courseFilterOptions['giao_vien_names'],
            'groups' => $groups,
            'canShowReport' => $canShowReport,
            'hasNgayFilter' => $hasNgayFilter,
            'hasPhanCong' => $hasPhanCong,
            'ngayHeading' => DatTheoDoiDat::formatNgayHeading($filters['ngay']),
            'tenKhoaHoc' => $tenKhoaHoc,
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $filters = DatTheoDoiDat::parseFilters($request);

        if ($filters['ma_khoa_hoc'] === '') {
            return redirect()
                ->route('daotao.pdt.dat.theo-doi')
                ->with('error', 'Chọn khóa học trước khi xuất Excel.');
        }

        if (! DatTheoDoiDat::hasPhanCong($filters['ma_khoa_hoc'])) {
            return redirect()
                ->route('daotao.pdt.dat.theo-doi', $request->query())
                ->with('error', 'Chưa có phân công học viên cho khóa này.');
        }

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->where('MaKhoaHoc', $filters['ma_khoa_hoc'])
            ->groupBy('MaKhoaHoc')
            ->first();

        $tenKhoaHoc = (string) ($khoaHocOptions?->TenKhoaHoc ?? '');
        $courseFilterOptions = DatTheoDoiDat::courseFilterOptions($filters['ma_khoa_hoc']);
        $groups = DatTheoDoiDat::buildGroups($filters);

        return DatTheoDoiDatExcelExporter::download(
            $groups,
            $filters,
            $tenKhoaHoc,
            $filters['ngay'] !== '',
            DatTheoDoiDat::formatNgayHeading($filters['ngay']),
            $courseFilterOptions['giao_vien_names']
        );
    }
}
