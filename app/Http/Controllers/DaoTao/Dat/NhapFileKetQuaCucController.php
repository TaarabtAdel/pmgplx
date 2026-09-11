<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Rules\ExcelUpload;
use App\Support\DaoTao\DatDSPhienExcelParser;
use App\Support\DaoTao\DatKetQuaCucExcelParser;
use App\Support\DaoTao\DatKetQuaCucUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NhapFileKetQuaCucController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.dat_ket_qua_cuc';

    private const TEMP_DIR = 'dat-ket-qua-cuc-pending';

    public function create(): View
    {
        return view('DaoTao.dat.nhap-ket-qua-cuc');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', new ExcelUpload(), 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $storedPath = null;
        $spreadsheet = null;

        try {
            $this->clearPendingImport($request);

            $storedPath = $file->storeAs(
                self::TEMP_DIR,
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                'local'
            );
            $fullPath = Storage::disk('local')->path($storedPath);

            $spreadsheet = DatDSPhienExcelParser::loadSpreadsheet($fullPath);
            $preview = (new DatKetQuaCucExcelParser())->parse(
                $spreadsheet,
                $file->getClientOriginalName(),
                DatKetQuaCucExcelParser::DEFAULT_PREVIEW_SAMPLE
            );

            $analysis = (new DatKetQuaCucUpdater())->analyzeFromFile($fullPath);
            $khoaStats = $preview['meta']['khoa_stats'] ?? [];

            foreach ($analysis['theo_khoa'] ?? [] as $maKhoaHoc => $courseStats) {
                $khoaStats[$maKhoaHoc] = array_merge($khoaStats[$maKhoaHoc] ?? [], [
                    'tong_phien_db' => (int) ($courseStats['tong_phien_db'] ?? 0),
                    'count_da_truyen' => (int) ($courseStats['da_truyen'] ?? 0),
                    'count_khong_chap_nhan' => max(
                        0,
                        (int) ($courseStats['cuoc_khong'] ?? 0) - (int) ($courseStats['khong_trong_file'] ?? 0)
                    ),
                    'khong_trong_file' => (int) ($courseStats['khong_trong_file'] ?? 0),
                    'bo_qua_da_truyen' => (int) ($courseStats['bo_qua_da_truyen'] ?? 0),
                ]);
            }

            $preview['meta']['khoa_stats'] = $khoaStats;
            $preview['meta']['tong_phien_db'] = (int) ($analysis['tong_phien_db'] ?? 0);
            $preview['meta']['count_da_truyen'] = (int) ($analysis['da_truyen'] ?? 0);
            $preview['meta']['count_khong_chap_nhan'] = max(
                0,
                (int) ($analysis['cuoc_khong'] ?? 0) - (int) ($analysis['khong_trong_file'] ?? 0)
            );
            $preview['meta']['khong_trong_file'] = (int) ($analysis['khong_trong_file'] ?? 0);
            $preview['meta']['bo_qua_da_truyen'] = (int) ($analysis['bo_qua_da_truyen'] ?? 0);
            $preview['meta']['file_khong_co_db'] = (int) ($analysis['file_khong_co_db'] ?? 0);

            $request->session()->put(self::SESSION_KEY, [
                'stored_path' => $storedPath,
                'file_name' => $preview['file_name'],
                'sheet_name' => $preview['sheet_name'],
                'records' => $preview['records'],
                'meta' => $preview['meta'],
            ]);

            return redirect()->route('daotao.pdt.dat.nhap-ket-qua-cuc.preview');
        } catch (Throwable $e) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            return back()->with('error', 'Không đọc được file Excel: '.$e->getMessage());
        } finally {
            DatDSPhienExcelParser::releaseSpreadsheet($spreadsheet);
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['meta']['record_count'])) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-ket-qua-cuc')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        $meta = $preview['meta'] ?? [];
        $previewLimit = (int) ($meta['preview_limit'] ?? DatKetQuaCucExcelParser::DEFAULT_PREVIEW_SAMPLE);

        return view('DaoTao.dat.xem-truoc-ket-qua-cuc', [
            'preview' => $preview,
            'detailRows' => $preview['records'] ?? [],
            'detailTotal' => (int) ($meta['record_count'] ?? 0),
            'detailLimit' => $previewLimit,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingImport($request);

        return redirect()->route('daotao.pdt.dat.nhap-ket-qua-cuc');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        $storedPath = is_array($preview) ? ($preview['stored_path'] ?? null) : null;

        if (! is_string($storedPath) || $storedPath === '' || ! Storage::disk('local')->exists($storedPath)) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-ket-qua-cuc')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        if ((int) ($preview['meta']['record_count'] ?? 0) === 0) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-ket-qua-cuc.preview')
                ->with('error', 'Không có dữ liệu để cập nhật.');
        }

        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            $result = (new DatKetQuaCucUpdater())->applyFromFile($fullPath);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-ket-qua-cuc.preview')
                ->with('error', 'Cập nhật thất bại: '.$e->getMessage());
        }

        $this->clearPendingImport($request);

        $msg = 'Đã cập nhật phân loại cục cho '
            .$result['so_khoa'].' khóa: '
            .$result['da_truyen'].' phiên → "'.DatKetQuaCucUpdater::PHAN_LOAI_DA_TRUYEN.'", '
            .$result['cuoc_khong'].' phiên → "'.DatKetQuaCucUpdater::PHAN_LOAI_CUOC_KHONG.'"';

        if ($result['khong_trong_file'] > 0) {
            $msg .= ' (trong đó '.$result['khong_trong_file'].' phiên trong DB không có trong file).';
        }

        if ($result['file_khong_co_db'] > 0) {
            $msg .= ' Bỏ qua '.$result['file_khong_co_db'].' dòng file không khớp phiên trong DB.';
        }

        if (($result['bo_qua_da_truyen'] ?? 0) > 0) {
            $msg .= ' Giữ nguyên '.$result['bo_qua_da_truyen'].' phiên đã có phân loại "'.DatKetQuaCucUpdater::PHAN_LOAI_DA_TRUYEN.'".';
        }

        if ($result['so_khoa'] <= 5) {
            $chiTiet = collect($result['theo_khoa'] ?? [])
                ->map(fn (array $khoa): string => $khoa['ma_khoa_hoc'].' ('.$khoa['da_truyen'].'/'.$khoa['tong_phien_db'].' đạt)')
                ->implode(', ');
            if ($chiTiet !== '') {
                $msg .= ' Khóa: '.$chiTiet.'.';
            }
        }

        return redirect()
            ->route('daotao.pdt.dat.nhap-ket-qua-cuc')
            ->with('success', $msg);
    }

    private function clearPendingImport(Request $request): void
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (is_array($preview) && ! empty($preview['stored_path'])) {
            Storage::disk('local')->delete((string) $preview['stored_path']);
        }

        $request->session()->forget(self::SESSION_KEY);
    }
}
