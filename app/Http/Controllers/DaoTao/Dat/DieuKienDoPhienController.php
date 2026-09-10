<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDieuKienDoPhien;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DieuKienDoPhienController extends Controller
{
    public function index(): View
    {
        return view('DaoTao.dat.dieu-kien-do-phien', [
            'cauHinh' => DatDieuKienDoPhien::hienTai(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cho_phep_som_phut' => ['required', 'integer', 'min:0', 'max:999'],
            'cho_phep_muon_phut' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $cauHinh = DatDieuKienDoPhien::hienTai();
        $cauHinh->update([
            'ChoPhepSomPhut' => (int) $validated['cho_phep_som_phut'],
            'ChoPhepMuonPhut' => (int) $validated['cho_phep_muon_phut'],
            'NgayCapNhat' => now(),
        ]);

        DatDieuKienDoPhien::resetCache();

        return back()->with('success', 'Đã cập nhật điều kiện dò phiên.');
    }
}
