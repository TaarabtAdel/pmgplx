<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class NhapHocVienTuFileController extends Controller
{
    private const SESSION_KEY = 'pmgplx.dm.hoc_vien.nhap_file.preview';

    private const PREVIEW_ROW_LIMIT = 50;

    public function create(): View
    {
        return view('PMGPLX.danh-muc.hoc-vien-nhap-tu-file');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:10240'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải là Excel (.xls, .xlsx) hoặc CSV.',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);

            $rows = [];
            foreach ($rawRows as $row) {
                $normalized = [];
                foreach ($row as $colIndex => $value) {
                    $normalized[$colIndex] = $this->normalizeCell($value);
                }

                $isEmpty = collect($normalized)->every(fn ($v) => $v === null || $v === '');
                if (! $isEmpty) {
                    $rows[] = $normalized;
                }
            }

            if ($rows === []) {
                return back()->with('error', 'File Excel không có dữ liệu.');
            }

            $maxCols = 0;
            foreach ($rows as $row) {
                $maxCols = max($maxCols, count($row));
            }

            $request->session()->put(self::SESSION_KEY, [
                'file_name' => $file->getClientOriginalName(),
                'rows' => $rows,
                'meta' => [
                    'row_count' => count($rows),
                    'col_count' => $maxCols,
                ],
            ]);

            return redirect()->route('pmgplx.dm.hoc-vien.nhap-file.preview');
        } catch (Throwable $e) {
            return back()->with('error', 'Không đọc được file Excel: '.$e->getMessage());
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('pmgplx.dm.hoc-vien.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        $rows = $preview['rows'];
        $limit = self::PREVIEW_ROW_LIMIT;

        return view('PMGPLX.danh-muc.hoc-vien-xem-truoc-nhap-file', [
            'preview' => $preview,
            'displayRows' => array_slice($rows, 0, $limit),
            'displayLimit' => $limit,
            'totalRows' => count($rows),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('pmgplx.dm.hoc-vien.nhap-file.create');
    }

    private function normalizeCell(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
