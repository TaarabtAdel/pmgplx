<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDieuKienCanhBao;
use App\Support\DaoTao\DatDSPhienKiemTra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DieuKienCanhBaoController extends Controller
{
    public function index(): View
    {
        $cauHinh = DatDieuKienCanhBao::hienTai();

        return view('DaoTao.dat.dieu-kien-canh-bao', [
            'cauHinh' => $cauHinh,
            'loiDefinitions' => DatDSPhienKiemTra::definitions(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thoi_gian_toi_thieu_phut' => ['required', 'integer', 'min:1', 'max:999'],
            'thoi_gian_toi_da_phut' => ['required', 'integer', 'min:1', 'max:9999', 'gt:thoi_gian_toi_thieu_phut'],
            'khoang_phien_lien_ke_phut' => ['required', 'integer', 'min:0', 'max:999'],
            'ti_le_nhan_dien_toi_thieu' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'thoi_gian_toi_da_phut.gt' => 'Thời gian tối đa phải lớn hơn thời gian tối thiểu.',
        ]);

        $cauHinh = DatDieuKienCanhBao::hienTai();
        $cauHinh->update([
            'ThoiGianPhienToiThieuPhut' => (int) $validated['thoi_gian_toi_thieu_phut'],
            'ThoiGianPhienToiDaPhut' => (int) $validated['thoi_gian_toi_da_phut'],
            'KhoangPhienLienKePhut' => (int) $validated['khoang_phien_lien_ke_phut'],
            'TiLeNhanDienToiThieu' => round((float) $validated['ti_le_nhan_dien_toi_thieu'], 2),
            'NgayCapNhat' => now(),
        ]);

        DatDieuKienCanhBao::resetCache();

        return back()->with('success', 'Đã cập nhật điều kiện cảnh báo.');
    }
}
