<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\SatHachBienBan;
use App\Support\DaoTao\Jp2PhotoConverter;
use App\Support\SatHach\BienBanDocxGenerator;
use App\Support\SatHach\SatHachBienBanImporter;
use App\Support\SatHach\XmlSatHachParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class NhapXmlBienBanTongHopController extends Controller
{
    public function create(Request $request): View
    {
        $maKySh = trim((string) $request->input('ma_ky_sh', ''));
        $tuKhoa = trim((string) $request->input('tu_khoa', ''));

        $kyOptions = SatHachBienBan::query()
            ->whereNotNull('MaKySH')
            ->where('MaKySH', '!=', '')
            ->distinct()
            ->orderByDesc('MaKySH')
            ->pluck('MaKySH');

        $query = SatHachBienBan::query()
            ->select([
                'Id', 'MaKySH', 'NgaySH', 'SoTT', 'MaDK', 'HoVaTen', 'NgaySinh',
                'SoCMT', 'SoBaoDanh', 'HangGPLX', 'KetQuaSH', 'FileNguon', 'NgayNhap',
            ])
            ->selectRaw("CASE WHEN AnhChanDung IS NULL OR AnhChanDung = '' THEN 0 ELSE 1 END as CoAnh")
            ->orderBy('MaKySH')
            ->orderBy('SoTT')
            ->orderBy('Id');

        if ($maKySh !== '') {
            $query->where('MaKySH', $maKySh);
        }
        if ($tuKhoa !== '') {
            $like = '%'.$tuKhoa.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('HoVaTen', 'like', $like)
                    ->orWhere('MaDK', 'like', $like)
                    ->orWhere('SoBaoDanh', 'like', $like)
                    ->orWhere('SoCMT', 'like', $like);
            });
        }

        $items = $query->paginate(50)->withQueryString();

        return view('DaoTao.cong-cu-nhap.nhap-xml-bien-ban', [
            'items' => $items,
            'kyOptions' => $kyOptions,
            'filters' => [
                'ma_ky_sh' => $maKySh,
                'tu_khoa' => $tuKhoa,
            ],
            'jp2Ready' => Jp2PhotoConverter::isAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uploadError = $this->uploadErrorMessage('file');
        if ($uploadError !== null) {
            return back()->with('error', $uploadError);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xml,txt', 'max:102400'],
        ], [
            'file.required' => 'Vui lòng chọn file XML.',
            'file.mimes' => 'File phải là XML (.xml).',
            'file.max' => 'File quá lớn (tối đa 100 MB).',
        ]);

        $file = $request->file('file');
        $storedPath = null;

        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(0);

            $storedPath = $file->store('temp/bien-ban-xml');
            $parsed = (new XmlSatHachParser())->parse(Storage::path($storedPath));
            $result = (new SatHachBienBanImporter())->import($parsed, $file->getClientOriginalName());
        } catch (Throwable $e) {
            return back()->with('error', 'Không nhập được file XML: '.$e->getMessage());
        } finally {
            if (is_string($storedPath) && $storedPath !== '') {
                Storage::delete($storedPath);
            }
        }

        $msg = 'Đã lưu '.$result['saved'].' thí sinh mới';
        if ($result['updated'] > 0) {
            $msg .= ', cập nhật '.$result['updated'].' thí sinh trùng kỳ/mã ĐK';
        }
        $msg .= ' vào bảng SatHachBienBan.';

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban', array_filter([
                'ma_ky_sh' => $result['ma_ky_sh'],
            ]))
            ->with('success', $msg);
    }

    public function export(int $id): BinaryFileResponse|RedirectResponse
    {
        $row = SatHachBienBan::query()->find($id);
        if ($row === null) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', 'Không tìm thấy thí sinh.');
        }

        $dir = storage_path('app/temp/bien-ban-docx');
        @mkdir($dir, 0755, true);
        $safeSbd = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($row->SoBaoDanh ?: $row->MaDK)) ?: 'hv';
        $path = $dir.DIRECTORY_SEPARATOR.'bien-ban-'.$id.'-'.$safeSbd.'.docx';

        try {
            (new BienBanDocxGenerator())->generateOne($row->toDocxRow(), $path);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', 'Xuất DOCX thất bại: '.$e->getMessage());
        }

        $downloadName = 'bien-ban-'.$safeSbd.'.docx';

        return response()->download($path, $downloadName)->deleteFileAfterSend(true);
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
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File quá lớn so với giới hạn upload của server.',
            UPLOAD_ERR_PARTIAL => 'File chỉ upload được một phần. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Vui lòng chọn file XML.',
            default => 'Upload thất bại (mã lỗi '.$error.'). Vui lòng thử lại.',
        };
    }
}
