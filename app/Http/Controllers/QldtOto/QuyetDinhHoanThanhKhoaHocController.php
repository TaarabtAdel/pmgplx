<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport;
use App\Support\QldtOto\BaoCaoDeNghiSatHachData;
use App\Support\QldtOto\DanhSachKemQuyetDinhHoanThanhExcelExporter;
use App\Support\QldtOto\QuyetDinhHoanThanhKhoaHocDocx;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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
            $hang = BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null);
            $hocVien = BaoCaoDeNghiSatHachData::hocVien((string) $khoa->MaKH);
            foreach ($hocVien as &$hv) {
                $ten = DanhSachKemQuyetDinhHoanThanhExcelExporter::tachHoTen($hv['ho_ten']);
                $hv['ho_dem'] = $ten['ho_dem'];
                $hv['ten_rieng'] = $ten['ten'];
                $hv['ngay_sinh_fm'] = BaoCaoDeNghiSatHachData::ngay($hv['ngay_sinh']);
                $hv['so_giay_ht'] = DanhSachKemQuyetDinhHoanThanhExcelExporter::soGiayXacNhanHt($hv['ma_dk'], $hang);
            }
            unset($hv);

            $khoaList[] = [
                'ma_kh' => (string) $khoa->MaKH,
                'ten_kh' => (string) $khoa->TenKH,
                'chinh' => $i === 0,
                'hang' => $hang,
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'ten_ngan' => QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa),
                'hoc_vien' => $hocVien,
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

    public function export(Request $request, DanhSachKemQuyetDinhHoanThanhExcelExporter $excelExporter): BinaryFileResponse|RedirectResponse
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
                    'ma_dk' => $hv['ma_dk'],
                    'ho_ten' => $hv['ho_ten'],
                    'ngay_sinh' => BaoCaoDeNghiSatHachData::ngay($hv['ngay_sinh']),
                    'so_cmnd' => $hv['so_cmnd'],
                    'so_ccn' => $hv['so_ccn'],
                ], $hocVien),
            ];
        }
        if (array_sum(array_map(fn ($k) => count($k['hoc_vien']), $khoa)) === 0) {
            return back()->withInput()->with('error', 'Chưa chọn học viên nào.');
        }
        if ($khoa[0]['hoc_vien'] === []) {
            return back()->withInput()->with('error', 'Khoá chính chưa có học viên nào được chọn.');
        }

        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $nhom = array_map(
            fn ($k) => sprintf('%02d', count($k['hoc_vien'])).' học viên Hạng '.$k['hang'].' khóa '.$k['khoa_so'],
            $khoa
        );
        $dsNhom = count($nhom) > 1 ? implode(', ', array_slice($nhom, 0, -1)).' và '.end($nhom) : $nhom[0];

        $wordPath = storage_path('app/tmp/quyet-dinh-hoan-thanh-'.uniqid('', true).'.docx');
        $excelPath = storage_path('app/tmp/kem-quyet-dinh-hoan-thanh-'.uniqid('', true).'.xlsx');
        $zipPath = storage_path('app/tmp/quyet-dinh-hoan-thanh-'.uniqid('', true).'.zip');
        try {
            QuyetDinhHoanThanhKhoaHocDocx::generate([
                'so_qd' => $data['so_qd'],
                'ngay_quyet_dinh' => $data['ngay_quyet_dinh'],
                'khoa' => $khoa,
            ], $wordPath);

            $dongExcel = [];
            foreach ($khoa as $k) {
                foreach ($k['hoc_vien'] as $hv) {
                    $ten = DanhSachKemQuyetDinhHoanThanhExcelExporter::tachHoTen($hv['ho_ten']);
                    $dongExcel[] = [
                        'ho_dem' => $ten['ho_dem'],
                        'ten' => $ten['ten'],
                        'ngay_sinh' => $hv['ngay_sinh'],
                        'so_cmnd' => $hv['so_cmnd'],
                        'so_giay_ht' => DanhSachKemQuyetDinhHoanThanhExcelExporter::soGiayXacNhanHt($hv['ma_dk'], $k['hang']),
                        'so_ccn' => $hv['so_ccn'],
                        'ten_khoa' => $k['ten_ngan'],
                    ];
                }
            }

            $excelExporter->generate([
                'tieu_de' => DanhSachKemQuyetDinhHoanThanhExcelExporter::tieuDe($khoa[0]['hang'], $khoa[0]['khoa_so']),
                'dong_quyet_dinh' => DanhSachKemQuyetDinhHoanThanhExcelExporter::dongKemTheo($data['so_qd'], $ngay, $dsNhom),
            ], $dongExcel, $excelPath);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Xuất file thất bại: '.$e->getMessage());
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $khoa[0]['ten_ngan']) ?: 'khoa';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withInput()->with('error', 'Không tạo được file ZIP.');
        }
        $zip->addFile($wordPath, 'quyet-dinh-hoan-thanh-'.$data['so_qd'].'-'.$safe.'.docx');
        $zip->addFile($excelPath, 'kem-quyet-dinh-hoan-thanh-'.$safe.'.xlsx');
        $zip->close();
        @unlink($wordPath);
        @unlink($excelPath);

        return response()
            ->download($zipPath, 'quyet-dinh-hoan-thanh-'.$data['so_qd'].'-'.$safe.'.zip')
            ->deleteFileAfterSend(true);
    }
}
