<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\PMGPLX\GiaoVienLichTrungScanner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoTrungLichGiaoVienController extends Controller
{
    public function show(Request $request): View
    {
        $submitted = $request->boolean('run');
        $input = $this->inputFromRequest($request);
        $result = $submitted ? GiaoVienLichTrungScanner::scan($input) : null;

        return view('PMGPLX.lich.do-trung-lich-gv', [
            'khoaHocs' => KhoaHoc::query()->orderBy('TenKH')->get(['MaKH', 'TenKH']),
            'giaoViens' => GiaoVien::query()->orderBy('TenGV')->orderBy('MaGV')->get(['MaGV', 'HoTenDem', 'TenGV']),
            'input' => $input,
            'result' => $result,
            'submitted' => $submitted,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function inputFromRequest(Request $request): array
    {
        return [
            'mode' => $request->input('mode', GiaoVienLichTrungScanner::MODE_BY_GV),
            'ma_gv' => $request->input('ma_gv', ''),
            'ma_kh' => $request->input('ma_kh', ''),
            'tu_ngay' => $request->input('tu_ngay', ''),
            'den_ngay' => $request->input('den_ngay', ''),
            'probe_ngay_bd' => $request->input('probe_ngay_bd', ''),
            'probe_ngay_kt' => $request->input('probe_ngay_kt', ''),
            'include_xe' => $request->boolean('include_xe', true),
            'loai_gv' => $request->input('loai_gv', 'TH'),
            'only_cross_khoa' => $request->boolean('only_cross_khoa', true),
            'trang_thai' => $request->input('trang_thai', '1'),
        ];
    }
}
