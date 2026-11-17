<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoPhienHocLoiDocx;
use App\Support\QldtOto\DanhSachHuyPhienExcelExporter;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\QuyetDinhHuyPhienQuyetDinhDocx;
use App\Support\QldtOto\QuyetDinhHuyPhienSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class QuyetDinhHuyPhienController extends Controller
{
    private const SLUG = '2-quyet-dinh-huy-phien-kem-danh-sach-huy-phien';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $tenKhoa = '';
        if ($maKh !== '') {
            $tenKhoa = QuyetDinhHocVienForm::khoaOptions()
                ->firstWhere('MaKH', $maKh)?->TenKH ?? '';
        }

        $phien = $maKh !== '' ? QuyetDinhHuyPhienSupport::phienLoiTuDat($maKh) : [];

        return view('QldtOto.quyet-dinh-huy-phien', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'tenKhoa' => $tenKhoa,
            'form' => [
                'so_qd' => '',
                'ngay_quyet_dinh' => now()->format('Y-m-d'),
                'phien' => $phien !== [] ? $phien : [
                    [
                        'ten_khoa' => $tenKhoa,
                        'ho_ten_gv' => '',
                        'ho_ten' => '',
                        'ma_hv' => '',
                        'ma_phien' => '',
                        'phuong_an' => BaoCaoPhienHocLoiDocx::PHUONG_AN_MAC_DINH,
                        'ly_do' => '',
                    ],
                ],
            ],
        ]);
    }

    public function khoaJson(string $maKh): JsonResponse
    {
        return response()->json([
            'phien' => QuyetDinhHuyPhienSupport::phienLoiTuDat($maKh),
        ]);
    }

    public function exportWord(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payloadQuyetDinh($request);
        $tmp = storage_path('app/tmp/quyet-dinh-huy-phien-'.uniqid('', true).'.docx');
        try {
            QuyetDinhHuyPhienQuyetDinhDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        return response()->download($tmp, 'quyet-dinh-huy-phien.docx')->deleteFileAfterSend(true);
    }

    public function exportDanhSach(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payloadQuyetDinh($request);
        $tmp = storage_path('app/tmp/danh-sach-huy-phien-'.uniqid('', true).'.xlsx');
        try {
            (new DanhSachHuyPhienExcelExporter())->generate($payload['phien'], $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Excel thất bại: '.$e->getMessage())->withInput();
        }

        return response()->download($tmp, 'danh-sach-huy-phien.xlsx')->deleteFileAfterSend(true);
    }

    public function exportCaHai(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payloadQuyetDinh($request);
        $dir = storage_path('app/tmp/huy-phien-'.uniqid('', true));
        @mkdir($dir, 0755, true);
        $word = $dir.'/quyet-dinh-huy-phien.docx';
        $excel = $dir.'/danh-sach-huy-phien.xlsx';
        $zipPath = storage_path('app/tmp/huy-phien-'.uniqid('', true).'.zip');

        try {
            QuyetDinhHuyPhienQuyetDinhDocx::generate($payload, $word);
            (new DanhSachHuyPhienExcelExporter())->generate($payload['phien'], $excel);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất file thất bại: '.$e->getMessage())->withInput();
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Không tạo được file ZIP.')->withInput();
        }
        $zip->addFile($word, 'quyet-dinh-huy-phien.docx');
        $zip->addFile($excel, 'danh-sach-huy-phien.xlsx');
        $zip->close();

        return response()->download($zipPath, 'quyet-dinh-huy-phien-kem-danh-sach.zip')->deleteFileAfterSend(true);
    }

    /**
     * @return array{so_qd: string, ngay_quyet_dinh: string, phien: list<array{ten_khoa: string, ho_ten_gv: string, ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>}
     */
    private function payloadQuyetDinh(Request $request): array
    {
        $validated = $request->validate([
            'so_qd' => ['required', 'string', 'max:20'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'phien' => ['required', 'array', 'min:1'],
            'phien.*.ten_khoa' => ['nullable', 'string', 'max:120'],
            'phien.*.ho_ten_gv' => ['nullable', 'string', 'max:120'],
            'phien.*.ho_ten' => ['nullable', 'string', 'max:120'],
            'phien.*.ma_hv' => ['nullable', 'string', 'max:80'],
            'phien.*.ma_phien' => ['nullable', 'string', 'max:80'],
            'phien.*.phuong_an' => ['nullable', 'string', 'max:40'],
            'phien.*.ly_do' => ['nullable', 'string', 'max:500'],
        ], [
            'so_qd.required' => 'Nhập số quyết định.',
            'ngay_quyet_dinh.required' => 'Chọn ngày quyết định.',
            'phien.required' => 'Cần ít nhất một dòng phiên.',
        ]);

        $phien = [];
        foreach ($validated['phien'] as $row) {
            $normalized = [
                'ten_khoa' => trim((string) ($row['ten_khoa'] ?? '')),
                'ho_ten_gv' => trim((string) ($row['ho_ten_gv'] ?? '')),
                'ho_ten' => trim((string) ($row['ho_ten'] ?? '')),
                'ma_hv' => trim((string) ($row['ma_hv'] ?? '')),
                'ma_phien' => trim((string) ($row['ma_phien'] ?? '')),
                'phuong_an' => trim((string) ($row['phuong_an'] ?? '')) ?: BaoCaoPhienHocLoiDocx::PHUONG_AN_MAC_DINH,
                'ly_do' => trim((string) ($row['ly_do'] ?? '')),
            ];
            if ($normalized['ho_ten'] === '' && $normalized['ma_hv'] === '' && $normalized['ma_phien'] === '' && $normalized['ly_do'] === '') {
                continue;
            }
            $phien[] = $normalized;
        }

        if ($phien === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phien' => 'Cần ít nhất một dòng phiên có dữ liệu.',
            ]);
        }

        foreach ($phien as $i => $row) {
            foreach (['ho_ten', 'ma_hv', 'ma_phien', 'ly_do'] as $key) {
                if ($row[$key] === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "phien.$i.$key" => 'Dòng '.($i + 1).': điền đủ họ tên, mã HV, mã phiên và lý do.',
                    ]);
                }
            }
        }

        return [
            'so_qd' => preg_replace('/\D/', '', $validated['so_qd']) ?: trim($validated['so_qd']),
            'ngay_quyet_dinh' => (string) $validated['ngay_quyet_dinh'],
            'phien' => $phien,
        ];
    }
}