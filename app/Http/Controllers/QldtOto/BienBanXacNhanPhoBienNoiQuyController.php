<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\BienBanXacNhanPhoBienNoiQuyDocx;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BienBanXacNhanPhoBienNoiQuyController extends Controller
{
    private const SLUG = '1-bien-ban-xac-nhan-pho-bien-noi-quy-quy-che-chuong-trinh-dao';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = null;
        $hocVien = [];
        if ($khoa instanceof KhoaHoc) {
            $hocVien = QuyetDinhHocVienForm::hocVien($khoa);
            $hang = BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null);
            $goiY = array_slice($hocVien, 0, 3);
            while (count($goiY) < 3) {
                $goiY[] = ['ma_dk' => '', 'ho_ten' => '', 'ngay_sinh' => '', 'so_cmnd' => '', 'noi_cu_tru' => ''];
            }

            $form = [
                'ma_kh' => (string) $khoa->MaKH,
                'ngay_lap' => now()->format('Y-m-d'),
                'gio' => '8',
                'ngay_hop' => now()->format('Y-m-d'),
                'dia_diem' => BienBanXacNhanPhoBienNoiQuyDocx::DIA_DIEM_MAC_DINH,
                'lop_mo_ta' => BienBanXacNhanPhoBienNoiQuyDocx::lopMoTaMacDinh($hang),
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'hang_hoa' => BienBanXacNhanPhoBienNoiQuyDocx::hangHoaTuMa($khoa->HangGPLX ?? null),
                'hang_rieng' => $hang,
                'xung_ddt' => BienBanXacNhanPhoBienNoiQuyDocx::XUNG[0],
                'ten_ddt' => BienBanXacNhanPhoBienNoiQuyDocx::TEN_DDT_MAC_DINH,
                'dai_dien' => array_map(
                    fn (array $hv) => ['ma_dk' => $hv['ma_dk'], 'ho_ten' => $hv['ho_ten'], 'ngay_sinh' => $hv['ngay_sinh']],
                    $goiY
                ),
            ];
        }

        return view('QldtOto.bien-ban-xac-nhan-pho-bien-noi-quy', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'hocVien' => $hocVien,
            'xungOptions' => BienBanXacNhanPhoBienNoiQuyDocx::XUNG,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ngay_lap' => ['required', 'date'],
            'gio' => ['required', 'string', 'max:10'],
            'ngay_hop' => ['required', 'date'],
            'dia_diem' => ['required', 'string', 'max:300'],
            'lop_mo_ta' => ['required', 'string', 'max:80'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'hang_hoa' => ['required', 'string', 'max:20'],
            'hang_rieng' => ['required', 'string', 'max:40'],
            'xung_ddt' => ['required', Rule::in(BienBanXacNhanPhoBienNoiQuyDocx::XUNG)],
            'ten_ddt' => ['required', 'string', 'max:100'],
            'dai_dien' => ['required', 'array', 'size:3'],
            'dai_dien.*.ho_ten' => ['required', 'string', 'max:120'],
            'dai_dien.*.ngay_sinh' => ['required', 'string', 'max:20'],
        ], [
            'dai_dien.size' => 'Biên bản cần đúng 3 học viên đại diện.',
            'dai_dien.*.ho_ten.required' => 'Nhập họ tên đại diện.',
            'dai_dien.*.ngay_sinh.required' => 'Nhập ngày sinh đại diện.',
        ]);

        $payload = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        $payload['dai_dien'] = array_values(array_map(
            fn (array $dd) => [
                'ho_ten' => preg_replace('/\s+/u', ' ', trim($dd['ho_ten'])),
                'ngay_sinh' => trim($dd['ngay_sinh']),
            ],
            $data['dai_dien']
        ));

        $tmp = storage_path('app/tmp/bien-ban-pho-bien-'.uniqid('', true).'.docx');
        try {
            BienBanXacNhanPhoBienNoiQuyDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['ma_kh']) ?: 'khoa';

        return response()
            ->download($tmp, 'bien-ban-pho-bien-noi-quy-'.$safe.'.docx')
            ->deleteFileAfterSend(true);
    }
}
