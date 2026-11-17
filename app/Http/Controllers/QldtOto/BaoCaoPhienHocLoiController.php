<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoPhienHocLoiDocx;
use App\Support\QldtOto\DanhSachHuyPhienExcelExporter;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\QuyetDinhHuyPhienSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BaoCaoPhienHocLoiController extends Controller
{
    private const SLUG = '2-bao-cao-phien-hoc-loi';

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

        return view('QldtOto.bao-cao-phien-hoc-loi', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'tenKhoa' => $tenKhoa,
            'form' => $this->formDefaults($maKh, $tenKhoa, $phien),
        ]);
    }

    public function khoaJson(string $maKh): JsonResponse
    {
        return response()->json([
            'phien' => QuyetDinhHuyPhienSupport::phienLoiTuDat($maKh),
        ]);
    }

    /** Tìm học viên có phiên DAT trong khoá — chọn để điền họ tên + mã học viên. */
    public function timHocVien(Request $request): JsonResponse
    {
        $maKh = trim((string) $request->query('ma_kh', ''));
        $q = trim((string) $request->query('q', ''));
        if ($maKh === '' || mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%'.$q.'%';
        $rows = DatDSPhien::query()
            ->where('MaKhoaHoc', $maKh)
            ->whereNotNull('MaHocVien')
            ->where('MaHocVien', '!=', '')
            ->where(function (Builder $w) use ($like): void {
                $w->where('HoTenHocVien', 'like', $like)
                    ->orWhere('MaHocVien', 'like', $like);
            })
            ->selectRaw('MaHocVien, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc, MAX(HoTenGiaoVien) as HoTenGiaoVien')
            ->groupBy('MaHocVien')
            ->orderBy('HoTenHocVien')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $rows->map(fn ($r): array => [
                'id' => (string) $r->MaHocVien,
                'text' => trim((string) $r->HoTenHocVien).' ('.(string) $r->MaHocVien.')',
                'hv' => [
                    'ten_khoa' => trim((string) $r->TenKhoaHoc),
                    'ho_ten_gv' => trim((string) $r->HoTenGiaoVien),
                    'ho_ten' => trim((string) $r->HoTenHocVien),
                    'ma_hv' => trim((string) $r->MaHocVien),
                ],
            ])->values()->all(),
        ]);
    }

    public function exportWord(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payloadBaoCao($request);
        $tmp = storage_path('app/tmp/bao-cao-phien-hoc-loi-'.uniqid('', true).'.docx');
        try {
            BaoCaoPhienHocLoiDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        return response()->download($tmp, 'bao-cao-phien-hoc-loi.docx')->deleteFileAfterSend(true);
    }

    public function exportDanhSach(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payloadBaoCao($request);
        $tmp = storage_path('app/tmp/danh-sach-phien-hoc-loi-'.uniqid('', true).'.xlsx');
        try {
            (new DanhSachHuyPhienExcelExporter())->generate($payload['phien'], $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Excel thất bại: '.$e->getMessage())->withInput();
        }

        return response()->download($tmp, 'danh-sach-phien-hoc-loi.xlsx')->deleteFileAfterSend(true);
    }

    public function exportCaHai(Request $request): BinaryFileResponse|RedirectResponse
    {
        $payload = $this->payloadBaoCao($request);
        $dir = storage_path('app/tmp/bao-cao-phien-loi-'.uniqid('', true));
        @mkdir($dir, 0755, true);
        $word = $dir.'/bao-cao-phien-hoc-loi.docx';
        $excel = $dir.'/danh-sach-phien-hoc-loi.xlsx';
        $zipPath = storage_path('app/tmp/bao-cao-phien-loi-'.uniqid('', true).'.zip');

        try {
            BaoCaoPhienHocLoiDocx::generate($payload, $word);
            (new DanhSachHuyPhienExcelExporter())->generate($payload['phien'], $excel);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất file thất bại: '.$e->getMessage())->withInput();
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Không tạo được file ZIP.')->withInput();
        }
        $zip->addFile($word, 'bao-cao-phien-hoc-loi.docx');
        $zip->addFile($excel, 'danh-sach-phien-hoc-loi.xlsx');
        $zip->close();

        return response()->download($zipPath, 'bao-cao-phien-hoc-loi.zip')->deleteFileAfterSend(true);
    }

    /**
     * @param  list<array<string, string>>  $phien
     * @return array{ma_kh: string, ngay_bao_cao: string, de_xuat: string, phien: list<array<string, string>>}
     */
    private function formDefaults(string $maKh, string $tenKhoa, array $phien): array
    {
        return [
            'ma_kh' => $maKh,
            'ngay_bao_cao' => now()->format('Y-m-d'),
            'de_xuat' => BaoCaoPhienHocLoiDocx::DE_XUAT_MAC_DINH,
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
        ];
    }

    /**
     * @return array{ngay_bao_cao: string, de_xuat: string, phien: list<array{ten_khoa: string, ho_ten_gv: string, ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>}
     */
    private function payloadBaoCao(Request $request): array
    {
        $validated = $request->validate([
            'ngay_bao_cao' => ['required', 'date'],
            'de_xuat' => ['nullable', 'string', 'max:2000'],
            'phien' => ['required', 'array', 'min:1'],
            'phien.*.ten_khoa' => ['nullable', 'string', 'max:120'],
            'phien.*.ho_ten_gv' => ['nullable', 'string', 'max:120'],
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