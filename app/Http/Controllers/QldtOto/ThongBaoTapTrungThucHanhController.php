<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\ThongBaoTapTrungThucHanhDocx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThongBaoTapTrungThucHanhController extends Controller
{
    private const SLUG = '2-thong-bao-thoi-gian-tap-trung-hoc-thuc-hanh-lai-xe';

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
                'so_tb' => '',
                'ngay_thong_bao' => now()->format('Y-m-d'),
                'hang' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'buoi' => ThongBaoTapTrungThucHanhDocx::BUOI[0],
                'gio' => '06h',
                'ngay_tap_trung' => '',
                'dia_diem' => ThongBaoTapTrungThucHanhDocx::DIA_DIEM_MAC_DINH,
                'noi_dung' => ThongBaoTapTrungThucHanhDocx::NOI_DUNG_MAC_DINH,
            ];
        }

        return view('QldtOto.thong-bao-tap-trung-thuc-hanh', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'buoiOptions' => ThongBaoTapTrungThucHanhDocx::BUOI,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'so_tb' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_thong_bao' => ['required', 'date'],
            'hang' => ['required', 'string', 'max:40'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'buoi' => ['required', Rule::in(ThongBaoTapTrungThucHanhDocx::BUOI)],
            'gio' => ['required', 'string', 'max:20'],
            'ngay_tap_trung' => ['required', 'date'],
            'dia_diem' => ['required', 'string', 'max:300'],
            'noi_dung' => ['required', 'string', 'max:500'],
        ], [
            'so_tb.regex' => 'Số thông báo chỉ gồm chữ số.',
            'ngay_tap_trung.required' => 'Chọn ngày tập trung học thực hành.',
        ]);

        $data = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        $tmp = storage_path('app/tmp/thong-bao-tap-trung-'.uniqid('', true).'.docx');
        try {
            ThongBaoTapTrungThucHanhDocx::generate($data, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['ma_kh']) ?: 'khoa';

        return response()
            ->download($tmp, 'thong-bao-tap-trung-thuc-hanh-'.$data['so_tb'].'-'.$safe.'.docx')
            ->deleteFileAfterSend(true);
    }
}
