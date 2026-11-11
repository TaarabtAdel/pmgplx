<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\HopDongDaoTaoLaiXeDocx;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HopDongDaoTaoLaiXeController extends Controller
{
    private const SLUG = '1-hop-dong-dao-tao-lai-xe-cua-tung-hoc-vien';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $loai = $request->query('loai', HopDongDaoTaoLaiXeDocx::LOAI_B1);
        if (! isset(HopDongDaoTaoLaiXeDocx::LOAI[$loai])) {
            $loai = HopDongDaoTaoLaiXeDocx::LOAI_B1;
        }

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = HopDongDaoTaoLaiXeDocx::LOAI[$loai]['mac_dinh'];
        $form['loai'] = $loai;
        $form['hoc_phi_mot_lan_chu'] = $form['hoc_phi_chu'];

        if ($khoa instanceof KhoaHoc) {
            $khoaSo = BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH);
            if ($khoaSo !== null && $khoaSo !== '') {
                $form['mo_ta_hang'] = preg_replace('/Khóa\s*\.+/u', 'Khóa '.$khoaSo, $form['mo_ta_hang']) ?? $form['mo_ta_hang'];
            }
        }

        return view('QldtOto.hop-dong-dao-tao-lai-xe', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'loaiOptions' => HopDongDaoTaoLaiXeDocx::LOAI,
            'form' => $form,
            'loaiMacDinhJson' => collect(HopDongDaoTaoLaiXeDocx::LOAI)
                ->map(fn ($c) => array_merge($c['mac_dinh'], ['hoc_phi_mot_lan_chu' => $c['mac_dinh']['hoc_phi_chu']]))
                ->all(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $loaiKeys = array_keys(HopDongDaoTaoLaiXeDocx::LOAI);
        $data = $request->validate([
            'loai' => ['required', Rule::in($loaiKeys)],
            'mo_ta_hang' => ['required', 'string', 'max:200'],
            'gio_tong' => ['required', 'regex:/^\d+$/', 'max:4'],
            'gio_lt' => ['required', 'regex:/^\d+$/', 'max:4'],
            'gio_th' => ['required', 'regex:/^\d+$/', 'max:4'],
            'hoc_phi' => ['required', 'integer', 'min:0'],
            'hoc_phi_chu' => ['required', 'string', 'max:120'],
            'lan_1' => ['required', 'integer', 'min:0'],
            'lan_1_chu' => ['required', 'string', 'max:120'],
            'lan_2' => ['required', 'integer', 'min:0'],
            'lan_2_chu' => ['required', 'string', 'max:120'],
            'lan_3' => ['required', 'integer', 'min:0'],
            'lan_3_chu' => ['required', 'string', 'max:120'],
            'hoc_phi_mot_lan_chu' => ['required', 'string', 'max:120'],
        ], [
            'gio_tong.regex' => 'Số giờ chỉ gồm chữ số.',
        ]);

        $payload = array_merge($data, [
            'mo_ta_hang' => trim($data['mo_ta_hang']),
            'hoc_phi_chu' => trim($data['hoc_phi_chu']),
            'hoc_phi_mot_lan_chu' => trim($data['hoc_phi_mot_lan_chu']),
        ]);

        $tmp = storage_path('app/tmp/hop-dong-dao-tao-'.uniqid('', true).'.docx');
        try {
            HopDongDaoTaoLaiXeDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        return response()
            ->download($tmp, 'hop-dong-dao-tao-'.$data['loai'].'.docx')
            ->deleteFileAfterSend(true);
    }
}
