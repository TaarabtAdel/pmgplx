<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\MenuQldt;
use App\Support\QldtOto\DanhSachHuyPhienExcelExporter;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\QuyetDinhHuyPhienKemDanhSachDocx;
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
        $phien = [];
        if ($maKh !== '') {
            $phien = QuyetDinhHuyPhienSupport::phienLoiTuDat($maKh);
        }

        $form = [
            'ma_kh' => $maKh,
            'ngay_bao_cao' => now()->format('Y-m-d'),
            'de_xuat' => QuyetDinhHuyPhienKemDanhSachDocx::DE_XUAT_MAC_DINH,
            'phien' => $phien !== [] ? $phien : [
                [
                    'ho_ten' => '',
                    'ma_hv' => '',
                    'ma_phien' => '',
                    'phuong_an' => QuyetDinhHuyPhienKemDanhSachDocx::PHUONG_AN_MAC_DINH,
                    'ly_do' => '',
                ],
            ],
        ];

        return view('QldtOto.quyet-dinh-huy-phien', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
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
        $payload = $this->payload($request);
        $tmp = storage_path('app/tmp/bao-cao-huy-phien-'.uniqid('', true).'.docx');
        try {
            QuyetDinhHuyPhienKemDanhSachDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        return response()->download($tmp, 'bao-cao-phien-hoc-loi.docx')->deleteFileAfterSend(true);
    }

    public function exportDanhSach(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payload($request);
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
        $payload = $this->payload($request);
        $dir = storage_path('app/tmp/huy-phien-'.uniqid('', true));
        @mkdir($dir, 0755, true);
        $word = $dir.'/bao-cao-phien-hoc-loi.docx';
        $excel = $dir.'/danh-sach-huy-phien.xlsx';
        $zipPath = storage_path('app/tmp/huy-phien-'.uniqid('', true).'.zip');

        try {
            QuyetDinhHuyPhienKemDanhSachDocx::generate($payload, $word);
            (new DanhSachHuyPhienExcelExporter())->generate($payload['phien'], $excel);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất file thất bại: '.$e->getMessage())->withInput();
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Không tạo được file ZIP.')->withInput();
        }
        $zip->addFile($word, 'bao-cao-phien-hoc-loi.docx');
        $zip->addFile($excel, 'danh-sach-huy-phien.xlsx');
        $zip->close();

        return response()->download($zipPath, 'quyet-dinh-huy-phien-kem-danh-sach.zip')->deleteFileAfterSend(true);
    }

    /**
     * @return array{ngay_bao_cao: string, de_xuat: string, phien: list<array{ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>}
     */
    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'ngay_bao_cao' => ['required', 'date'],
            'de_xuat' => ['nullable', 'string', 'max:2000'],
            'phien' => ['required', 'array', 'min:1'],
            'phien.*.ho_ten' => ['nullable', 'string', 'max:120'],
            'phien.*.ma_hv' => ['nullable', 'string', 'max:80'],
            'phien.*.ma_phien' => ['nullable', 'string', 'max:80'],
            'phien.*.phuong_an' => ['nullable', 'string', 'max:40'],
            'phien.*.ly_do' => ['nullable', 'string', 'max:500'],
        ], [
            'ngay_bao_cao.required' => 'Chọn ngày lập báo cáo.',
            'phien.required' => 'Cần ít nhất một dòng phiên.',
        ]);

        $payload = QuyetDinhHuyPhienSupport::normalizePayload($validated);
        if ($payload['phien'] === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phien' => 'Cần ít nhất một dòng phiên có dữ liệu.',
            ]);
        }

        foreach ($payload['phien'] as $i => $row) {
            foreach (['ho_ten', 'ma_hv', 'ma_phien', 'ly_do'] as $key) {
                if ($row[$key] === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "phien.$i.$key" => 'Dòng '.($i + 1).': điền đủ họ tên, mã HV, mã phiên và lý do.',
                    ]);
                }
            }
        }

        return $payload;
    }
}
