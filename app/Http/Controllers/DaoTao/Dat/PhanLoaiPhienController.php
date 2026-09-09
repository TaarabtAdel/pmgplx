<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanLoaiPhien;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhanLoaiPhienController extends Controller
{
    public function index(): View
    {
        $items = DatPhanLoaiPhien::query()
            ->withCount('phienHoc')
            ->orderBy('ThuTu')
            ->orderBy('TenPhanLoai')
            ->get();

        return view('DaoTao.dat.phan-loai-phien', [
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ten_phan_loai' => ['required', 'string', 'max:150'],
            'mo_ta' => ['nullable', 'string', 'max:500'],
            'thu_tu' => ['nullable', 'integer', 'min:0'],
        ], [
            'ten_phan_loai.required' => 'Nhập tên phân loại.',
        ]);

        DatPhanLoaiPhien::query()->create([
            'TenPhanLoai' => trim((string) $validated['ten_phan_loai']),
            'MoTa' => trim((string) ($validated['mo_ta'] ?? '')) ?: null,
            'ThuTu' => (int) ($validated['thu_tu'] ?? 0),
            'NgayTao' => now(),
        ]);

        return back()->with('success', 'Đã thêm phân loại phiên.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = DatPhanLoaiPhien::query()->findOrFail($id);

        $validated = $request->validate([
            'ten_phan_loai' => ['required', 'string', 'max:150'],
            'mo_ta' => ['nullable', 'string', 'max:500'],
            'thu_tu' => ['nullable', 'integer', 'min:0'],
        ], [
            'ten_phan_loai.required' => 'Nhập tên phân loại.',
        ]);

        $item->update([
            'TenPhanLoai' => trim((string) $validated['ten_phan_loai']),
            'MoTa' => trim((string) ($validated['mo_ta'] ?? '')) ?: null,
            'ThuTu' => (int) ($validated['thu_tu'] ?? 0),
        ]);

        return back()->with('success', 'Đã cập nhật phân loại phiên.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = DatPhanLoaiPhien::query()->findOrFail($id);
        $item->phienHoc()->detach();
        $item->delete();

        return back()->with('success', 'Đã xóa phân loại phiên.');
    }
}
