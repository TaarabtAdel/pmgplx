<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Rules\ExcelUpload;
use App\Support\MenuQldt;
use App\Support\QldtOto\KtmBaoCao\KtmBaoCaoFormMacDinh;
use App\Support\QldtOto\KtmBaoCao\KtmBaoCaoThamSo;
use App\Support\QldtOto\KtmBaoCao\KtmDocFileNhap;
use App\Support\QldtOto\KtmBaoCao\KtmKyThiDuLieu;
use App\Support\QldtOto\KtmBaoCao\KtmXuatExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class XuatBaoCaoKtmController extends Controller
{
    private const SLUG = '2-dua-vao-file-cua-nhan-lam-danh-sach-vang-dat-truot-bien-ban';

    private const SESSION_KEY = 'qldtoto.ktm_bao_cao.preview';

    private const TEMP_DIR = 'qldt-oto-ktm-bao-cao';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $cfg = config('qldt_oto_ktm_bao_cao', []);

        return view('QldtOto.xuat-bao-cao-ktm.index', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'cfg' => $cfg,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', new ExcelUpload(), 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel nhập.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $this->clearSession($request);

        $storedPath = $file->storeAs(
            self::TEMP_DIR,
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            'local'
        );
        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            $duLieu = KtmDocFileNhap::doc($fullPath);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);

            return back()->with('error', 'Không đọc được file nhập: '.$e->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, [
            'stored_path' => $storedPath,
            'file_name' => $file->getClientOriginalName(),
            'payload' => self::serializeDuLieu($duLieu),
            'form' => KtmBaoCaoFormMacDinh::tao($duLieu),
        ]);

        return redirect()->route('qldtoto.xuat-bao-cao-ktm.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);
        if (! is_array($session) || empty($session['payload'])) {
            return redirect()->route('qldtoto.xuat-bao-cao-ktm.index')
                ->with('error', 'Chưa có dữ liệu. Vui lòng chọn file nhập.');
        }

        $duLieu = self::hydrateDuLieu($session['payload']);
        $form = self::formTuSession($session, $duLieu);

        return view('QldtOto.xuat-bao-cao-ktm.preview', [
            'duLieu' => $duLieu,
            'fileName' => (string) ($session['file_name'] ?? ''),
            'form' => $form,
        ]);
    }

    public function updateThamSo(Request $request): RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);
        if (! is_array($session) || empty($session['payload'])) {
            return redirect()->route('qldtoto.xuat-bao-cao-ktm.index')
                ->with('error', 'Chưa có dữ liệu.');
        }

        $duLieu = self::hydrateDuLieu($session['payload']);
        $form = self::luuForm($request, $session, $duLieu);
        $request->session()->put(self::SESSION_KEY, array_merge($session, ['form' => $form]));

        return redirect()->route('qldtoto.xuat-bao-cao-ktm.preview')
            ->with('success', 'Đã lưu tham số báo cáo.');
    }

    public function export(Request $request, string $loai): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);
        if (! is_array($session) || empty($session['payload'])) {
            return redirect()->route('qldtoto.xuat-bao-cao-ktm.index')
                ->with('error', 'Chưa có dữ liệu để xuất.');
        }

        $duLieu = self::hydrateDuLieu($session['payload']);
        $form = self::luuForm($request, $session, $duLieu);
        $request->session()->put(self::SESSION_KEY, array_merge($session, ['form' => $form]));
        $ts = KtmBaoCaoThamSo::tuForm($form, $duLieu);
        $slugTen = Str::slug(mb_substr($duLieu->tenKhoa, 0, 40)) ?: 'ktm';
        $ngay = preg_replace('/[^\d]/', '', $duLieu->ngayThi) ?: date('Ymd');
        $dir = Storage::disk('local')->path(self::TEMP_DIR.'/out-'.Str::uuid());
        @mkdir($dir, 0755, true);

        $files = [
            'bbth' => ['BBTH_'.$slugTen.'_'.$ngay.'.xlsx', fn () => KtmXuatExcel::xuatBbth($duLieu, $ts, $dir.'/BBTH.xlsx')],
            'chi-tiet' => ['BC_CHI_TIET_'.$slugTen.'_'.$ngay.'.xlsx', fn () => KtmXuatExcel::xuatBcChiTiet($duLieu, $ts, $dir.'/BC_CHI_TIET.xlsx')],
            'chung' => ['BC_CHUNG_'.$slugTen.'_'.$ngay.'.xlsx', fn () => KtmXuatExcel::xuatBcChung($duLieu, $ts, $dir.'/BC_CHUNG.xlsx')],
        ];

        if ($loai === 'zip') {
            foreach ($files as [, $spec]) {
                $spec[1]();
            }
            $zipPath = $dir.'.zip';
            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($files as [, [$name]]) {
                $zip->addFile($dir.'/'.$this->fileBase($name), $name);
            }
            $zip->close();

            return response()->download($zipPath, 'Bao_cao_KTM_'.$slugTen.'_'.$ngay.'.zip')->deleteFileAfterSend(true);
        }

        if (! isset($files[$loai])) {
            abort(404);
        }

        [$downloadName, $gen] = $files[$loai];
        try {
            $gen();
        } catch (\Throwable $e) {
            return back()->with('error', 'Không xuất được file: '.$e->getMessage());
        }

        $path = $dir.'/'.$this->fileBase($downloadName);

        return response()->download($path, $downloadName)->deleteFileAfterSend(true);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearSession($request);

        return redirect()->route('qldtoto.xuat-bao-cao-ktm.index')->with('success', 'Đã hủy phiên xuất báo cáo.');
    }

    private function fileBase(string $downloadName): string
    {
        return match (true) {
            str_starts_with($downloadName, 'BBTH') => 'BBTH.xlsx',
            str_starts_with($downloadName, 'BC_CHI') => 'BC_CHI_TIET.xlsx',
            default => 'BC_CHUNG.xlsx',
        };
    }

    private function clearSession(Request $request): void
    {
        $session = $request->session()->get(self::SESSION_KEY);
        if (is_array($session) && ! empty($session['stored_path'])) {
            Storage::disk('local')->delete((string) $session['stored_path']);
        }
        $request->session()->forget(self::SESSION_KEY);
    }

    /** @return array<string, mixed> */
    private static function serializeDuLieu(KtmKyThiDuLieu $dl): array
    {
        return $dl->toArray();
    }

    private static function hydrateDuLieu(array $payload): KtmKyThiDuLieu
    {
        return KtmKyThiDuLieu::fromArray($payload);
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{bbth: array<string, mixed>, chi_tiet: array<string, mixed>, bc_chung: array<string, mixed>}
     */
    private static function formTuSession(array $session, KtmKyThiDuLieu $duLieu): array
    {
        $saved = $session['form'] ?? [];
        if (! isset($saved['bbth'])) {
            return KtmBaoCaoFormMacDinh::tao($duLieu);
        }

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{bbth: array<string, mixed>, chi_tiet: array<string, mixed>, bc_chung: array<string, mixed>}
     */
    private static function luuForm(Request $request, array $session, KtmKyThiDuLieu $duLieu): array
    {
        $coSan = self::formTuSession($session, $duLieu);
        $posted = (array) $request->input('form', []);

        return KtmBaoCaoFormMacDinh::hopNhat($coSan, $posted);
    }
}