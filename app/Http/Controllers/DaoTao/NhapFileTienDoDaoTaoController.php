<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\TienDoDaoTao;
use App\Support\DaoTao\TienDoDaoTaoExcelParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class NhapFileTienDoDaoTaoController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.tien_do_nhap_file';

    private const PREVIEW_DETAIL_LIMIT = 300;

    public function create(): View
    {
        return view('DaoTao.tien-do.nhap-file');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải là Excel (.xls, .xlsx).',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $preview = (new TienDoDaoTaoExcelParser())->parse($spreadsheet, $file->getClientOriginalName());

            $request->session()->put(self::SESSION_KEY, $preview);

            return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.preview');
        } catch (Throwable $e) {
            return back()->with('error', 'Không đọc được file Excel: '.$e->getMessage());
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['sheets'])) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        $sheetIndex = max(0, (int) $request->query('sheet', 0));
        $sheetIndex = min($sheetIndex, count($preview['sheets']) - 1);
        $activeSheet = $preview['sheets'][$sheetIndex];
        $records = $activeSheet['records'] ?? [];
        $detailLimit = self::PREVIEW_DETAIL_LIMIT;

        return view('DaoTao.tien-do.xem-truoc-nhap-file', [
            'preview' => $preview,
            'sheetIndex' => $sheetIndex,
            'activeSheet' => $activeSheet,
            'detailRows' => array_slice($records, 0, $detailLimit),
            'detailTotal' => count($records),
            'detailLimit' => $detailLimit,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['sheets'])) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        $rows = TienDoDaoTao::rowsFromPreview($preview);
        if ($rows === []) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.preview')
                ->with('error', 'Không có dữ liệu để lưu.');
        }

        try {
            $result = TienDoDaoTao::upsertFromPreview($preview);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.preview')
                ->with('error', 'Lưu DB thất bại: '.$e->getMessage());
        }

        $request->session()->forget(self::SESSION_KEY);

        $msg = "Đã lưu {$result['saved']} dòng vào TienDoDaoTao (DB MANHLINH).";
        if ($result['updated_classes'] > 0) {
            $msg .= " Cập nhật {$result['updated_classes']} lớp trùng mã khóa-lớp.";
        }
        if ($result['new_classes'] > 0) {
            $msg .= " Thêm mới {$result['new_classes']} lớp.";
        }

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao')
            ->with('success', $msg);
    }
}
