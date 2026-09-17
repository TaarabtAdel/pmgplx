<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\SatHachBienBan;
use App\Support\DaoTao\Jp2PhotoConverter;
use App\Support\SatHach\BienBanDocxGenerator;
use App\Support\SatHach\BienBanDocxMerger;
use App\Support\SatHach\BienBanTongHopSession;
use App\Support\SatHach\SatHachBienBanImporter;
use App\Support\SatHach\XmlSatHachParser;
use Illuminate\Http\JsonResponse;
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
            ->orderBy('MaKySH')
            ->pluck('MaKySH');

        $query = SatHachBienBan::query()
            ->select([
                'Id', 'MaKySH', 'NgaySH', 'SoTT', 'MaDK', 'HoVaTen', 'NgaySinh',
                'SoCMT', 'SoBaoDanh', 'HangGPLX', 'KetQuaSH', 'FileNguon', 'NgayNhap',
            ])
            ->selectRaw("CASE WHEN AnhChanDung IS NULL OR AnhChanDung = '' THEN 0 ELSE 1 END as CoAnh")
            ->orderBy('SoBaoDanh');
            // ->orderBy('SoTT')
            // ->orderBy('Id');

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

    public function exportTongStart(Request $request): JsonResponse
    {
        $maKySh = trim((string) $request->input('ma_ky_sh', ''));
        if ($maKySh === '') {
            return response()->json(['message' => 'Chọn kỳ sát hạch trước khi xuất tổng.'], 422);
        }

        $rows = SatHachBienBan::query()
            ->where('MaKySH', $maKySh)
            ->orderBy('SoBaoDanh')
            ->orderBy('SoTT')
            ->orderBy('Id')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Kỳ này không có thí sinh.'], 422);
        }

        try {
            @ini_set('memory_limit', '512M');
            $job = BienBanTongHopSession::start($maKySh, $rows);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'job_id' => $job['id'],
            'total' => count($job['items']),
            'items' => $job['items'],
        ]);
    }

    public function exportTongAdd(Request $request): JsonResponse
    {
        $jobId = trim((string) $request->input('job_id', ''));
        $id = (int) $request->input('id', 0);
        if ($jobId === '' || $id < 1) {
            return response()->json(['message' => 'Thiếu dữ liệu xuất.'], 422);
        }

        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(120);

            $job = BienBanTongHopSession::load($jobId);
            $doneIds = array_map('intval', $job['done_ids'] ?? []);
            $items = $job['items'] ?? [];
            $index = null;
            $meta = null;
            foreach ($items as $i => $item) {
                if ((int) ($item['id'] ?? 0) === $id) {
                    $index = $i;
                    $meta = $item;
                    break;
                }
            }
            if ($index === null || ! is_array($meta)) {
                return response()->json(['message' => 'Thí sinh không thuộc phiên xuất này.'], 422);
            }

            $total = count($items);
            $label = trim(($meta['sbd'] ?? '').' — '.($meta['ten'] ?? ''));

            if (! in_array($id, $doneIds, true)) {
                $row = SatHachBienBan::query()->find($id);
                if ($row === null) {
                    return response()->json(['message' => 'Không tìm thấy thí sinh #'.$id], 404);
                }

                $dir = BienBanTongHopSession::dir($jobId);
                $seq = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                $safeSbd = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($row->SoBaoDanh ?: $row->MaDK)) ?: 'hv';
                $docx = $dir.DIRECTORY_SEPARATOR.$seq.'-'.$id.'-'.$safeSbd.'.docx';

                (new BienBanDocxGenerator())->generateOne($row->toDocxRow(), $docx);

                $files = $job['files'] ?? [];
                $files[] = $docx;
                $job['files'] = $files;
                $job['done_ids'] = array_merge($doneIds, [$id]);
                $job['done'] = count($job['done_ids']);

                (new BienBanDocxMerger())->merge($files, (string) $job['combined_docx']);
                BienBanTongHopSession::save($job);
            }

            $done = (int) ($job['done'] ?? count($doneIds));

            return response()->json([
                'done' => $done,
                'total' => $total,
                'name' => $label,
                'download_url' => $done >= $total
                    ? route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export-tong.download', $jobId)
                    : null,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function exportTongDownload(string $job): BinaryFileResponse|RedirectResponse
    {
        try {
            $session = BienBanTongHopSession::load($job);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', $e->getMessage());
        }

        $docx = (string) ($session['combined_docx'] ?? '');
        if (! is_file($docx)) {
            BienBanTongHopSession::destroy($job);

            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban')
                ->with('error', 'Chưa có file Word tổng.');
        }

        $safeKy = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($session['ma_ky_sh'] ?? 'ky')) ?: 'ky';
        $downloadName = 'bien-ban-tong-'.$safeKy.'.docx';

        register_shutdown_function(static function () use ($job): void {
            BienBanTongHopSession::destroy($job);
        });

        return response()->download($docx, $downloadName);
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
