<?php

namespace App\Http\Controllers\HeThong;

use App\Http\Controllers\Controller;
use App\Models\HeThong\NguoiDung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NguoiDungController extends Controller
{
    public function index(Request $request): View
    {
        $query = NguoiDung::query()->with('vaiTros');

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $like = '%'.$keyword.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('Ten', 'like', $like)
                    ->orWhere('SoDienThoai', 'like', $like);
            });
        }

        if ($vaiTro = trim((string) $request->input('vai_tro'))) {
            $query->whereHas('vaiTros', fn ($q) => $q->where('MaVaiTro', $vaiTro));
        }

        $items = $query
            ->orderBy('Ten')
            ->orderBy('Id')
            ->paginate(50)
            ->withQueryString();

        return view('HeThong.nguoi-dung', [
            'items' => $items,
            'vaiTroOptions' => NguoiDung::VAI_TRO,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'vai_tro' => $request->input('vai_tro', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInput($request, null);

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($validated): void {
            $item = NguoiDung::query()->create([
                'Ten' => $validated['ten'],
                'SoDienThoai' => $validated['so_dien_thoai'],
                'MatKhau' => $validated['mat_khau'],
                'NgayTao' => now(),
                'NgayCapNhat' => now(),
            ]);
            $item->ganVaiTro($validated['vai_tro']);
        });

        return redirect()
            ->route('hethong.nguoi-dung.index')
            ->with('success', 'Đã thêm người dùng '.$validated['ten'].'.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = NguoiDung::query()->findOrFail($id);
        $validated = $this->validateInput($request, $item->Id);

        $payload = [
            'Ten' => $validated['ten'],
            'SoDienThoai' => $validated['so_dien_thoai'],
            'NgayCapNhat' => now(),
        ];
        if (($validated['mat_khau'] ?? '') !== '') {
            $payload['MatKhau'] = $validated['mat_khau'];
        }

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($item, $payload, $validated): void {
            $item->update($payload);
            $item->ganVaiTro($validated['vai_tro']);
        });

        $filters = array_filter([
            'tu_khoa' => trim((string) $request->input('tu_khoa')),
            'vai_tro' => trim((string) $request->input('vai_tro_loc')),
        ], static fn (string $v): bool => $v !== '');

        return redirect()
            ->route('hethong.nguoi-dung.index', $filters)
            ->with('success', 'Đã cập nhật người dùng '.$validated['ten'].'.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $item = NguoiDung::query()->findOrFail($id);
        if ((int) $request->user()?->getAuthIdentifier() === (int) $item->Id) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }
        $ten = $item->Ten;
        $item->delete();

        return redirect()
            ->route('hethong.nguoi-dung.index')
            ->with('success', 'Đã xóa người dùng '.$ten.'.');
    }

    /**
     * @return array{ten: string, so_dien_thoai: string, mat_khau: ?string, vai_tro: list<string>}
     */
    private function validateInput(Request $request, ?int $ignoreId): array
    {
        $request->merge([
            'ten' => trim((string) $request->input('ten')),
            'so_dien_thoai' => preg_replace('/\s+/', '', (string) $request->input('so_dien_thoai')),
            'vai_tro' => array_values(array_filter((array) $request->input('vai_tro', []), 'strlen')),
        ]);

        $validated = $request->validate([
            'ten' => ['required', 'string', 'max:150'],
            'so_dien_thoai' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9._+\-]{3,20}$/',
                Rule::unique('sqlsrv_manhlinh.NguoiDung', 'SoDienThoai')->ignore($ignoreId, 'Id'),
            ],
            'mat_khau' => [$ignoreId === null ? 'required' : 'nullable', 'string', 'min:6', 'max:100'],
            'vai_tro' => ['required', 'array', 'min:1'],
            'vai_tro.*' => ['string', Rule::in(array_keys(NguoiDung::VAI_TRO))],
        ], [
            'ten.required' => 'Nhập tên người dùng.',
            'so_dien_thoai.required' => 'Nhập số điện thoại.',
            'so_dien_thoai.regex' => 'SĐT chỉ gồm chữ số (hoặc tên tài khoản: chữ, số, dấu . _ - +), 3–20 ký tự.',
            'so_dien_thoai.unique' => 'Số điện thoại đã được dùng cho người dùng khác.',
            'mat_khau.required' => 'Nhập mật khẩu.',
            'mat_khau.min' => 'Mật khẩu tối thiểu 6 ký tự.',
            'vai_tro.required' => 'Chọn ít nhất một vai trò.',
            'vai_tro.min' => 'Chọn ít nhất một vai trò.',
            'vai_tro.*.in' => 'Vai trò không hợp lệ.',
        ]);

        $validated['vai_tro'] = array_values(array_unique($validated['vai_tro']));

        return $validated;
    }
}
