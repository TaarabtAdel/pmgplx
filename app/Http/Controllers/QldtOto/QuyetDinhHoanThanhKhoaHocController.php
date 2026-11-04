<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport;
use App\Support\QldtOto\QuyetDinhHoanThanhKhoaHocDocx;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Quyết định hoàn thành khoá học + danh sách học viên được cấp giấy xác nhận hoàn thành.
 * Khoá chính + có thể thêm khoá khác (học viên thi lại / khoá trước) — mẫu ghi «56 học viên … khóa 55 và 01 học viên … khóa 54».
 */
class QuyetDinhHoanThanhKhoaHocController extends Controller
{
    private const SLUG = '2-quyet-dinh-hoan-thanh-cua-khoa-hoc-danh-sach-hoc-vien-duoc';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoaThem = array_values(array_unique(array_filter(
            array_map('trim', (array) $request->query('khoa_them', [])),
            fn ($m) => $m !== '' && $m !== $maKh
        )));

        $khoaList = [];
        foreach ($maKh !== '' ? [$maKh, ...$khoaThem] : [] as $i => $ma) {
            $khoa = KhoaHoc::query()->find($ma);
            if (! $khoa instanceof KhoaHoc) {
                continue;
            }
            $khoaList[] = [
                'ma_kh' => (string) $khoa->MaKH,
                'ten_kh' => (string) $khoa->TenKH,
                'chinh' => $i === 0,
                'hang' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'ten_ngan' => QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa),
                'hoc_vien' => \App\Support\QldtOto\BaoCaoDeNghiSatHachData::hocVien((string) $khoa->MaKH),
            ];
        }

        return view('QldtOto.quyet-dinh-hoan-thanh-khoa-hoc', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'khoaThem' => $khoaThem,
            'khoaList' => $khoaList,
            'ngayMacDinh' => now()->format('Y-m-d'),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'so_qd' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'khoa_list' => ['required', 'array', 'min:1'],
            'khoa_list.*' => ['string', 'max:50'],
            'khoa' => ['required', 'array'],
            'khoa.*.hang' => ['required', 'string', 'max:40'],
            'khoa.*.khoa_so' => ['required', 'string', 'max:20'],
            'khoa.*.ten_ngan' => ['required', 'string', 'max:40'],
            'chon' => ['nullable', 'array'],
            'chon.*' => ['array'],
            'chon.*.*' => ['string', 'max:50'],
        ], [
            'so_qd.regex' => 'Số quyết định chỉ gồm chữ số.',
            'khoa.*.hang.required' => 'Nhập hạng của từng khoá.',
            'khoa.*.khoa_so.required' => 'Nhập số khoá của từng khoá.',
        ]);

        $khoa = [];
        foreach (array_values(array_unique($data['khoa_list'])) as $ma) {
            if (! KhoaHoc::query()->where('MaKH', $ma)->exists()) {
                continue;
            }
            $chon = array_flip((array) ($data['chon'][$ma] ?? []));
            $hocVien = array_values(array_filter(
                \App\Support\QldtOto\BaoCaoDeNghiSatHachData::hocVien($ma),
                fn ($hv) => isset($chon[$hv['ma_dk']])
            ));
            $khoa[] = [
                'hang' => trim($data['khoa'][$ma]['hang'] ?? ''),
                'khoa_so' => trim($data['khoa'][$ma]['khoa_so'] ?? ''),
                'ten_ngan' => trim($data['khoa'][$ma]['ten_ngan'] ?? ''),
                'hoc_vien' => array_map(fn ($hv) => [
                    'ho_ten' => $hv['ho_ten'],
                    'ngay_sinh' => $hv['ngay_sinh'],
                    'so_cmnd' => $hv['so_cmnd'],
                    'noi_cu_tru' => $hv['noi_cu_tru'],
                ], $hocVien),
            ];
        }
        if (array_sum(array_map(fn ($k) => count($k['hoc_vien']), $khoa)) === 0) {
            return back()->withInput()->with('error', 'Chưa chọn học viên nào.');
        }
        if ($khoa[0]['hoc_vien'] === []) {
            return back()->withInput()->with('error', 'Khoá chính chưa có học viên nào được chọn.');
        }

        $tmp = storage_path('app/tmp/quyet-dinh-hoan-thanh-'.uniqid('', true).'.docx');
        try {
            QuyetDinhHoanThanhKhoaHocDocx::generate([
                'so_qd' => $data['so_qd'],
                'ngay_quyet_dinh' => $data['ngay_quyet_dinh'],
                'khoa' => $khoa,
            ], $tmp);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Xuất Word thất bại: '.$e->getMessage());
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $khoa[0]['ten_ngan']) ?: 'khoa';

        return response()
            ->download($tmp, 'quyet-dinh-hoan-thanh-'.$data['so_qd'].'-'.$safe.'.docx')
            ->deleteFileAfterSend(true);
    }
}
