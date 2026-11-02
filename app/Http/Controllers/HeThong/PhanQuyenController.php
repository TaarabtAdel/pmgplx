<?php

namespace App\Http\Controllers\HeThong;

use App\Http\Controllers\Controller;
use App\Models\HeThong\PhanQuyen;
use App\Models\HeThong\VaiTro;
use App\Support\HeThong\CayChucNang;
use App\Support\HeThong\PhanQuyenNguoiDung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Hệ Thống → Vai trò → Phân quyền: ma trận chức năng (Tab → Menu 1 → Menu 2) × vai trò. */
class PhanQuyenController extends Controller
{
    public function index(): View
    {
        $vaiTroSua = $this->vaiTroSua();
        $daCap = [];
        foreach (PhanQuyen::query()->whereIn('MaVaiTro', array_keys($vaiTroSua))->get(['MaVaiTro', 'MaChucNang']) as $r) {
            $daCap[$r->MaVaiTro][$r->MaChucNang] = true;
        }

        return view('HeThong.phan-quyen', [
            'cay' => CayChucNang::cay(),
            'vaiTroSua' => $vaiTroSua,
            'daCap' => $daCap,
            'soLa' => count(CayChucNang::tatCaKhoaLa()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vaiTroSua = $this->vaiTroSua();
        $hopLe = array_flip(CayChucNang::tatCaKhoaLa());
        $quyen = (array) $request->input('quyen', []);
        $now = now();

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($vaiTroSua, $hopLe, $quyen, $now, $request): void {
            foreach (array_keys($vaiTroSua) as $vaiTro) {
                $khoa = array_values(array_unique(array_filter(
                    (array) ($quyen[$vaiTro] ?? []),
                    fn ($k) => is_string($k) && isset($hopLe[$k])
                )));
                PhanQuyen::query()->where('MaVaiTro', $vaiTro)->delete();
                foreach (array_chunk($khoa, 200) as $chunk) {
                    PhanQuyen::query()->insert(array_map(fn (string $k) => [
                        'MaVaiTro' => $vaiTro,
                        'MaChucNang' => $k,
                        'NguoiCapNhatId' => $request->user()?->getAuthIdentifier(),
                        'NgayCapNhat' => $now,
                    ], $chunk));
                }
            }
        });
        PhanQuyenNguoiDung::xoaCache();

        return redirect()->route('hethong.phan-quyen.index')->with('success', 'Đã lưu ma trận phân quyền.');
    }

    /** @return array<string, string> vai trò sửa được (Admin luôn đủ quyền, không sửa) */
    private function vaiTroSua(): array
    {
        return array_diff_key(VaiTro::options(), [VaiTro::ADMIN => true]);
    }
}
