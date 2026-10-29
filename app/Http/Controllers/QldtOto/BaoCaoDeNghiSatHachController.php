<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDeNghiSatHachData;
use App\Support\QldtOto\BaoCaoDeNghiSatHachDocxExporter;
use App\Support\QldtOto\DanhSachBc2DocxGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BaoCaoDeNghiSatHachController extends Controller
{
    private const SLUG = '3-bao-cao-de-nghi-to-chuc-sat-hach-danh-sach';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $hocVien = [];
        $form = null;
        if ($khoa instanceof KhoaHoc) {
            $hocVien = BaoCaoDeNghiSatHachData::hocVien((string) $khoa->MaKH);
            $form = BaoCaoDeNghiSatHachData::macDinh($khoa, count($hocVien));
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.bao-cao-de-nghi-sat-hach', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'maKh' => $maKh,
            'khoa' => $khoa,
            'form' => $form,
            'hocVien' => $hocVien,
        ]);
    }

    public function export(
        Request $request,
        BaoCaoDeNghiSatHachDocxExporter $baoCao,
        DanhSachBc2DocxGenerator $bc2
    ): BinaryFileResponse|RedirectResponse {
        $form = $request->validate([
            'loai' => ['required', Rule::in(['bao_cao', 'bc2'])],
            'ma_kh' => ['required', 'string', 'max:50'],
            'so_bc' => ['required_if:loai,bao_cao', 'nullable', 'regex:/^\d+$/', 'max:10'],
            'ngay_bao_cao' => ['required', 'date'],
            'kinh_gui' => ['required_if:loai,bao_cao', 'nullable', 'string', 'max:200'],
            'hang' => ['required_if:loai,bao_cao', 'nullable', 'string', 'max:40'],
            'khoa_lop' => ['required_if:loai,bao_cao', 'nullable', 'string', 'max:50'],
            'so_vao_hoc' => ['required_if:loai,bao_cao', 'nullable', 'integer', 'min:0'],
            'ngay_sh' => ['nullable', 'date'],
            'ghi_chu' => ['nullable', 'string', 'max:100'],
            'hang_bc2' => ['required_if:loai,bc2', 'nullable', 'string', 'max:20'],
            'khoa_bc2' => ['required_if:loai,bc2', 'nullable', 'string', 'max:50'],
            'thoi_gian_dt' => ['nullable', 'string', 'max:20'],
            'ngay_kg' => ['nullable', 'date'],
            'ngay_bg' => ['nullable', 'date'],
            'ccn_tien_to' => ['nullable', 'string', 'max:10'],
            'ccn_bat_dau' => ['nullable', 'integer', 'min:0'],
            'chon' => ['nullable', 'array'],
            'chon.*' => ['string', 'max:50'],
        ], [
            'so_bc.required_if' => 'Nhập số báo cáo.',
            'so_bc.regex' => 'Số báo cáo chỉ gồm chữ số.',
        ]);

        if (! KhoaHoc::query()->where('MaKH', $form['ma_kh'])->exists()) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $chon = array_flip($form['chon'] ?? []);
        $thiSinh = array_values(array_filter(
            BaoCaoDeNghiSatHachData::hocVien($form['ma_kh']),
            fn (array $hv): bool => isset($chon[$hv['ma_dk']])
        ));
        if ($thiSinh === []) {
            return back()->withErrors(['chon' => 'Chưa chọn học viên dự sát hạch.'])->withInput();
        }

        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', $form['ma_kh']) ?: 'khoa';
        $tmpDir = storage_path('app/tmp');

        try {
            if ($form['loai'] === 'bao_cao') {
                $tmp = $tmpDir.'/bao-cao-de-nghi-sh-'.uniqid('', true).'.docx';
                $baoCao->generate($form, count($thiSinh), $tmp);
                $ten = 'bao-cao-de-nghi-to-chuc-sat-hach-'.$safeMa.'.docx';
            } else {
                $batDau = isset($form['ccn_bat_dau']) ? (int) $form['ccn_bat_dau'] : null;
                foreach ($thiSinh as &$hv) {
                    $hv['so_ccn'] = BaoCaoDeNghiSatHachData::soCcn($hv, (string) ($form['ccn_tien_to'] ?? ''), $batDau);
                }
                unset($hv);
                $tmp = $tmpDir.'/danh-sach-bc2-'.uniqid('', true).'.docx';
                $bc2->generate($form, $thiSinh, $tmp);
                $ten = 'danh-sach-thi-sinh-du-sat-hach-bc2-'.$safeMa.'.docx';
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        return response()->download($tmp, $ten)->deleteFileAfterSend(true);
    }
}
