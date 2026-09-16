<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Rules\ExcelUpload;
use App\Support\DaoTao\DatDSPhienExcelParser;
use App\Support\DaoTao\KetQuaDaoTaoExcelParser;
use App\Support\DaoTao\KetQuaDaoTaoUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NhapKetQuaDaoTaoController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.ket_qua_dao_tao';

    private const TEMP_DIR = 'ket-qua-dao-tao-pending';

    public function create(): View
    {
        return view('DaoTao.cong-cu-nhap.nhap-ket-qua-dao-tao');
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
            $preview = (new KetQuaDaoTaoExcelParser())->parse(
                $spreadsheet,
                $file->getClientOriginalName(),
                KetQuaDaoTaoExcelParser::DEFAULT_PREVIEW_SAMPLE
            );
            $analysis = (new KetQuaDaoTaoUpdater())->analyzeFromFile($fullPath);

            $request->session()->put(self::SESSION_KEY, [
                'stored_path' => $storedPath,
                'file_name' => $preview['file_name'],
                'sheet_name' => $preview['sheet_name'],
                'records' => $preview['records'],
                'meta' => array_merge($preview['meta'], $analysis['meta']),
                'updates' => array_slice($analysis['updates'], 0, KetQuaDaoTaoUpdater::PREVIEW_UPDATE_LIMIT),
                'update_total' => count($analysis['updates']),
                'skipped' => array_slice($analysis['skipped'], 0, 30),
                'skip_total' => count($analysis['skipped']),
            ]);

            return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.preview');
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
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        return view('DaoTao.cong-cu-nhap.xem-truoc-ket-qua-dao-tao', [
            'preview' => $preview,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingImport($request);

        return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        $storedPath = is_array($preview) ? ($preview['stored_path'] ?? null) : null;

        if (! is_string($storedPath) || $storedPath === '' || ! Storage::disk('local')->exists($storedPath)) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        if ((int) ($preview['update_total'] ?? $preview['meta']['update_count'] ?? 0) === 0) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.preview')
                ->with('error', 'Không có dòng nào để cập nhật.');
        }

        try {
            $result = (new KetQuaDaoTaoUpdater())->applyFromFile(
                Storage::disk('local')->path($storedPath)
            );
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.preview')
                ->with('error', 'Cập nhật thất bại: '.$e->getMessage());
        }

        $this->clearPendingImport($request);

        $msg = 'Đã cập nhật kết quả đào tạo cho '.$result['updated'].' học viên (NguoiLX_HoSo).';
        if ($result['skipped'] > 0) {
            $msg .= ' Bỏ qua '.$result['skipped'].' dòng.';
        }

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao')
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
