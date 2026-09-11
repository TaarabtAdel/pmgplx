<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Rules\ExcelUpload;
use App\Support\DaoTao\DatDSPhienExcelParser;
use App\Support\DaoTao\DatPhanCongHocVienExcelParser;
use App\Support\DaoTao\DatPhanCongHocVienImporter;
use App\Support\DaoTao\DatPhanCongHocVienSaver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class NhapFilePhanCongHocVienController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.dat_phan_cong_hoc_vien';

    private const TEMP_DIR = 'dat-phan-cong-hv-pending';

    public function create(): View
    {
        return view('DaoTao.dat.nhap-phan-cong-hoc-vien');
    }

    public function saveManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ma_hoc_vien' => ['required', 'string', 'max:50'],
            'ho_ten_hoc_vien' => ['nullable', 'string', 'max:255'],
            'ma_giao_vien' => ['required', 'string', 'max:50'],
            'bien_so_xe' => ['nullable', 'string', 'max:50'],
            'bien_so_xe_tu_dong' => ['nullable', 'string', 'max:50'],
        ], [
            'ma_khoa_hoc.required' => 'Nhập mã khóa học.',
            'ma_hoc_vien.required' => 'Nhập mã học viên.',
            'ma_giao_vien.required' => 'Nhập mã giáo viên.',
        ]);

        $editItem = null;
        if (! empty($validated['id'])) {
            $editItem = DatPhanCongHocVien::query()->findOrFail((int) $validated['id']);
        }

        try {
            $result = DatPhanCongHocVienSaver::upsert(
                $editItem ? (string) $editItem->MaKhoaHoc : trim((string) $validated['ma_khoa_hoc']),
                trim((string) $validated['ma_hoc_vien']),
                trim((string) $validated['ma_giao_vien']),
                trim((string) ($validated['bien_so_xe'] ?? '')),
                trim((string) ($validated['ho_ten_hoc_vien'] ?? '')),
                $editItem ? 'Sửa thủ công' : 'Nhập thủ công',
                $editItem ? (int) $editItem->Id : null,
                trim((string) ($validated['bien_so_xe_tu_dong'] ?? ''))
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Lưu thất bại: '.$e->getMessage());
        }

        $msg = $result['created']
            ? 'Đã thêm phân công học viên.'
            : 'Đã cập nhật phân công học viên (mã HV trùng khóa).';

        return back()->with('success', $msg);
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
            $preview = (new DatPhanCongHocVienExcelParser())->parse(
                $spreadsheet,
                $file->getClientOriginalName(),
                DatPhanCongHocVienExcelParser::DEFAULT_PREVIEW_SAMPLE
            );

            $request->session()->put(self::SESSION_KEY, [
                'stored_path' => $storedPath,
                'file_name' => $preview['file_name'],
                'sheet_name' => $preview['sheet_name'],
                'records' => $preview['records'],
                'meta' => $preview['meta'],
            ]);

            return redirect()->route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.preview');
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
                ->route('daotao.pdt.dat.nhap-phan-cong-hoc-vien')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        $meta = $preview['meta'] ?? [];

        return view('DaoTao.dat.xem-truoc-phan-cong-hoc-vien', [
            'preview' => $preview,
            'detailRows' => $preview['records'] ?? [],
            'detailTotal' => (int) ($meta['record_count'] ?? 0),
            'detailLimit' => (int) ($meta['preview_limit'] ?? DatPhanCongHocVienExcelParser::DEFAULT_PREVIEW_SAMPLE),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingImport($request);

        return redirect()->route('daotao.pdt.dat.nhap-phan-cong-hoc-vien');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);

        if (! is_array($preview) || empty($preview['meta']['save_count'])) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.preview')
                ->with('error', 'Không có dòng hợp lệ để lưu.');
        }

        try {
            $result = (new DatPhanCongHocVienImporter())->importFromPreview($preview);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.preview')
                ->with('error', 'Lưu thất bại: '.$e->getMessage());
        }

        $this->clearPendingImport($request);

        $msg = 'Đã nhập phân công khóa '.$result['ma_khoa_hoc'].': '
            .$result['saved'].' mới';
        if (($result['updated'] ?? 0) > 0) {
            $msg .= ', '.$result['updated'].' cập nhật';
        }
        $msg .= ' học viên';
        if (($result['deleted'] ?? 0) > 0) {
            $msg .= ' (bỏ '.$result['deleted'].' bản ghi không còn trong file)';
        }
        $msg .= '.';

        return redirect()
            ->route('daotao.pdt.dat.phan-cong-hoc-vien', ['ma_khoa_hoc' => $result['ma_khoa_hoc']])
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
