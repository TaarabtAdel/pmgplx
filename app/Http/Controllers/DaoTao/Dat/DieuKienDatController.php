<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDieuKienDat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DieuKienDatController extends Controller
{
    public function index(): View
    {
        $items = DatDieuKienDat::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get();

        return view('DaoTao.dat.dieu-kien-dat', [
            'items' => $items,
            'apDungTuNgay' => DatDieuKienDat::AP_DUNG_TU_NGAY,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DatDieuKienDat::query()->create([
            'Hang' => $validated['hang'],
            'ApDungTuNgay' => DatDieuKienDat::AP_DUNG_TU_NGAY,
            'TapLaiBanDemGio' => $validated['tap_lai_ban_dem_gio'],
            'XeSoTuDongGio' => $validated['xe_so_tu_dong_gio'],
            'SoGioHoc' => $validated['so_gio_hoc'],
            'TongQuangDuongKm' => $validated['tong_quang_duong_km'],
            'ThuTu' => $validated['thu_tu'],
            'NgayTao' => now(),
            'NgayCapNhat' => now(),
        ]);

        return back()->with('success', 'Đã thêm điều kiện đạt.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = DatDieuKienDat::query()->findOrFail($id);
        $validated = $this->validated($request);

        $item->update([
            'Hang' => $validated['hang'],
            'TapLaiBanDemGio' => $validated['tap_lai_ban_dem_gio'],
            'XeSoTuDongGio' => $validated['xe_so_tu_dong_gio'],
            'SoGioHoc' => $validated['so_gio_hoc'],
            'TongQuangDuongKm' => $validated['tong_quang_duong_km'],
            'ThuTu' => $validated['thu_tu'],
            'NgayCapNhat' => now(),
        ]);

        return back()->with('success', 'Đã cập nhật điều kiện đạt.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DatDieuKienDat::query()->findOrFail($id)->delete();

        return back()->with('success', 'Đã xóa điều kiện đạt.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'hang' => ['required', 'string', 'max:20'],
            'tap_lai_ban_dem_gio' => ['required', 'numeric', 'min:0', 'max:9999'],
            'xe_so_tu_dong_gio' => ['required', 'numeric', 'min:0', 'max:9999'],
            'so_gio_hoc' => ['required', 'numeric', 'min:0', 'max:9999'],
            'tong_quang_duong_km' => ['required', 'numeric', 'min:0', 'max:999999'],
            'thu_tu' => ['nullable', 'integer', 'min:0'],
        ], [
            'hang.required' => 'Nhập hạng GPLX.',
        ]);

        return [
            'hang' => trim((string) $validated['hang']),
            'tap_lai_ban_dem_gio' => round((float) $validated['tap_lai_ban_dem_gio'], 2),
            'xe_so_tu_dong_gio' => round((float) $validated['xe_so_tu_dong_gio'], 2),
            'so_gio_hoc' => round((float) $validated['so_gio_hoc'], 2),
            'tong_quang_duong_km' => round((float) $validated['tong_quang_duong_km'], 2),
            'thu_tu' => (int) ($validated['thu_tu'] ?? 0),
        ];
    }
}
