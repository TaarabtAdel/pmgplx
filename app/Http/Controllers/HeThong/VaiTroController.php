<?php

namespace App\Http\Controllers\HeThong;

use App\Http\Controllers\Controller;
use App\Models\HeThong\NguoiDungVaiTro;
use App\Models\HeThong\PhanQuyen;
use App\Models\HeThong\VaiTro;
use App\Support\HeThong\PhanQuyenNguoiDung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Hệ Thống → Vai trò → Danh sách vai trò: thêm / đổi tên / sắp thứ tự / xoá. Quyền của từng vai trò sửa ở ma trận Phân quyền. */
class VaiTroController extends Controller
{
    public function index(): View
    {
        $items = VaiTro::query()->orderBy('ThuTu')->orderBy('Id')->get();
        $soNguoi = NguoiDungVaiTro::query()->selectRaw('MaVaiTro, COUNT(*) AS c')->groupBy('MaVaiTro')->pluck('c', 'MaVaiTro');
        $soQuyen = PhanQuyen::query()->selectRaw('MaVaiTro, COUNT(*) AS c')->groupBy('MaVaiTro')->pluck('c', 'MaVaiTro');

        return view('HeThong.vai-tro', [
            'items' => $items,
            'soNguoi' => $soNguoi,
            'soQuyen' => $soQuyen,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:100'],
            'ma' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'sao_chep_tu' => ['nullable', 'string', Rule::in(array_keys(VaiTro::options()))],
        ], [
            'ten.required' => 'Nhập tên vai trò.',
            'ma.regex' => 'Mã vai trò chỉ gồm chữ thường không dấu, số và dấu gạch dưới (vd. ke_toan).',
        ]);

        $ten = trim($data['ten']);
        $ma = trim((string) ($data['ma'] ?? '')) ?: Str::slug($ten, '_');
        if ($ma === '') {
            return back()->withInput()->with('error', 'Không tạo được mã từ tên — nhập mã vai trò.');
        }
        if (VaiTro::query()->where('MaVaiTro', $ma)->exists()) {
            return back()->withInput()->with('error', 'Mã vai trò «'.$ma.'» đã có.');
        }

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($ma, $ten, $data, $request): void {
            VaiTro::query()->create([
                'MaVaiTro' => $ma,
                'TenVaiTro' => $ten,
                'ThuTu' => (int) VaiTro::query()->max('ThuTu') + 1,
                'LaHeThong' => false,
                'NgayTao' => now(),
            ]);

            $nguon = (string) ($data['sao_chep_tu'] ?? '');
            if ($nguon !== '' && $nguon !== VaiTro::ADMIN) {
                $now = now();
                $khoa = PhanQuyen::query()->where('MaVaiTro', $nguon)->pluck('MaChucNang')->all();
                foreach (array_chunk($khoa, 200) as $chunk) {
                    PhanQuyen::query()->insert(array_map(fn ($k) => [
                        'MaVaiTro' => $ma,
                        'MaChucNang' => $k,
                        'NguoiCapNhatId' => $request->user()?->getAuthIdentifier(),
                        'NgayCapNhat' => $now,
                    ], $chunk));
                }
            }
        });
        VaiTro::xoaCache();

        $nguon = (string) ($data['sao_chep_tu'] ?? '');
        $thongBao = 'Đã thêm vai trò «'.$ten.'» (mã '.$ma.')'
            .($nguon !== '' ? ', sao chép quyền từ «'.(VaiTro::options()[$nguon] ?? $nguon).'».' : ' — chưa có quyền, vào Phân quyền để cấp.');

        return redirect()->route('hethong.vai-tro.index')->with('success', $thongBao);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = VaiTro::query()->findOrFail($id);
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:100'],
            'thu_tu' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'ten.required' => 'Nhập tên vai trò.',
        ]);

        $item->update(['TenVaiTro' => trim($data['ten']), 'ThuTu' => (int) $data['thu_tu']]);
        VaiTro::xoaCache();

        return redirect()->route('hethong.vai-tro.index')->with('success', 'Đã cập nhật vai trò «'.$item->TenVaiTro.'».');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = VaiTro::query()->findOrFail($id);
        if ($item->LaHeThong) {
            return back()->with('error', 'Không xoá được vai trò hệ thống «'.$item->TenVaiTro.'».');
        }
        $soNguoi = NguoiDungVaiTro::query()->where('MaVaiTro', $item->MaVaiTro)->count();
        if ($soNguoi > 0) {
            return back()->with('error', 'Vai trò «'.$item->TenVaiTro.'» đang gán cho '.$soNguoi.' người dùng — bỏ vai trò khỏi các người dùng đó trước khi xoá.');
        }

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($item): void {
            PhanQuyen::query()->where('MaVaiTro', $item->MaVaiTro)->delete();
            $item->delete();
        });
        VaiTro::xoaCache();
        PhanQuyenNguoiDung::xoaCache();

        return redirect()->route('hethong.vai-tro.index')->with('success', 'Đã xoá vai trò «'.$item->TenVaiTro.'».');
    }
}
