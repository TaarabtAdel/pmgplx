<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Support\DaoTao\DangKyKhoaHocXmlParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class NhapFileInBangTenController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.in_bang_ten';

    public function create(): View
    {
        return view('DaoTao.cong-cu-nhap.nhap-file-in-bang-ten');
    }

    public function store(Request $request): RedirectResponse
    {
        $uploadError = $this->uploadErrorMessage('file');
        if ($uploadError !== null) {
            return back()->with('error', $uploadError);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xml,txt', 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file XML.',
            'file.mimes' => 'File phải là XML (.xml).',
            'file.max' => 'File quá lớn (tối đa 50 MB).',
        ]);

        $file = $request->file('file');
        $this->clearTempFile($request);

        try {
            $storedPath = $file->store('temp/in-bang-ten');
            $preview = (new DangKyKhoaHocXmlParser())->parse(
                Storage::path($storedPath),
                $file->getClientOriginalName()
            );

            $request->session()->put(self::SESSION_KEY, [
                'temp_path' => $storedPath,
                'file_name' => $preview['file_name'],
                'khoa_hoc' => $preview['khoa_hoc'],
                'meta' => $preview['meta'],
            ]);

            return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten.preview');
        } catch (Throwable $e) {
            if (isset($storedPath)) {
                Storage::delete($storedPath);
            }

            return back()->with('error', 'Không đọc được file XML: '.$e->getMessage());
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $this->loadPreview($request);
        if ($preview instanceof RedirectResponse) {
            return $preview;
        }

        return view('DaoTao.cong-cu-nhap.xem-truoc-in-bang-ten', [
            'preview' => $preview,
        ]);
    }

    public function printSheet(Request $request): View|RedirectResponse
    {
        $preview = $this->loadPreview($request);
        if ($preview instanceof RedirectResponse) {
            return $preview;
        }

        return view('DaoTao.cong-cu-nhap.in-bang-ten-print', [
            'preview' => $preview,
        ]);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function loadPreview(Request $request): array|RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);
        $tempPath = is_array($session) ? ($session['temp_path'] ?? null) : null;

        if (! is_string($tempPath) || ! Storage::exists($tempPath)) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file XML.');
        }

        try {
            return (new DangKyKhoaHocXmlParser())->parse(
                Storage::path($tempPath),
                (string) ($session['file_name'] ?? basename($tempPath))
            );
        } catch (Throwable $e) {
            $this->clearTempFile($request);

            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten')
                ->with('error', 'Không đọc được file XML: '.$e->getMessage());
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearTempFile($request);

        return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten');
    }

    private function clearTempFile(Request $request): void
    {
        $session = $request->session()->get(self::SESSION_KEY);
        if (is_array($session) && ! empty($session['temp_path'])) {
            Storage::delete((string) $session['temp_path']);
        }

        $request->session()->forget(self::SESSION_KEY);
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
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File quá lớn so với giới hạn upload của server (file DKKH thường 3–8 MB). Liên hệ quản trị để tăng upload_max_filesize.',
            UPLOAD_ERR_PARTIAL => 'File chỉ upload được một phần. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Vui lòng chọn file XML.',
            default => 'Upload thất bại (mã lỗi '.$error.'). Vui lòng thử lại.',
        };
    }
}
