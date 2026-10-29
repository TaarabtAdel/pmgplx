<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\PhanCongDaoTao;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhanCongGvXeTapLaiDocxExporter;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SoPhanCongGvXeTapLaiController extends Controller
{
    private const SLUG = '1-so-phan-cong-giao-vien-xe-tham-gia-giang-day-cua-khoa';

    private const SESSION_KEY = 'qldtoto.preview.so_phan_cong_gv_xe';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $defaults = [
            'ma_kh' => $maKh,
            'ten_khoa' => '',
            'ten_khoa_doc' => '',
            'nam' => (int) now()->format('Y'),
            'rows' => [
                ['ma_gv' => '', 'ho_ten' => '', 'tu_ngay' => '', 'den_ngay' => '', 'bien_so' => '', 'noi_dung' => 'GVLT'],
            ],
        ];

        if ($khoa instanceof KhoaHoc) {
            $defaults['ten_khoa'] = PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa);
            $defaults['ten_khoa_doc'] = PhanCongGvXeTapLaiFormSupport::tenKhoaNganTuPm($khoa);
        }

        $sessionPreview = $request->session()->get(self::SESSION_KEY);
        if (is_array($sessionPreview)) {
            $formDraft = $sessionPreview['form'] ?? null;
            if (! is_array($formDraft) || ($formDraft['rows'] ?? []) === []) {
                $formDraft = self::formDraftFromPreview($sessionPreview);
            }
            if (is_array($formDraft) && ($formDraft['rows'] ?? []) !== []) {
                $defaults = array_merge($defaults, $formDraft);
            }
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.so-phan-cong-gv-xe-tap-lai.form', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'giaoVienOptions' => PhanCongGvXeTapLaiFormSupport::giaoVienSelectOptions(),
            'xeTapOptions' => PhanCongGvXeTapLaiFormSupport::xeSelectOptions(),
            'form' => $defaults,
            'noiDungOptions' => PhanCongGvXeTapLaiFormSupport::NOI_DUNG_OPTIONS,
        ]);
    }

    public function previewStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ten_khoa' => ['required', 'string', 'max:120'],
            'ten_khoa_doc' => ['required', 'string', 'max:40'],
            'nam' => ['required', 'integer', 'min:2000', 'max:2100'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.ma_gv' => ['nullable', 'string', 'max:20'],
            'rows.*.ho_ten' => ['nullable', 'string', 'max:120'],
            'rows.*.tu_ngay' => ['nullable', 'string', 'max:20'],
            'rows.*.den_ngay' => ['nullable', 'string', 'max:20'],
            'rows.*.bien_so' => ['nullable', 'string', 'max:30'],
            'rows.*.noi_dung' => ['nullable', 'string', 'max:40'],
        ]);

        if (! KhoaHoc::query()->where('MaKH', $validated['ma_kh'])->exists()) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $rowsInput = array_values(array_filter($validated['rows'], function (array $row): bool {
            return trim((string) ($row['ma_gv'] ?? '')) !== ''
                || trim((string) ($row['ho_ten'] ?? '')) !== ''
                || trim((string) ($row['tu_ngay'] ?? '')) !== ''
                || trim((string) ($row['den_ngay'] ?? '')) !== ''
                || trim((string) ($row['bien_so'] ?? '')) !== ''
                || trim((string) ($row['noi_dung'] ?? '')) !== '';
        }));

        $rowsInput = PhanCongGvXeTapLaiFormSupport::resolveGiaoVienRows($rowsInput);

        if ($rowsInput === []) {
            return back()->withErrors(['rows' => 'Thêm ít nhất một dòng phân công.'])->withInput();
        }

        foreach ($rowsInput as $i => $row) {
            $nd = trim((string) ($row['noi_dung'] ?? ''));
            if ($nd === '' || ! in_array($nd, PhanCongGvXeTapLaiFormSupport::NOI_DUNG_OPTIONS, true)) {
                return back()->withErrors(["rows.{$i}.noi_dung" => 'Chọn nội dung: GVLT, GVTH hoặc Xe tự động.'])->withInput();
            }
        }

        $records = PhanCongGvXeTapLaiFormSupport::recordsFromForm($validated['ten_khoa'], $rowsInput);
        $annotated = PhanCongDaoTao::annotatePreviewRecords($records);
        $saveable = PhanCongDaoTao::saveableRecords($annotated['records']);

        $preview = [
            'ma_kh' => $validated['ma_kh'],
            'ten_khoa' => $validated['ten_khoa'],
            'ten_khoa_doc' => $validated['ten_khoa_doc'],
            'nam' => (int) $validated['nam'],
            'records' => $annotated['records'],
            'meta' => [
                'record_count' => count($annotated['records']),
                'save_count' => count($saveable),
                'update_count' => $annotated['update_count'],
                'create_count' => $annotated['create_count'],
                'skip_count' => count($annotated['records']) - count($saveable),
            ],
            'validation_errors' => PhanCongDaoTao::collectRowErrors($annotated['records']),
            'overlap_errors' => [],
        ];

        if ($preview['validation_errors'] === []) {
            $preview['overlap_errors'] = PhanCongDaoTao::validateOverlaps($annotated['records']);
        }

        $preview['form'] = self::formDraftFromRowsInput($validated, $rowsInput);

        $request->session()->put(self::SESSION_KEY, $preview);

        return redirect()->route('qldtoto.so-phan-cong-gv-xe-tap-lai.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['records'])) {
            return redirect()
                ->route('qldtoto.so-phan-cong-gv-xe-tap-lai.index')
                ->with('error', 'Chưa có dữ liệu xem trước.');
        }

        $saveableCount = (int) ($preview['meta']['save_count'] ?? count(PhanCongDaoTao::saveableRecords($preview['records'])));

        return view('QldtOto.so-phan-cong-gv-xe-tap-lai.preview', [
            'preview' => $preview,
            'saveableCount' => $saveableCount,
            'canImport' => ($preview['validation_errors'] ?? []) === [] && $saveableCount > 0,
        ]);
    }

    public function export(Request $request, PhanCongGvXeTapLaiDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['records'])) {
            return redirect()
                ->route('qldtoto.so-phan-cong-gv-xe-tap-lai.index')
                ->with('error', 'Phiên xem trước đã hết hạn.');
        }

        $docxRows = PhanCongGvXeTapLaiFormSupport::docxRows(
            (string) ($preview['ten_khoa_doc'] ?? $preview['ten_khoa'] ?? ''),
            $preview['records']
        );

        $tmp = storage_path('app/tmp/so-phan-cong-gv-xe-'.uniqid('', true).'.docx');
        $exporter->generate(
            (string) ($preview['ten_khoa_doc'] ?? ''),
            (int) ($preview['nam'] ?? now()->year),
            $docxRows,
            $tmp
        );

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($preview['ma_kh'] ?? 'khoa')) ?: 'khoa';

        return response()->download($tmp, 'so-phan-cong-gv-xe-'.$safe.'.docx')->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['records'])) {
            return redirect()
                ->route('qldtoto.so-phan-cong-gv-xe-tap-lai.index')
                ->with('error', 'Phiên xem trước đã hết hạn.');
        }

        if (! $request->boolean('nhap_phan_cong')) {
            return back()->with('error', 'Chưa chọn «Nhập vào Phân công giáo viên».');
        }

        if (($preview['validation_errors'] ?? []) !== []) {
            return back()->with('error', 'Dữ liệu còn lỗi, không thể nhập vào Phân công đào tạo.');
        }

        if (PhanCongDaoTao::saveableRecords($preview['records']) === []) {
            return back()->with('error', 'Không có dòng nào đủ điều kiện nhập (GVLT/GVTH có giáo viên).');
        }

        try {
            $result = PhanCongDaoTao::importFromPreview($preview);
        } catch (\Throwable $e) {
            return back()->with('error', 'Nhập DB thất bại: '.$e->getMessage());
        }

        if ($result['errors'] !== []) {
            return back()->with('error', implode(' ', $result['errors']));
        }

        $msg = "Đã nhập {$result['saved']} dòng vào Phân công đào tạo ({$result['khoa_count']} khoá).";

        $request->session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('daotao.pdt.phan-cong-dao-tao.danh-sach')
            ->with('success', $msg);
    }

    public function cancel(Request $request): RedirectResponse
    {
        return redirect()->route('qldtoto.so-phan-cong-gv-xe-tap-lai.index');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<array<string, mixed>>  $rowsInput
     * @return array{ma_kh: string, ten_khoa: string, ten_khoa_doc: string, nam: int, rows: list<array<string, string>>}
     */
    /**
     * @param  array<string, mixed>  $preview
     * @return array{ma_kh: string, ten_khoa: string, ten_khoa_doc: string, nam: int, rows: list<array<string, string>>}|null
     */
    private static function formDraftFromPreview(array $preview): ?array
    {
        $records = $preview['records'] ?? [];
        if (! is_array($records) || $records === []) {
            return null;
        }

        $rows = [];
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            $rows[] = [
                'ma_gv' => '',
                'ho_ten' => trim((string) ($record['HoTen'] ?? '')),
                'tu_ngay' => trim((string) ($record['TuNgay'] ?? '')),
                'den_ngay' => trim((string) ($record['DenNgay'] ?? '')),
                'bien_so' => trim((string) ($record['BienSo'] ?? '')),
                'noi_dung' => trim((string) ($record['NoiDungGiangDay'] ?? '')),
            ];
        }

        if ($rows === []) {
            return null;
        }

        return [
            'ma_kh' => (string) ($preview['ma_kh'] ?? ''),
            'ten_khoa' => (string) ($preview['ten_khoa'] ?? ''),
            'ten_khoa_doc' => (string) ($preview['ten_khoa_doc'] ?? ''),
            'nam' => (int) ($preview['nam'] ?? now()->year),
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<array<string, mixed>>  $rowsInput
     * @return array{ma_kh: string, ten_khoa: string, ten_khoa_doc: string, nam: int, rows: list<array<string, string>>}
     */
    private static function formDraftFromRowsInput(array $validated, array $rowsInput): array
    {
        $rows = [];
        foreach ($rowsInput as $row) {
            $rows[] = [
                'ma_gv' => trim((string) ($row['ma_gv'] ?? '')),
                'ho_ten' => trim((string) ($row['ho_ten'] ?? '')),
                'tu_ngay' => trim((string) ($row['tu_ngay'] ?? '')),
                'den_ngay' => trim((string) ($row['den_ngay'] ?? '')),
                'bien_so' => trim((string) ($row['bien_so'] ?? '')),
                'noi_dung' => trim((string) ($row['noi_dung'] ?? '')),
            ];
        }

        return [
            'ma_kh' => (string) $validated['ma_kh'],
            'ten_khoa' => (string) $validated['ten_khoa'],
            'ten_khoa_doc' => (string) $validated['ten_khoa_doc'],
            'nam' => (int) $validated['nam'],
            'rows' => $rows,
        ];
    }

    public function khoaJson(string $maKh): \Illuminate\Http\JsonResponse
    {
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa instanceof KhoaHoc) {
            return response()->json(['message' => 'Không tìm thấy khoá.'], 404);
        }

        return response()->json([
            'ma_kh' => $khoa->MaKH,
            'ten_khoa' => PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa),
            'ten_khoa_doc' => PhanCongGvXeTapLaiFormSupport::tenKhoaNganTuPm($khoa),
        ]);
    }
}
