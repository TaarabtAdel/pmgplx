<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\BienBanPhanCongToChamDocx;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BienBanPhanCongToChamController extends Controller
{
    private const SLUG = '2-bien-ban-phan-cong-nhiem-vu-to-cham-kiem-tra';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = null;
        if ($khoa instanceof KhoaHoc) {
            $form = [
                'ma_kh' => (string) $khoa->MaKH,
                'ngay_lap' => now()->format('Y-m-d'),
                'hang' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'so_qd' => '',
                'ngay_qd' => now()->format('Y-m-d'),
                'ngay_kiem_tra' => '',
                'pho_truong_phong' => BienBanPhanCongToChamDocx::PHO_TRUONG_PHONG_MAC_DINH,
                'nv' => array_map(
                    fn ($ten) => ['ten' => $ten, 'nguoi' => [['xung' => 'Ông', 'ten' => '']]],
                    BienBanPhanCongToChamDocx::NHIEM_VU_MAC_DINH
                ),
            ];
        }

        return view('QldtOto.bien-ban-phan-cong-to-cham', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'giaoVien' => $form === null ? [] : self::giaoVienGoiY(),
            'xungOptions' => BienBanPhanCongToChamDocx::XUNG,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ngay_lap' => ['required', 'date'],
            'hang' => ['required', 'string', 'max:40'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'so_qd' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_qd' => ['required', 'date'],
            'ngay_kiem_tra' => ['required', 'date'],
            'pho_truong_phong' => ['required', 'string', 'max:100'],
            'nv' => ['required', 'array', 'min:1'],
            'nv.*.ten' => ['required', 'string', 'max:500'],
            'nv.*.nguoi' => ['nullable', 'array'],
            'nv.*.nguoi.*.xung' => ['required', Rule::in(BienBanPhanCongToChamDocx::XUNG)],
            'nv.*.nguoi.*.ten' => ['nullable', 'string', 'max:100'],
        ], [
            'so_qd.regex' => 'Số quyết định chỉ gồm chữ số.',
            'ngay_kiem_tra.required' => 'Chọn ngày kiểm tra.',
            'nv.required' => 'Cần ít nhất một nhiệm vụ.',
            'nv.*.ten.required' => 'Nhập tên nhiệm vụ.',
        ]);

        $nhiemVu = [];
        foreach (array_values($data['nv']) as $i => $nv) {
            $nguoi = [];
            foreach ($nv['nguoi'] ?? [] as $ng) {
                $ten = trim((string) ($ng['ten'] ?? ''));
                if ($ten !== '') {
                    $nguoi[] = ['xung' => $ng['xung'], 'ten' => preg_replace('/\s+/u', ' ', $ten)];
                }
            }
            if ($nguoi === []) {
                throw ValidationException::withMessages([
                    'nv' => 'Nhiệm vụ '.($i + 1).' chưa có người thực hiện — thêm người hoặc bỏ nhiệm vụ đó.',
                ]);
            }
            $nhiemVu[] = ['ten' => trim($nv['ten']), 'nguoi' => $nguoi];
        }

        $payload = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        $payload['nhiem_vu'] = $nhiemVu;
        $tmp = storage_path('app/tmp/bien-ban-phan-cong-to-cham-'.uniqid('', true).'.docx');
        try {
            BienBanPhanCongToChamDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['ma_kh']) ?: 'khoa';

        return response()
            ->download($tmp, 'bien-ban-phan-cong-to-cham-'.$safe.'.docx')
            ->deleteFileAfterSend(true);
    }

    /** @return list<array{ten: string, xung: string}> giáo viên PMGPLX đang hoạt động — gợi ý tên + Ông/Bà. */
    private static function giaoVienGoiY(): array
    {
        return GiaoVien::query()
            ->where('TrangThai', 1)
            ->orderBy('TenGV')
            ->orderBy('HoTenDem')
            ->get(['HoTenDem', 'TenGV', 'GioiTinh'])
            ->map(fn (GiaoVien $gv) => [
                'ten' => $gv->ho_ten,
                'xung' => strtoupper(trim((string) $gv->GioiTinh)) === 'F' ? 'Bà' : 'Ông',
            ])
            ->filter(fn ($gv) => $gv['ten'] !== '')
            ->unique('ten')
            ->values()
            ->all();
    }
}
