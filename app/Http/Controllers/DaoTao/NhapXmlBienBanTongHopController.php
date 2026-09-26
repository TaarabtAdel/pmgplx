<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\SatHachBienBan;
use Illuminate\Database\Eloquent\Builder;
use App\Support\DaoTao\Jp2PhotoConverter;
use App\Support\SatHach\BienBanDocxGenerator;
use App\Support\SatHach\BienBanTongDocxCombiner;
use App\Support\SatHach\BienBanTongPdfExporter;
use App\Support\SatHach\BienBanTongHopPagePath;
use App\Support\SatHach\BienBanTongHopSession;
use App\Support\SatHach\BienBanTongHopZip;
use App\Support\SatHach\SatHachBienBanImporter;
use App\Support\SatHach\Utf8;
use App\Support\SatHach\XmlSatHachParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class NhapXmlBienBanTongHopController extends Controller
{
    public function create(Request $request): View
    {
        $maKySh = trim((string) $request->input('ma_ky_sh', ''));
        $tuKhoa = trim((string) $request->input('tu_khoa', ''));

        $kyOptions = SatHachBienBan::query()
            ->whereNotNull('MaKySH')
            ->where('MaKySH', '!=', '')
            ->distinct()
            ->orderBy('MaKySH')
            ->pluck('MaKySH');

        $items = $this->filteredBienBanQuery($maKySh, $tuKhoa)
            ->select([
                'Id', 'MaKySH', 'NgaySH', 'SoTT', 'MaDK', 'HoVaTen', 'NgaySinh',
                'SoCMT', 'SoBaoDanh', 'HangGPLX', 'KetQuaSH', 'FileNguon', 'NgayNhap',
            ])
            ->selectRaw("CASE WHEN AnhChanDung IS NULL OR AnhChanDung = '' THEN 0 ELSE 1 END as CoAnh")
            ->orderBy('SoBaoDanh')
            ->orderBy('SoTT')
            ->orderBy('Id')
            ->get();

        return view('DaoTao.cong-cu-nhap.nhap-xml-bien-ban', [
            'items' => $items,
            'kyOptions' => $kyOptions,
            'filters' => [
                'ma_ky_sh' => $maKySh,
                'tu_khoa' => $tuKhoa,
            ],
            'jp2Ready' => Jp2PhotoConverter::isAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uploadError = $this->uploadErrorMessage('file');
        if ($uploadError !== null) {
            return back()->with('error', $uploadError);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xml,txt', 'max:102400'],
        ], [
            'file.required' => 'Vui lòng chọn file XML.',
            'file.mimes' => 'File phải là XML (.xml).',
            'file.max' => 'File quá lớn (tối đa 100 MB).',
        ]);

        $file = $request->file('file');
        $storedPath = null;

        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(0);

            $storedPath = $file->store('temp/bien-ban-xml');
            $parsed = (new XmlSatHachParser())->parse(Storage::path($storedPath));
            $result = (new SatHachBienBanImporter())->import($parsed, $file->getClientOriginalName());
        } catch (Throwable $e) {
            return back()->with('error', 'Không nhập được file XML: '.$e->getMessage());
        } finally {
            if (is_string($storedPath) && $storedPath !== '') {
                Storage::delete($storedPath);
            }
        }

        $msg = 'Đã lưu '.$result['saved'].' thí sinh mới';
        if ($result['updated'] > 0) {
            $msg .= ', cập nhật '.$result['updated'].' thí sinh trùng kỳ/mã ĐK';
        }
        $msg .= ' vào bảng SatHachBienBan.';

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban', array_filter([
                'ma_ky_sh' => $result['ma_ky_sh'],
            ]))
            ->with('success', $msg);
    }

    public function export(int $id): BinaryFileResponse|RedirectResponse
    {
        $row = SatHachBienBan::query()->find($id);
        if ($row === null) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', 'Không tìm thấy thí sinh.');
        }

        $dir = storage_path('app/temp/bien-ban-docx');
        @mkdir($dir, 0755, true);
        $safeSbd = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($row->SoBaoDanh ?: $row->MaDK)) ?: 'hv';
        $path = $dir.DIRECTORY_SEPARATOR.'bien-ban-'.$id.'-'.$safeSbd.'.docx';

        try {
            (new BienBanDocxGenerator())->generateOne($row->toDocxRow(), $path);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', 'Xuất DOCX thất bại: '.$e->getMessage());
        }

        $downloadName = 'bien-ban-'.$safeSbd.'.docx';

        return response()->download($path, $downloadName)->deleteFileAfterSend(true);
    }

    public function exportTongStart(Request $request): JsonResponse
    {
        $maKySh = trim((string) $request->input('ma_ky_sh', ''));
        $tuKhoa = trim((string) $request->input('tu_khoa', ''));
        $ids = $this->parseExportIds($request);

        $query = $this->filteredBienBanQuery($maKySh, $tuKhoa);
        if ($ids !== []) {
            $query->whereIn('Id', $ids);
        }

        $rows = $query->orderBy('SoBaoDanh')->orderBy('SoTT')->orderBy('Id')->get();

        if ($rows->isEmpty()) {
            return $this->jsonTong(['message' => 'Không có thí sinh để xuất (theo bộ lọc / checkbox).'], 422);
        }

        if ($ids !== []) {
            if ($rows->count() !== count($ids)) {
                return $this->jsonTong(['message' => 'Có thí sinh đã chọn không nằm trong kết quả lọc hiện tại.'], 422);
            }
            $order = array_flip($ids);
            $rows = $rows->sortBy(static fn ($row) => $order[(int) $row->Id] ?? PHP_INT_MAX)->values();
        }

        $jobMaKy = $maKySh !== '' ? $maKySh : (string) ($rows->first()->MaKySH ?: 'loc');

        try {
            @ini_set('memory_limit', '512M');
            $job = BienBanTongHopSession::start($jobMaKy, $rows);
        } catch (Throwable $e) {
            return $this->jsonTong(['message' => Utf8::sanitize($e->getMessage())], 500);
        }

        return $this->jsonTong([
            'job_id' => $job['id'],
            'total' => count($job['items']),
            'items' => $job['items'],
        ]);
    }

    public function exportTongAdd(Request $request): JsonResponse
    {
        $jobId = trim((string) $request->input('job_id', ''));
        if ($jobId === '') {
            return $this->jsonTong(['message' => 'Thiếu phiên xuất.'], 422);
        }

        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(0);

            $job = BienBanTongHopSession::load($jobId);
            $items = $job['items'] ?? [];
            $total = count($items);
            $done = (int) ($job['done'] ?? 0);
            if ($total === 0) {
                return $this->jsonTong(['message' => 'Kỳ này không có thí sinh.'], 422);
            }
            if ($done >= $total) {
                if (($job['phase'] ?? '') === 'pdf') {
                    return $this->jsonTong($this->pdfPhaseProgressPayload($job));
                }
                if ($this->isTongDownloadReady($job)) {
                    return $this->jsonTong($this->finishedTongPayload($job, $jobId));
                }
            }

            $itemId = (int) $request->input('id', 0);
            if ($itemId <= 0) {
                $itemId = (int) ($items[$done]['id'] ?? 0);
            }
            $expectedId = (int) ($items[$done]['id'] ?? 0);
            if ($itemId <= 0 || ($expectedId > 0 && $itemId !== $expectedId)) {
                return $this->jsonTong(['message' => 'Thứ tự xuất không khớp phiên.'], 422);
            }

            $row = SatHachBienBan::query()->find($itemId);
            if ($row === null) {
                return $this->jsonTong(['message' => 'Không tìm thấy thí sinh #'.$itemId.'.'], 422);
            }

            $dir = BienBanTongHopSession::dir($jobId);
            $docx = BienBanTongHopPagePath::docxPath($dir, $done + 1, $row);
            (new BienBanDocxGenerator())->generateOne($row->toDocxRow(), $docx);

            $files = $job['files'] ?? [];
            $files[] = $docx;
            $job['files'] = $files;
            $job['pages_dir'] = BienBanTongHopPagePath::pagesDir($dir);
            $job['done'] = $done + 1;

            $finish = null;
            if ($job['done'] >= $total) {
                $finish = $this->finalizeTongExport($job, $dir);
                BienBanTongHopSession::save($job);

                return $this->jsonTong(array_merge([
                    'done' => $job['done'],
                    'total' => $total,
                    'name' => Utf8::sanitize((string) ($row->HoVaTen ?: $row->SoBaoDanh ?: '')),
                ], $finish));
            }

            BienBanTongHopSession::save($job);

            return $this->jsonTong([
                'done' => $job['done'],
                'total' => $total,
                'name' => Utf8::sanitize((string) ($row->HoVaTen ?: $row->SoBaoDanh ?: '')),
            ]);
        } catch (Throwable $e) {
            return $this->jsonTong(['message' => Utf8::sanitize($e->getMessage()) ?: 'Xuất tổng thất bại.'], 500);
        }
    }

    public function exportTongPdfStep(Request $request): JsonResponse
    {
        $jobId = trim((string) $request->input('job_id', ''));
        if ($jobId === '') {
            return $this->jsonTong(['message' => 'Thiếu phiên xuất.'], 422);
        }

        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(0);

            $job = BienBanTongHopSession::load($jobId);
            if (($job['phase'] ?? '') !== 'pdf') {
                if ($this->isTongDownloadReady($job)) {
                    return $this->jsonTong($this->finishedTongPayload($job, $jobId));
                }

                return $this->jsonTong(['message' => 'Phiên không ở bước chuyển PDF.'], 422);
            }

            /** @var list<string> $docxFiles */
            $docxFiles = $job['files'] ?? [];
            $pdfTotal = count($docxFiles);
            $pdfDone = (int) ($job['pdf_done'] ?? 0);
            if ($pdfTotal === 0) {
                return $this->jsonTong(['message' => 'Không có file Word để chuyển PDF.'], 422);
            }

            $dir = BienBanTongHopSession::dir($jobId);
            $exporter = new BienBanTongPdfExporter();

            if ($pdfDone < $pdfTotal) {
                $batchSize = BienBanTongPdfExporter::pdfBatchSize();
                $chunk = array_slice($docxFiles, $pdfDone, $batchSize);
                $newPdfs = $exporter->convertDocxBatch($chunk, $dir);
                /** @var list<string> $pdfFiles */
                $pdfFiles = $job['pdf_files'] ?? [];
                foreach ($newPdfs as $pdfPath) {
                    $pdfFiles[] = $pdfPath;
                }
                $job['pdf_files'] = $pdfFiles;
                $job['pdf_done'] = $pdfDone + count($chunk);
                BienBanTongHopSession::save($job);

                $items = $job['items'] ?? [];
                $lastIdx = min($job['pdf_done'] - 1, $pdfTotal - 1);
                $label = Utf8::sanitize((string) ($items[$lastIdx]['ten'] ?? $items[$lastIdx]['sbd'] ?? ''));

                return $this->jsonTong(array_merge(
                    $this->pdfPhaseProgressPayload($job),
                    ['name' => $label, 'pdf_batch' => count($chunk)]
                ));
            }

            /** @var list<string> $pdfFiles */
            $pdfFiles = $job['pdf_files'] ?? [];
            $exporter->mergePdfs($pdfFiles, (string) $job['combined_pdf']);
            $job['download_kind'] = 'pdf';
            $job['phase'] = 'done';
            BienBanTongHopSession::save($job);

            return $this->jsonTong(array_merge(
                [
                    'done' => (int) ($job['done'] ?? $pdfTotal),
                    'total' => $pdfTotal,
                    'phase' => 'done',
                    'pdf_done' => $pdfTotal,
                    'pdf_total' => $pdfTotal,
                    'status' => 'Đang gộp PDF…',
                ],
                $this->downloadMeta($job, $jobId)
            ));
        } catch (Throwable $e) {
            return $this->jsonTong(['message' => Utf8::sanitize($e->getMessage()) ?: 'Chuyển PDF thất bại.'], 500);
        }
    }

    public function exportTongDownload(string $job): BinaryFileResponse|RedirectResponse
    {
        try {
            $session = BienBanTongHopSession::load($job);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', $e->getMessage());
        }

        $kind = (string) ($session['download_kind'] ?? 'pages');
        $safeKy = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($session['ma_ky_sh'] ?? 'ky')) ?: 'ky';

        if ($kind === 'pdf') {
            $pdf = (string) ($session['combined_pdf'] ?? '');
            if (! is_file($pdf)) {
                return redirect()
                    ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                    ->with('error', 'Chưa có file PDF tổng.');
            }

            return response()->download($pdf, 'bien-ban-tong-'.$safeKy.'.pdf');
        }

        if ($kind === 'merge') {
            $docx = (string) ($session['combined_docx'] ?? '');
            if (! is_file($docx)) {
                BienBanTongHopSession::destroy($job);

                return redirect()
                    ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                    ->with('error', 'Chưa có file Word tổng.');
            }

            register_shutdown_function(static function () use ($job): void {
                BienBanTongHopSession::destroy($job);
            });

            return response()->download($docx, 'bien-ban-tong-'.$safeKy.'.docx');
        }

        $zip = (string) ($session['zip_path'] ?? '');
        if (! is_file($zip)) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', 'Chưa có file ZIP từng biên bản.');
        }

        return response()->download($zip, 'bien-ban-tung-file-'.$safeKy.'.zip');
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    private function finishedTongPayload(array $job, string $jobId): array
    {
        $total = count($job['items'] ?? []);

        return array_merge([
            'done' => (int) ($job['done'] ?? $total),
            'total' => $total,
        ], $this->downloadMeta($job, $jobId));
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, string|null>
     */
    private function downloadMeta(array $job, string $jobId): array
    {
        $kind = (string) ($job['download_kind'] ?? 'pages');

        return [
            'download_url' => route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export-tong.download', $jobId),
            'download_kind' => $kind,
            'pages_dir' => isset($job['pages_dir']) ? (string) $job['pages_dir'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, string|null>
     */
    private function finalizeTongExport(array &$job, string $dir): array
    {
        /** @var list<string> $files */
        $files = $job['files'] ?? [];
        $pagesDir = BienBanTongHopPagePath::pagesDir($dir);
        $job['pages_dir'] = $pagesDir;

        $zipPath = $dir.DIRECTORY_SEPARATOR.'bien-ban-tung-file.zip';
        (new BienBanTongHopZip())->createFromDirectory($pagesDir, $zipPath);
        $job['zip_path'] = $zipPath;
        $job['download_kind'] = 'pages';

        if ($this->shouldMergeTongFiles() && count($files) >= 1) {
            $driver = strtolower(trim((string) config('services.bien_ban_tong.merge_driver', 'pdf')));
            if ($driver === 'pdf') {
                BienBanTongPdfExporter::assertLibreOfficeAvailable();
                $job['phase'] = 'pdf';
                $job['pdf_done'] = 0;
                $job['pdf_files'] = [];
                $job['download_kind'] = 'pages';

                return $this->pdfPhaseProgressPayload($job);
            }

            (new BienBanTongDocxCombiner())->merge($files, (string) $job['combined_docx']);
            $job['download_kind'] = 'merge';
        }

        return $this->downloadMeta($job, (string) $job['id']);
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    private function pdfPhaseProgressPayload(array $job): array
    {
        /** @var list<string> $docxFiles */
        $docxFiles = $job['files'] ?? [];
        $pdfTotal = count($docxFiles);
        $pdfDone = (int) ($job['pdf_done'] ?? 0);
        $total = count($job['items'] ?? []);

        $batch = BienBanTongPdfExporter::pdfBatchSize();
        $nextFrom = $pdfDone + 1;
        $nextTo = min($pdfDone + $batch, $pdfTotal);

        return [
            'done' => (int) ($job['done'] ?? $total),
            'total' => $total,
            'phase' => 'pdf',
            'pdf_done' => $pdfDone,
            'pdf_total' => $pdfTotal,
            'pdf_batch_size' => $batch,
            'status' => $pdfDone >= $pdfTotal
                ? 'Đang gộp file PDF tổng (qpdf)…'
                : 'Đang chuyển PDF '.$nextFrom.'–'.$nextTo.'/'.$pdfTotal.' (LibreOffice, lô '.$batch.')…',
        ];
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function isTongDownloadReady(array $job): bool
    {
        $kind = (string) ($job['download_kind'] ?? 'pages');
        if ($kind === 'pdf') {
            return is_file((string) ($job['combined_pdf'] ?? ''));
        }
        if ($kind === 'merge') {
            return is_file((string) ($job['combined_docx'] ?? ''));
        }

        return is_file((string) ($job['zip_path'] ?? ''));
    }

    private function shouldMergeTongFiles(): bool
    {
        return filter_var(config('services.bien_ban_tong.merge_files', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function jsonTong(array $data, int $status = 200): JsonResponse
    {
        return response()->json(
            $data,
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    private function filteredBienBanQuery(string $maKySh, string $tuKhoa): Builder
    {
        $query = SatHachBienBan::query();

        if ($maKySh !== '') {
            $query->where('MaKySH', $maKySh);
        }
        if ($tuKhoa !== '') {
            $like = '%'.$tuKhoa.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('HoVaTen', 'like', $like)
                    ->orWhere('MaDK', 'like', $like)
                    ->orWhere('SoBaoDanh', 'like', $like)
                    ->orWhere('SoCMT', 'like', $like);
            });
        }

        return $query;
    }

    /**
     * @return list<int>
     */
    private function parseExportIds(Request $request): array
    {
        $raw = $request->input('ids', []);
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (is_numeric($value)) {
                $id = (int) $value;
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        return array_values($ids);
    }

    private function uploadErrorMessage(string $field): ?string
    {
        if (! isset($_FILES[$field])) {
            return null;
        }

        $error = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_OK) {
            return null;
        }

        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File quá lớn so với giới hạn upload của server.',
            UPLOAD_ERR_PARTIAL => 'File chỉ upload được một phần. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Vui lòng chọn file XML.',
            default => 'Upload thất bại (mã lỗi '.$error.'). Vui lòng thử lại.',
        };
    }
}
