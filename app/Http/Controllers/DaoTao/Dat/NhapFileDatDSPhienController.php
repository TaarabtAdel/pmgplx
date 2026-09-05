<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienExcelParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NhapFileDatDSPhienController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.dat_ds_phien';

    private const TEMP_DIR = 'dat-import-pending';

    public function create(): View
    {
        return view('DaoTao.dat.nhap-file');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải là Excel (.xls, .xlsx).',
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
            $preview = (new DatDSPhienExcelParser())->parse(
                $spreadsheet,
                $file->getClientOriginalName(),
                DatDSPhienExcelParser::DEFAULT_PREVIEW_SAMPLE
            );

            $request->session()->put(self::SESSION_KEY, [
                'stored_path' => $storedPath,
                'file_name' => $preview['file_name'],
                'sheet_name' => $preview['sheet_name'],
                'records' => $preview['records'],
                'meta' => $preview['meta'],
            ]);

            return redirect()->route('daotao.pdt.dat.nhap-du-lieu-phien.preview');
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
                ->route('daotao.pdt.dat.nhap-du-lieu-phien')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        $meta = $preview['meta'] ?? [];
        $previewLimit = (int) ($meta['preview_limit'] ?? DatDSPhienExcelParser::DEFAULT_PREVIEW_SAMPLE);

        return view('DaoTao.dat.xem-truoc-nhap-file', [
            'preview' => $preview,
            'detailRows' => $preview['records'] ?? [],
            'detailTotal' => (int) ($meta['record_count'] ?? 0),
            'detailLimit' => $previewLimit,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingImport($request);

        return redirect()->route('daotao.pdt.dat.nhap-du-lieu-phien');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        $storedPath = is_array($preview) ? ($preview['stored_path'] ?? null) : null;

        if (! is_string($storedPath) || $storedPath === '' || ! Storage::disk('local')->exists($storedPath)) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-du-lieu-phien')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        if ((int) ($preview['meta']['record_count'] ?? 0) === 0) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-du-lieu-phien.preview')
                ->with('error', 'Không có dữ liệu để lưu.');
        }

        $fullPath = Storage::disk('local')->path($storedPath);
        $fileName = (string) ($preview['file_name'] ?? basename($storedPath));

        try {
            $result = DatDSPhien::upsertFromExcelFile($fullPath, $fileName);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-du-lieu-phien.preview')
                ->with('error', 'Lưu DB thất bại: '.$e->getMessage());
        }

        $this->clearPendingImport($request);

        $msgParts = [];
        if ($result['saved'] > 0) {
            $msgParts[] = "{$result['saved']} mới";
        }
        if ($result['updated'] > 0) {
            $msgParts[] = "{$result['updated']} cập nhật";
        }
        $msg = 'Đã lưu DB: '.($msgParts !== [] ? implode(', ', $msgParts) : '0').' phiên học.';
        if ($result['skipped'] > 0) {
            $msg .= " Bỏ qua {$result['skipped']} dòng thiếu mã phiên học.";
        }

        return redirect()
            ->route('daotao.pdt.dat.nhap-du-lieu-phien')
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
