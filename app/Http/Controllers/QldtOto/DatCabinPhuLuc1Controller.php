<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatDSPhienLocResolver;
use App\Support\DaoTao\DatKhoaHocSelectOptions;
use App\Support\DaoTao\PhuLuc1DatBaoCaoHoTro as HoTro;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhuLuc1DatCabinDocx;
use App\Support\QldtOto\PhuLuc1DatCabinZip;
use App\Support\QldtOto\PhuLuc1TongPdfSession;
use App\Support\SatHach\BienBanTongPdfExporter;
use App\Support\SatHach\Utf8;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DatCabinPhuLuc1Controller extends Controller
{
    public const SLUG = '4-dat-cabin-phu-luc-1';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $filters = HoTro::filtersTuRequest($request);
        $resolved = DatDSPhienLocResolver::resolve($filters);
        $canAnalyzeViolations = $resolved['canAnalyzeViolations'];

        $hocViens = ($filters['ma_khoa_hoc'] ?? '') !== ''
            ? HoTro::danhSachHocVienTomTat($filters)
            : [];

        $khoaHocOptions = DatKhoaHocSelectOptions::fromDatDSPhien();

        $giaoVienOptions = DatDSPhien::query()
            ->selectRaw('MaGiaoVien, MAX(HoTenGiaoVien) as HoTenGiaoVien')
            ->whereNotNull('MaGiaoVien')
            ->where('MaGiaoVien', '!=', '')
            ->groupBy('MaGiaoVien')
            ->orderBy('MaGiaoVien')
            ->get();

        $xeOptions = DatDSPhien::query()
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->distinct()
            ->orderBy('BienSoXe')
            ->pluck('BienSoXe');

        $loaiKhoaHocOptions = DatDSPhien::query()
            ->whereNotNull('LoaiKhoaHoc')
            ->where('LoaiKhoaHoc', '!=', '')
            ->distinct()
            ->orderBy('LoaiKhoaHoc')
            ->pluck('LoaiKhoaHoc');

        $selectedHocVienOption = null;
        if (($filters['ma_hoc_vien'] ?? '') !== '') {
            [$maHv, $maKh] = DatDSPhienBoLoc::parseMaHocVienFilter($filters['ma_hoc_vien']);
            $selectedQuery = DatDSPhien::query()
                ->selectRaw('MaHocVien, MaKhoaHoc, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc')
                ->where('MaHocVien', $maHv)
                ->groupBy('MaHocVien', 'MaKhoaHoc');
            if ($maKh !== '') {
                $selectedQuery->where('MaKhoaHoc', $maKh);
            }
            $selectedRow = $selectedQuery->first();
            if ($selectedRow) {
                $selectedHocVienOption = [
                    'id' => DatDSPhienBoLoc::composeMaHocVienValue(
                        (string) $selectedRow->MaHocVien,
                        (string) $selectedRow->MaKhoaHoc,
                    ),
                    'text' => trim((string) ($selectedRow->HoTenHocVien ?? '')) !== ''
                        ? trim((string) $selectedRow->HoTenHocVien).' ('.$selectedRow->MaHocVien.')'
                        : (string) $selectedRow->MaHocVien,
                ];
            }
        }

        return view('QldtOto.dat-cabin-phu-luc-1', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'filters' => $filters,
            'hocViens' => $hocViens,
            'tongPhienLoc' => $resolved['filteredCount'],
            'canAnalyzeViolations' => $canAnalyzeViolations,
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienOptions' => $giaoVienOptions,
            'xeOptions' => $xeOptions,
            'loaiKhoaHocOptions' => $loaiKhoaHocOptions,
            'selectedHocVienOption' => $selectedHocVienOption,
            'ngay_bao_cao' => old('ngay_bao_cao', $request->input('ngay_bao_cao', now()->format('Y-m-d'))),
        ]);
    }

    public function exportMot(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = array_merge($this->validateXuat($request), $request->validate([
            'ma_hoc_vien' => ['required', 'string', 'max:120'],
        ]));

        $filters = $this->filtersTuValidated($validated);
        $maHocViens = HoTro::danhSachMaHocVien($filters, $validated['ma_hoc_vien']);
        if ($maHocViens === []) {
            return back()->withInput()->with('error', 'Không có phiên DAT cho học viên / bộ lọc hiện tại.');
        }

        try {
            $out = sys_get_temp_dir().'/'.uniqid('pl1-', true).'.docx';
            $this->xuatMotHocVien($maHocViens[0], $filters, $validated['ngay_bao_cao'], $out);

            return response()->download($out, PhuLuc1DatCabinDocx::tenFile($maHocViens[0]))->deleteFileAfterSend(true);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /** Giữ xuất ZIP Word (nhiều HV) — dùng khi cần từng file .docx. */
    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $this->validateXuat($request);
        $filters = $this->filtersTuValidated($validated);

        $chiChon = array_values(array_filter((array) $request->input('ma_hoc_vien_chon', [])));
        $maHocViens = HoTro::danhSachMaHocVien(
            $filters,
            trim((string) ($validated['ma_hoc_vien'] ?? '')) ?: null,
            $chiChon !== [] ? $chiChon : null,
        );

        if ($maHocViens === []) {
            return back()->withInput()->with('error', 'Không có học viên / phiên DAT phù hợp bộ lọc.');
        }

        try {
            if (count($maHocViens) === 1) {
                $out = sys_get_temp_dir().'/'.uniqid('pl1-', true).'.docx';
                $this->xuatMotHocVien($maHocViens[0], $filters, $validated['ngay_bao_cao'], $out);

                return response()->download($out, PhuLuc1DatCabinDocx::tenFile($maHocViens[0]))->deleteFileAfterSend(true);
            }

            $zip = sys_get_temp_dir().'/'.uniqid('pl1-zip-', true).'.zip';
            PhuLuc1DatCabinZip::taiZip($filters, $maHocViens, $validated['ngay_bao_cao'], $zip);

            return response()->download($zip, PhuLuc1DatCabinZip::tenFileZip($validated['ma_khoa_hoc']))->deleteFileAfterSend(true);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function exportTongStart(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateXuat($request);
            $filters = $this->filtersTuValidated($validated);
            $chiChon = array_values(array_filter(array_map('strval', (array) $request->input('ma_hoc_vien_chon', []))));
            $maHocViens = HoTro::danhSachMaHocVien(
                $filters,
                trim((string) ($validated['ma_hoc_vien'] ?? '')) ?: null,
                $chiChon !== [] ? $chiChon : null,
            );
            if ($maHocViens === []) {
                return $this->jsonTong(['message' => 'Không có học viên / phiên DAT phù hợp bộ lọc.'], 422);
            }

            BienBanTongPdfExporter::assertLibreOfficeAvailable();

            $tomTat = collect(HoTro::danhSachHocVienTomTat($filters))->keyBy(fn (array $r) => (string) $r['ma_hoc_vien']);
            $items = [];
            foreach ($maHocViens as $ma) {
                $row = $tomTat->get($ma);
                $items[] = [
                    'ma' => $ma,
                    'ten' => Utf8::sanitize((string) ($row['ho_ten'] ?? $ma)),
                ];
            }

            @ini_set('memory_limit', '512M');
            $job = PhuLuc1TongPdfSession::start(
                (string) $validated['ma_khoa_hoc'],
                (string) $validated['ngay_bao_cao'],
                $filters,
                $items,
            );

            return $this->jsonTong([
                'job_id' => $job['id'],
                'total' => count($job['items']),
                'items' => $job['items'],
            ]);
        } catch (Throwable $e) {
            return $this->jsonTong(['message' => Utf8::sanitize($e->getMessage()) ?: 'Không bắt đầu được phiên xuất.'], 500);
        }
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

            $job = PhuLuc1TongPdfSession::load($jobId);
            $items = $job['items'] ?? [];
            $total = count($items);
            $done = (int) ($job['done'] ?? 0);
            if ($total === 0) {
                return $this->jsonTong(['message' => 'Không có học viên để xuất.'], 422);
            }
            if ($done >= $total) {
                if (($job['phase'] ?? '') === 'pdf') {
                    return $this->jsonTong($this->pdfPhaseProgressPayload($job));
                }
                if (is_file((string) ($job['combined_pdf'] ?? ''))) {
                    return $this->jsonTong($this->finishedTongPayload($job, $jobId));
                }

                return $this->jsonTong(['message' => 'Phiên xuất đã hoàn tất.'], 422);
            }

            $ma = trim((string) $request->input('ma', ''));
            if ($ma === '') {
                $ma = (string) ($items[$done]['ma'] ?? '');
            }
            $expected = (string) ($items[$done]['ma'] ?? '');
            if ($ma === '' || ($expected !== '' && $ma !== $expected)) {
                return $this->jsonTong(['message' => 'Thứ tự xuất không khớp phiên.'], 422);
            }

            $dir = PhuLuc1TongPdfSession::dir($jobId);
            $pages = PhuLuc1TongPdfSession::pagesDir($dir);
            @mkdir($pages, 0755, true);
            $docx = $pages.DIRECTORY_SEPARATOR.sprintf('%04d-%s', $done + 1, PhuLuc1DatCabinDocx::tenFile($ma));
            $this->xuatMotHocVien($ma, (array) ($job['filters'] ?? []), (string) $job['ngay_bao_cao'], $docx);

            $files = $job['files'] ?? [];
            $files[] = $docx;
            $job['files'] = $files;
            $job['done'] = $done + 1;

            if ($job['done'] >= $total) {
                BienBanTongPdfExporter::assertLibreOfficeAvailable();
                $job['phase'] = 'pdf';
                $job['pdf_done'] = 0;
                $job['pdf_files'] = [];
                PhuLuc1TongPdfSession::save($job);

                return $this->jsonTong(array_merge([
                    'done' => $job['done'],
                    'total' => $total,
                    'name' => Utf8::sanitize((string) ($items[$done]['ten'] ?? $ma)),
                ], $this->pdfPhaseProgressPayload($job)));
            }

            PhuLuc1TongPdfSession::save($job);

            return $this->jsonTong([
                'done' => $job['done'],
                'total' => $total,
                'name' => Utf8::sanitize((string) ($items[$done]['ten'] ?? $ma)),
            ]);
        } catch (Throwable $e) {
            return $this->jsonTong(['message' => Utf8::sanitize($e->getMessage()) ?: 'Xuất Word thất bại.'], 500);
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

            $job = PhuLuc1TongPdfSession::load($jobId);
            if (($job['phase'] ?? '') !== 'pdf') {
                if (is_file((string) ($job['combined_pdf'] ?? ''))) {
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

            $dir = PhuLuc1TongPdfSession::dir($jobId);
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
                PhuLuc1TongPdfSession::save($job);

                $items = $job['items'] ?? [];
                $lastIdx = min($job['pdf_done'] - 1, $pdfTotal - 1);
                $label = Utf8::sanitize((string) ($items[$lastIdx]['ten'] ?? $items[$lastIdx]['ma'] ?? ''));

                return $this->jsonTong(array_merge(
                    $this->pdfPhaseProgressPayload($job),
                    ['name' => $label, 'pdf_batch' => count($chunk)]
                ));
            }

            /** @var list<string> $pdfFiles */
            $pdfFiles = $job['pdf_files'] ?? [];
            $exporter->mergePdfs($pdfFiles, (string) $job['combined_pdf']);
            $job['phase'] = 'done';
            PhuLuc1TongPdfSession::save($job);

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
            $session = PhuLuc1TongPdfSession::load($job);
        } catch (Throwable $e) {
            return redirect()
                ->route('qldtoto.'.MenuQldt::tenRouteTrang(self::SLUG).'.index')
                ->with('error', $e->getMessage());
        }

        $pdf = (string) ($session['combined_pdf'] ?? '');
        if (! is_file($pdf)) {
            return redirect()
                ->route('qldtoto.'.MenuQldt::tenRouteTrang(self::SLUG).'.index')
                ->with('error', 'Chưa có file PDF tổng.');
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($session['ma_khoa_hoc'] ?? 'khoa')) ?: 'khoa';

        return response()->download($pdf, 'phu-luc-1-dat-'.$safe.'.pdf');
    }

    /**
     * @return array<string, string>
     */
    private function validateXuat(Request $request): array
    {
        return $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ma_hoc_vien' => ['nullable', 'string', 'max:120'],
            'ma_phien' => ['nullable', 'string', 'max:80'],
            'ma_giao_vien' => ['nullable', 'string', 'max:50'],
            'loai_khoa_hoc' => ['nullable', 'string', 'max:50'],
            'bien_so_xe' => ['nullable', 'string', 'max:30'],
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
            'dat' => ['nullable', 'string', 'in:,dat,chua_dat'],
            'ngay_bao_cao' => ['required', 'date'],
        ]);
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
     * @return array<string, string>
     */
    private function downloadMeta(array $job, string $jobId): array
    {
        return [
            'download_url' => route('qldtoto.'.MenuQldt::tenRouteTrang(self::SLUG).'.export-tong.download', $jobId),
            'download_kind' => 'pdf',
        ];
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
                ? 'Đang gộp file PDF tổng…'
                : 'Đang chuyển PDF '.$nextFrom.'–'.$nextTo.'/'.$pdfTotal.' (LibreOffice)…',
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function jsonTong(array $data, int $status = 200): JsonResponse
    {
        return response()->json(
            $data,
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    /**
     * @param  array<string, string>  $validated
     * @return array<string, mixed>
     */
    private function filtersTuValidated(array $validated): array
    {
        return HoTro::filtersTuRequest(new Request([
            'ma_khoa_hoc' => $validated['ma_khoa_hoc'],
            'ma_hoc_vien' => $validated['ma_hoc_vien'] ?? '',
            'ma_phien' => $validated['ma_phien'] ?? '',
            'ma_giao_vien' => $validated['ma_giao_vien'] ?? '',
            'loai_khoa_hoc' => $validated['loai_khoa_hoc'] ?? '',
            'bien_so_xe' => $validated['bien_so_xe'] ?? '',
            'tu_ngay' => $validated['tu_ngay'] ?? '',
            'den_ngay' => $validated['den_ngay'] ?? '',
            'dat' => $validated['dat'] ?? '',
        ]));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function xuatMotHocVien(string $maHv, array $filters, string $ngayBaoCao, string $out): void
    {
        $ctx = HoTro::taiPhienVaKiemTra($filters);
        $theoHv = HoTro::nhomTheoHocVien($ctx['sessions']);
        $phien = $theoHv[$maHv] ?? [];
        if ($phien === []) {
            throw new \InvalidArgumentException('Không có phiên DAT cho học viên này.');
        }

        $tomTat = HoTro::baoCaoMotHocVien($maHv, $phien, $ctx['violationsById']);
        $ngaySinh = HoTro::ngaySinhTheoMaHocVien([$maHv]);

        PhuLuc1DatCabinDocx::generate([
            'ngay_bao_cao' => $ngayBaoCao,
            'ho_ten' => $tomTat['ho_ten'],
            'ma_hoc_vien' => $maHv,
            'ngay_sinh' => HoTro::ngaySinhHienThi($ngaySinh[$maHv] ?? null),
            'ma_khoa_hoc' => $tomTat['ma_khoa_hoc'],
            'ten_khoa_hoc' => $tomTat['ten_khoa_hoc'],
            'hang_dao_tao' => $tomTat['hang_dao_tao'],
            'phien' => $tomTat['phien'],
            'tong_thoi_gian' => $tomTat['tong_thoi_gian'],
            'tong_quang_duong' => $tomTat['tong_quang_duong'],
            'ket_luan' => HoTro::ketLuanChuongTrinh($maHv, $tomTat['hang_dao_tao'], $phien, $ctx['violationsById']),
        ], $out);
    }
}