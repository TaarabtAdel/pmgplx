<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Rules\ExcelUpload;
use App\Support\DaoTao\HocVienSdtSaleTuFileExcel;
use App\Support\DaoTao\HocVienSdtSaleTuFileUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NhapFileSdtSaleHocVienController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.sdt_sale_hoc_vien';

    private const TEMP_DIR = 'sdt-sale-hoc-vien-pending';

    public function create(): View
    {
        return view('DaoTao.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien');
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

            $spreadsheet = HocVienSdtSaleTuFileExcel::loadSpreadsheet($fullPath);
            $rawRows = HocVienSdtSaleTuFileExcel::docTatCaSheet($spreadsheet);
            $analysis = (new HocVienSdtSaleTuFileUpdater())->analyzeAllSheets($rawRows);

            if (($analysis['meta']['row_count'] ?? 0) === 0) {
                Storage::disk('local')->delete($storedPath);

                return back()->with('error', 'Không đọc được dòng nào có CCCD và (SDT hoặc Sale) ở cột F/G/H.');
            }

            $request->session()->put(self::SESSION_KEY, [
                'stored_path' => $storedPath,
                'file_name' => $file->getClientOriginalName(),
                'meta' => $analysis['meta'],
                'sheets' => $analysis['sheets'],
            ]);

            return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.preview');
        } catch (Throwable $e) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            return back()->with('error', 'Không đọc được file Excel: '.$e->getMessage());
        } finally {
            HocVienSdtSaleTuFileExcel::releaseSpreadsheet($spreadsheet);
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['sheets'])) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        $sheetIndex = max(0, (int) $request->query('sheet', 0));
        $sheetIndex = min($sheetIndex, count($preview['sheets']) - 1);
        $activeSheet = $preview['sheets'][$sheetIndex];

        return view('DaoTao.cong-cu-nhap.xem-truoc-nhap-file-sdt-sale', [
            'preview' => $preview,
            'sheetIndex' => $sheetIndex,
            'activeSheet' => $activeSheet,
            'updater' => new HocVienSdtSaleTuFileUpdater(),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingImport($request);

        return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien');
    }

    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        $storedPath = is_array($preview) ? ($preview['stored_path'] ?? null) : null;

        if (! is_string($storedPath) || $storedPath === '' || ! Storage::disk('local')->exists($storedPath)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Phiên xem trước đã hết hạn.'], 410);
            }

            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        $sheetIndex = (int) $request->input('sheet_index', -1);
        $sheetTotal = (int) $request->input('sheet_total', count($preview['sheets'] ?? []));
        $finalize = (int) $request->input('chunk_finalize', 0) === 1;

        if ($sheetIndex < 0 || $sheetTotal < 1) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Thiếu tham số sheet.'], 422);
            }

            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.preview')
                ->with('error', 'Thiếu tham số xử lý.');
        }

        try {
            $step = (new HocVienSdtSaleTuFileUpdater())->applySheet(
                Storage::disk('local')->path($storedPath),
                $sheetIndex,
                auth()->id()
            );
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Cập nhật thất bại: '.$e->getMessage()], 500);
            }

            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.preview')
                ->with('error', 'Cập nhật thất bại: '.$e->getMessage());
        }

        $acc = $request->session()->get(self::SESSION_KEY.'.accum', [
            'saved' => 0, 'unchanged' => 0, 'not_found' => 0, 'ambiguous' => 0,
        ]);
        foreach (['saved', 'unchanged', 'not_found', 'ambiguous'] as $k) {
            $acc[$k] += (int) ($step[$k] ?? 0);
        }
        $request->session()->put(self::SESSION_KEY.'.accum', $acc);

        if ($finalize) {
            $this->clearPendingImport($request);
            $msg = 'Đã cập nhật '.$acc['saved'].' học viên (MANHLINH — SDT/Sale).';
            if ($acc['unchanged'] > 0) {
                $msg .= ' Không đổi: '.$acc['unchanged'].'.';
            }
            if ($acc['not_found'] > 0) {
                $msg .= ' Không khớp CCCD: '.$acc['not_found'].'.';
            }
            if ($acc['ambiguous'] > 0) {
                $msg .= ' CCCD nhiều HV: '.$acc['ambiguous'].'.';
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'message' => $msg,
                    'redirect' => route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien'),
                    'accum' => $acc,
                    'step' => $step,
                ]);
            }

            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien')
                ->with('success', $msg);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'step' => $step,
                'accum' => $acc,
                'sheet_index' => $sheetIndex,
            ]);
        }

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.preview')
            ->with('success', 'Đã xử lý sheet '.($sheetIndex + 1).'/'.$sheetTotal);
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
