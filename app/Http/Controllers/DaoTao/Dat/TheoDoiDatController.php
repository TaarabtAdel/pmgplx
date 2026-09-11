<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatTheoDoiDat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TheoDoiDatController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatTheoDoiDat::parseFilters($request);
        $canShowReport = $filters['ma_khoa_hoc'] !== '' && $filters['ngay'] !== '';

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $groups = $canShowReport
            ? DatTheoDoiDat::buildGroups($filters['ma_khoa_hoc'], $filters['ngay'])
            : [];

        $tenKhoaHoc = '';
        if ($filters['ma_khoa_hoc'] !== '') {
            $tenKhoaHoc = (string) ($khoaHocOptions->firstWhere('MaKhoaHoc', $filters['ma_khoa_hoc'])?->TenKhoaHoc ?? '');
        }

        return view('DaoTao.dat.theo-doi-dat', [
            'filters' => $filters,
            'khoaHocOptions' => $khoaHocOptions,
            'groups' => $groups,
            'canShowReport' => $canShowReport,
            'ngayHeading' => DatTheoDoiDat::formatNgayHeading($filters['ngay']),
            'tenKhoaHoc' => $tenKhoaHoc,
        ]);
    }
}
