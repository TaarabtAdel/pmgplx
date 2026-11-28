<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Rules\ExcelUpload;
use App\Support\DaoTao\DatDSPhienExcelParser;
use App\Support\DaoTao\KetQuaSatHachCapNhatGplxUpdater;
use App\Support\DaoTao\KetQuaSatHachExcelParser;
use App\Support\DaoTao\KetQuaSatHachMauExcel;
use App\Support\DaoTao\KetQuaSatHachThuTienUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class NhapKetQuaSatHachController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.ket_qua_sat_hach';

    private const TEMP_DIR = 'ket-qua-sat-hach-pending';

    public function create(): View
    {
        return view('DaoTao.cong-cu-nhap.nhap-ket-qua-sat-hach');
    }

    public function downloadMau(): BinaryFileResponse
    {
        $path = KetQuaSatHachMauExcel::writeToTempFile();

        return response()->download($path, KetQuaSatHachMauExcel::DOWNLOAD_NAME)->deleteFileAfterSend(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', new ExcelUpload(), 'max:51200'],
            'dich' => ['required', 'in:thu_tien,cap_nhat_gplx'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'dich.required' => 'Chọn nơi cập nhật.',
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
            $parsed = (new KetQuaSatHachExcelParser())->parse($spreadsheet, $file->getClientOriginalName());
            $dich = (string) $request->input('dich');
            $analysis = $dich === 'cap_nhat_gplx'
                ? (new KetQuaSatHachCapNhatGplxUpdater())->analyzeFromFile($fullPath)
                : (new KetQuaSatHachThuTienUpdater())->analyzeFromFile($fullPath);

            $request->session()->put(self::SESSION_KEY, [
                'stored_path' => $storedPath,
                'file_name' => $parsed['file_name'],
                'sheet_name' => $parsed['sheet_name'],
                'dich' => $dich,
                'meta' => array_merge($parsed['meta'], $analysis['meta']),
                'rows' => $analysis['rows'],
            ]);

            return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.preview');
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
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        return view('DaoTao.cong-cu-nhap.xem-truoc-ket-qua-sat-hach', [
            'preview' => $preview,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingImport($request);

        return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        $storedPath = is_array($preview) ? ($preview['stored_path'] ?? null) : null;

        if (! is_string($storedPath) || $storedPath === '' || ! Storage::disk('local')->exists($storedPath)) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        if ((int) ($preview['meta']['will_update'] ?? 0) === 0) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.preview')
                ->with('error', 'Không có học viên/môn nào cần cập nhật.');
        }

        try {
            $fullPath = Storage::disk('local')->path($storedPath);
            $result = ($preview['dich'] ?? '') === 'cap_nhat_gplx'
                ? (new KetQuaSatHachCapNhatGplxUpdater())->applyFromFile($fullPath)
                : (new KetQuaSatHachThuTienUpdater())->applyFromFile($fullPath, auth()->id());
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.preview')
                ->with('error', 'Cập nhật thất bại: '.$e->getMessage());
        }

        $this->clearPendingImport($request);

        $msg = ($preview['dich'] ?? '') === 'cap_nhat_gplx'
            ? 'Đã ghi cột Kết quả (Tổng hợp) cho '.$result['updated_hv'].' học viên.'
            : 'Đã cập nhật kết quả thi cho '.$result['updated_hv'].' học viên ('.$result['updated_mon'].' lượt môn).';
        if ($result['skipped'] > 0) {
            $msg .= ' Bỏ qua '.$result['skipped'].' dòng không thay đổi hoặc không khớp.';
        }

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach')
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
