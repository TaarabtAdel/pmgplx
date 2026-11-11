<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\DanhSachHoiDongXetHoanThanhExcelExporter;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\QuyetDinhThanhLapHoiDongXetHoanThanhDocx;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ThanhLapHoiDongXetKtmController extends Controller
{
    private const SLUG = '2-lam-quyet-dinh-thanh-lap-hoi-dong-xet-kiem-tra-ket-thuc-mon';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;
        $batDau = $request->boolean('bat_dau');

        $form = null;
        if ($khoa instanceof KhoaHoc || $batDau) {
            $hang = '';
            $khoaSo = '';
            if ($khoa instanceof KhoaHoc) {
                $hang = BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null);
                $khoaSo = BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '';
            }
            $form = [
                'ma_kh' => $khoa instanceof KhoaHoc ? (string) $khoa->MaKH : '',
                'so_qd' => '',
                'ngay_quyet_dinh' => now()->format('Y-m-d'),
                'mo_ta_quyet_dinh' => $hang !== ''
                    ? QuyetDinhThanhLapHoiDongXetHoanThanhDocx::moTaMacDinhTuKhoa($hang, $khoaSo)
                    : QuyetDinhThanhLapHoiDongXetHoanThanhDocx::MO_TA_QUYET_DINH_MAU,
                'mo_ta_dieu_1' => $hang !== ''
                    ? QuyetDinhThanhLapHoiDongXetHoanThanhDocx::moTaDieu1MacDinhTuKhoa($hang, $khoaSo)
                    : 'hạng … Khóa … và các học viên vắng, thi lại các khóa khác trước.',
                'giam_doc' => QuyetDinhThanhLapHoiDongXetHoanThanhDocx::GIAM_DOC_MAC_DINH,
                'thanh_vien' => QuyetDinhThanhLapHoiDongXetHoanThanhDocx::THANH_VIEN_MAC_DINH,
            ];
        }

        return view('QldtOto.thanh-lap-hoi-dong-xet-ktm', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
        ]);
    }

    public function exportQuyetDinh(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $this->validateChung($request);
        $tmp = storage_path('app/tmp/qd-hoi-dong-xet-ht-'.uniqid('', true).'.docx');
        try {
            QuyetDinhThanhLapHoiDongXetHoanThanhDocx::generate($this->payloadQuyetDinh($data), $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Quyết định thất bại: '.$e->getMessage())->withInput();
        }

        return $this->download($tmp, 'quyet-dinh-thanh-lap-hoi-dong-'.$this->safeMa($data).'.docx');
    }

    public function exportDanhSach(Request $request, DanhSachHoiDongXetHoanThanhExcelExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $data = $this->validateChung($request);
        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $tmp = storage_path('app/tmp/ds-hoi-dong-xet-ht-'.uniqid('', true).'.xlsx');
        try {
            $exporter->generate([
                'tieu_de' => DanhSachHoiDongXetHoanThanhExcelExporter::tieuDeTuMoTa($data['mo_ta_quyet_dinh']),
                'dong_quyet_dinh' => DanhSachHoiDongXetHoanThanhExcelExporter::dongKemTheo($data['so_qd'], $ngay),
            ], $this->parseThanhVien($data), $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Excel thất bại: '.$e->getMessage())->withInput();
        }

        return $this->download($tmp, 'danh-sach-hoi-dong-xet-ht-'.$this->safeMa($data).'.xlsx');
    }

    public function exportCaHai(Request $request, DanhSachHoiDongXetHoanThanhExcelExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $data = $this->validateChung($request);
        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $qdPath = storage_path('app/tmp/qd-hoi-dong-xet-ht-'.uniqid('', true).'.docx');
        $xlPath = storage_path('app/tmp/ds-hoi-dong-xet-ht-'.uniqid('', true).'.xlsx');
        $zipPath = storage_path('app/tmp/qd-ds-hoi-dong-'.uniqid('', true).'.zip');

        try {
            QuyetDinhThanhLapHoiDongXetHoanThanhDocx::generate($this->payloadQuyetDinh($data), $qdPath);
            $exporter->generate([
                'tieu_de' => DanhSachHoiDongXetHoanThanhExcelExporter::tieuDeTuMoTa($data['mo_ta_quyet_dinh']),
                'dong_quyet_dinh' => DanhSachHoiDongXetHoanThanhExcelExporter::dongKemTheo($data['so_qd'], $ngay),
            ], $this->parseThanhVien($data), $xlPath);
        } catch (\Throwable $e) {
            @unlink($qdPath);
            @unlink($xlPath);

            return back()->with('error', 'Xuất file thất bại: '.$e->getMessage())->withInput();
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($qdPath);
            @unlink($xlPath);

            return back()->with('error', 'Không tạo được file ZIP.')->withInput();
        }
        $safe = $this->safeMa($data);
        $zip->addFile($qdPath, 'quyet-dinh-thanh-lap-hoi-dong-'.$safe.'.docx');
        $zip->addFile($xlPath, 'danh-sach-hoi-dong-xet-ht-'.$safe.'.xlsx');
        $zip->close();
        @unlink($qdPath);
        @unlink($xlPath);

        return $this->download($zipPath, 'qd-ds-hoi-dong-xet-ht-'.$safe.'.zip');
    }

    /** @return array<string, mixed> */
    private function validateChung(Request $request): array
    {
        return $request->validate([
            'ma_kh' => ['nullable', 'string', 'max:50'],
            'so_qd' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'mo_ta_quyet_dinh' => ['required', 'string', 'max:500'],
            'mo_ta_dieu_1' => ['required', 'string', 'max:500'],
            'giam_doc' => ['required', 'string', 'max:100'],
            'thanh_vien' => ['required', 'array', 'min:1'],
            'thanh_vien.*.ho_ten' => ['required', 'string', 'max:120'],
            'thanh_vien.*.chuc_vu' => ['required', 'string', 'max:120'],
            'thanh_vien.*.chuc_danh' => ['required', 'string', 'max:120'],
        ], [
            'so_qd.regex' => 'Số quyết định chỉ gồm chữ số.',
            'thanh_vien.min' => 'Cần ít nhất một thành viên hội đồng.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{ho_ten: string, chuc_vu: string, chuc_danh: string}>
     */
    private function parseThanhVien(array $data): array
    {
        return array_values(array_map(fn ($tv) => [
            'ho_ten' => preg_replace('/\s+/u', ' ', trim($tv['ho_ten'])),
            'chuc_vu' => trim($tv['chuc_vu']),
            'chuc_danh' => trim($tv['chuc_danh']),
        ], $data['thanh_vien']));
    }

    /** @param  array<string, mixed>  $data */
    private function payloadQuyetDinh(array $data): array
    {
        return [
            'so_qd' => trim($data['so_qd']),
            'ngay_quyet_dinh' => $data['ngay_quyet_dinh'],
            'mo_ta_quyet_dinh' => trim($data['mo_ta_quyet_dinh']),
            'mo_ta_dieu_1' => trim($data['mo_ta_dieu_1']),
            'giam_doc' => trim($data['giam_doc']),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function safeMa(array $data): string
    {
        $ma = trim((string) ($data['ma_kh'] ?? ''));
        if ($ma !== '') {
            return preg_replace('/[^A-Za-z0-9_-]+/', '-', $ma) ?: 'khoa';
        }

        return preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['so_qd']) ?: 'qd';
    }

    private function download(string $tmp, string $name): BinaryFileResponse
    {
        return response()->download($tmp, $name)->deleteFileAfterSend(true);
    }
}
