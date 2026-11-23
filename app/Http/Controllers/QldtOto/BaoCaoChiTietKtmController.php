<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\QldtOto\KtmBaoCaoChiTiet;
use App\Rules\ExcelUpload;
use App\Support\MenuQldt;
use App\Support\QldtOto\KtmChiTiet\DocDanhSachSatHach;
use App\Support\QldtOto\KtmChiTiet\DocDiemDanhSach;
use App\Support\QldtOto\KtmChiTiet\DocDiemKetQua;
use App\Support\QldtOto\KtmChiTiet\GhepBaoCaoChiTiet;
use App\Support\QldtOto\KtmChiTiet\KtmChiTietLocDanhSach;
use App\Support\QldtOto\KtmChiTiet\LichSuUploadKtmChiTiet;
use App\Support\QldtOto\KtmChiTiet\XuatBaoCaoChiTietExcel;
use App\Support\QldtOto\KtmChiTiet\XuatDanhSachKtmChiTiet;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BaoCaoChiTietKtmController extends Controller
{
    public const SLUG = 'bao-cao-chi-tiet';

    private const SESSION_PREVIEW = 'qldtoto.bao_cao_chi_tiet.preview';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $danhSachKy = KtmBaoCaoChiTiet::query()
            ->with('baoCaoGoc')
            ->orderByDesc('NgayKyThi')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('QldtOto.bao-cao-chi-tiet.index', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'danhSachKy' => $danhSachKy,
        ]);
    }

    public function create(): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        return view('QldtOto.bao-cao-chi-tiet.create', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'ngayMacDinh' => now()->format('Y-m-d'),
            'danhSachKyGoc' => $this->danhSachKyGoc(),
        ]);
    }

    public function previewStore(Request $request): RedirectResponse
    {
        $ketQua = $this->chuanBiGhepTuRequest($request);
        if ($ketQua instanceof RedirectResponse) {
            return $ketQua;
        }

        $token = Str::random(40);
        $request->session()->put(self::SESSION_PREVIEW, array_merge($ketQua, ['token' => $token]));

        return redirect()->route('qldtoto.bao-cao-chi-tiet.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_PREVIEW);
        if (! is_array($session) || empty($session['thi_sinh']) || empty($session['token'])) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')
                ->with('error', 'Chưa có dữ liệu ghép. Chọn file và bấm «Ghép & xem trước».');
        }

        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $kyGocLabel = '';
        if (! empty($session['la_thi_lai']) && ! empty($session['bao_cao_goc_id'])) {
            $goc = KtmBaoCaoChiTiet::query()->find((int) $session['bao_cao_goc_id']);
            if ($goc !== null) {
                $kyGocLabel = $goc->NgayKyThi->format('d/m/Y').' — '.$goc->TenKhoaThi;
            }
        }

        $ngay = Carbon::parse($session['ngay_ky_thi']);

        return view('QldtOto.bao-cao-chi-tiet.preview', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'token' => (string) $session['token'],
            'ngayKyThi' => $ngay->format('d/m/Y'),
            'tenKhoaThi' => (string) ($session['ten_khoa_thi'] ?? ''),
            'tenKhoaTuFile' => (string) ($session['ten_khoa_tu_file'] ?? ''),
            'diaDiem' => (string) ($session['dia_diem'] ?? ''),
            'laThiLai' => (bool) ($session['la_thi_lai'] ?? false),
            'kyGocLabel' => $kyGocLabel,
            'soThiSinh' => (int) ($session['so_thi_sinh'] ?? 0),
            'canhBao' => is_array($session['canh_bao'] ?? null) ? $session['canh_bao'] : [],
            'xemTruocGhep' => is_array($session['xem_truoc'] ?? null) ? $session['xem_truoc'] : [],
            'uuTienTuFile' => (bool) ($session['uu_tien_tu_file'] ?? false),
        ]);
    }

    public function confirmStore(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $session = $request->session()->get(self::SESSION_PREVIEW);
        if (! is_array($session)
            || empty($session['thi_sinh'])
            || ! hash_equals((string) ($session['token'] ?? ''), (string) $request->input('token'))) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')
                ->with('error', 'Phiên xem trước hết hạn hoặc không hợp lệ. Ghép lại từ đầu.');
        }

        $banGhiCu = null;
        $recordId = $session['record_id'] ?? null;
        if ($recordId !== null) {
            $banGhiCu = KtmBaoCaoChiTiet::query()->find((int) $recordId);
        }
        if ($banGhiCu === null) {
            $banGhiCu = KtmBaoCaoChiTiet::query()
                ->where('NgayKyThi', $session['ngay_ky_thi'])
                ->where('TenKhoaThi', trim((string) $session['ten_khoa_thi']))
                ->first();
        }

        $lichSu = $banGhiCu?->lichSuUploadList() ?? [];
        $lichSuMoi = is_array($session['lich_su_moi'] ?? null) ? $session['lich_su_moi'] : [];
        if ($lichSuMoi !== []) {
            $lichSu = array_merge($lichSu, $lichSuMoi);
        }

        $record = KtmBaoCaoChiTiet::query()->updateOrCreate(
            [
                'NgayKyThi' => $session['ngay_ky_thi'],
                'TenKhoaThi' => trim((string) $session['ten_khoa_thi']),
            ],
            [
                'TenKhoaTuFile' => (string) ($session['ten_khoa_tu_file'] ?? ''),
                'DiaDiemSatHach' => (string) ($session['dia_diem'] ?? ''),
                'LaThiLai' => (bool) ($session['la_thi_lai'] ?? false),
                'BaoCaoGocId' => $session['bao_cao_goc_id'] ?? null,
                'DuLieu' => json_encode($session['thi_sinh'], JSON_UNESCAPED_UNICODE),
                'CanhBao' => json_encode($session['canh_bao'] ?? [], JSON_UNESCAPED_UNICODE),
                'LichSuUpload' => json_encode($lichSu, JSON_UNESCAPED_UNICODE),
            ]
        );

        $request->session()->forget(self::SESSION_PREVIEW);

        return redirect()
            ->route('qldtoto.bao-cao-chi-tiet.show', ['id' => $record->id])
            ->with('success', 'Đã lưu báo cáo chi tiết.');
    }

    public function previewCancel(Request $request): RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_PREVIEW);
        $recordId = is_array($session) ? ($session['record_id'] ?? null) : null;
        $request->session()->forget(self::SESSION_PREVIEW);

        if ($recordId !== null) {
            return redirect()
                ->route('qldtoto.bao-cao-chi-tiet.show', ['id' => (int) $recordId])
                ->with('error', 'Đã huỷ xem trước — chưa lưu. Chọn lại file nếu cần ghép.');
        }

        return redirect()
            ->route('qldtoto.bao-cao-chi-tiet.create')
            ->with('error', 'Đã huỷ xem trước — chưa lưu. Chọn lại file nếu cần ghép.');
    }

    public function show(int $id): View|RedirectResponse
    {
        $record = KtmBaoCaoChiTiet::query()->find($id);
        if ($record === null) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')->with('error', 'Không tìm thấy kỳ thi.');
        }

        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $record->loadMissing('baoCaoGoc');
        $cccdDatDotGoc = $this->cccdDaDatKyGoc($record);

        return view('QldtOto.bao-cao-chi-tiet.show', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'record' => $record,
            'thiSinh' => $record->thiSinh(),
            'canhBao' => $record->canhBaoList(),
            'danhSachKyGoc' => $this->danhSachKyGoc(),
            'cccdDatDotGoc' => $cccdDatDotGoc,
            'lichSuUpload' => array_reverse($record->lichSuUploadList()),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $record = KtmBaoCaoChiTiet::query()->find($id);
        if ($record === null) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')->with('error', 'Không tìm thấy kỳ thi.');
        }

        $ten = $record->TenKhoaThi;
        $ngay = $record->NgayKyThi->format('d/m/Y');
        $record->delete();

        return redirect()
            ->route('qldtoto.bao-cao-chi-tiet.index')
            ->with('success', 'Đã xoá báo cáo «'.$ten.'» (ngày '.$ngay.'). Toàn bộ danh sách và kết quả đã ghép đã bị xoá.');
    }

    public function export(int $id): BinaryFileResponse|RedirectResponse
    {
        $record = KtmBaoCaoChiTiet::query()->find($id);
        if ($record === null) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')->with('error', 'Không tìm thấy kỳ thi.');
        }

        $thiSinh = $record->thiSinh();
        if ($thiSinh === []) {
            return back()->with('error', 'Không có dữ liệu để xuất.');
        }

        $tmp = storage_path('app/tmp/bc-chi-tiet-ktm-'.uniqid('', true).'.xls');
        @mkdir(dirname($tmp), 0755, true);

        try {
            XuatBaoCaoChiTietExcel::xuat(
                $thiSinh,
                Carbon::parse($record->NgayKyThi),
                (string) ($record->DiaDiemSatHach ?? ''),
                $record->NgayKyThi->format('d/m/Y'),
                $tmp,
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Excel thất bại: '.$e->getMessage());
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $record->TenKhoaThi) ?: 'khoa';

        return response()
            ->download($tmp, 'bao-cao-chi-tiet-'.$safe.'-'.$record->NgayKyThi->format('Ymd').'.xls')
            ->deleteFileAfterSend(true);
    }

    public function exportDanhSach(string $loai, int $id): BinaryFileResponse|RedirectResponse
    {
        $loaiHopLe = [
            XuatDanhSachKtmChiTiet::LOAI_VANG,
            XuatDanhSachKtmChiTiet::LOAI_DAT,
            XuatDanhSachKtmChiTiet::LOAI_KO_DAT,
        ];
        if (! in_array($loai, $loaiHopLe, true)) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')->with('error', 'Loại danh sách không hợp lệ.');
        }

        $record = KtmBaoCaoChiTiet::query()->find($id);
        if ($record === null) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')->with('error', 'Không tìm thấy kỳ thi.');
        }

        $thiSinh = $record->thiSinh();
        if ($thiSinh === []) {
            return back()->with('error', 'Không có dữ liệu để xuất.');
        }

        $loc = match ($loai) {
            XuatDanhSachKtmChiTiet::LOAI_VANG => KtmChiTietLocDanhSach::locVang($thiSinh),
            XuatDanhSachKtmChiTiet::LOAI_DAT => KtmChiTietLocDanhSach::locDat($thiSinh),
            XuatDanhSachKtmChiTiet::LOAI_KO_DAT => KtmChiTietLocDanhSach::locKhongDat($thiSinh),
            default => [],
        };

        $tmp = storage_path('app/tmp/ds-'.$loai.'-'.uniqid('', true).'.xls');
        @mkdir(dirname($tmp), 0755, true);

        $tenKhoa = trim((string) ($record->TenKhoaTuFile ?? ''));
        if ($tenKhoa === '') {
            $tenKhoa = $record->TenKhoaThi;
        }
        $diaDiem = (string) ($record->DiaDiemSatHach ?? '');
        if ($diaDiem === '') {
            $diaDiem = 'TRUNG TÂM SÁT HẠCH LÁI XE MẠNH LINH';
        }

        try {
            XuatDanhSachKtmChiTiet::xuat(
                $loai,
                $loc,
                $tenKhoa,
                $diaDiem,
                Carbon::parse($record->NgayKyThi),
                $tmp,
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất danh sách thất bại: '.$e->getMessage());
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $record->TenKhoaThi) ?: 'khoa';
        $tenFile = match ($loai) {
            XuatDanhSachKtmChiTiet::LOAI_VANG => 'ds-vang',
            XuatDanhSachKtmChiTiet::LOAI_DAT => 'ds-dat',
            XuatDanhSachKtmChiTiet::LOAI_KO_DAT => 'ds-khong-dat',
            default => 'ds',
        };

        return response()
            ->download($tmp, $tenFile.'-'.$safe.'-'.$record->NgayKyThi->format('Ymd').'.xls')
            ->deleteFileAfterSend(true);
    }

    /**
     * Ghép file → dữ liệu lưu session xem trước (chưa ghi DB).
     *
     * @return array<string, mixed>|RedirectResponse
     */
    private function chuanBiGhepTuRequest(Request $request): array|RedirectResponse
    {
        $banGhiCu = null;
        if ($request->filled('record_id')) {
            $banGhiCu = KtmBaoCaoChiTiet::query()->find((int) $request->input('record_id'));
        }
        if ($banGhiCu === null && $request->filled('ngay_ky_thi') && $request->filled('ten_khoa_thi')) {
            $banGhiCu = KtmBaoCaoChiTiet::query()
                ->where('NgayKyThi', $request->input('ngay_ky_thi'))
                ->where('TenKhoaThi', trim((string) $request->input('ten_khoa_thi')))
                ->first();
        }
        $coDanhSachDaLuu = $banGhiCu !== null && $banGhiCu->thiSinh() !== [];

        $data = $request->validate([
            'record_id' => ['nullable', 'integer'],
            'ngay_ky_thi' => ['required', 'date'],
            'ten_khoa_thi' => ['required', 'string', 'max:255'],
            'dia_diem_sat_hach' => ['nullable', 'string', 'max:255'],
            'la_thi_lai' => ['nullable', 'boolean'],
            'bao_cao_goc_id' => ['nullable', 'integer'],
            'file_danh_sach' => array_merge(
                $coDanhSachDaLuu ? ['nullable'] : ['required'],
                ['file', new ExcelUpload(), 'max:51200']
            ),
            'file_lt' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
            'file_mp' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
            'file_cc' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
            'file_h_dat' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
            'file_h_truot' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
            'file_dt_dat' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
            'file_dt_truot' => ['nullable', 'file', new ExcelUpload(), 'max:51200'],
        ], [
            'file_danh_sach.required' => 'Vui lòng chọn file danh sách thí sinh.',
        ]);

        $laThiLai = $request->boolean('la_thi_lai') || (bool) ($banGhiCu?->LaThiLai ?? false);
        $baoCaoGocId = $request->filled('bao_cao_goc_id')
            ? (int) $request->input('bao_cao_goc_id')
            : ($banGhiCu?->BaoCaoGocId);

        if ($laThiLai) {
            if ($baoCaoGocId === null) {
                return back()->withInput()->with('error', 'Báo cáo thi lại: vui lòng chọn kỳ thi gốc.');
            }
            if ($banGhiCu !== null && $baoCaoGocId === (int) $banGhiCu->id) {
                return back()->withInput()->with('error', 'Kỳ thi gốc không được trùng bản ghi hiện tại.');
            }
            $goc = KtmBaoCaoChiTiet::query()->find($baoCaoGocId);
            if ($goc === null || $goc->thiSinh() === []) {
                return back()->withInput()->with('error', 'Kỳ thi gốc không tồn tại hoặc chưa có dữ liệu.');
            }
        } else {
            $baoCaoGocId = null;
        }

        if ($banGhiCu === null) {
            $banGhiCu = KtmBaoCaoChiTiet::query()
                ->where('NgayKyThi', $data['ngay_ky_thi'])
                ->where('TenKhoaThi', trim($data['ten_khoa_thi']))
                ->first();
        }

        try {
            if ($request->hasFile('file_danh_sach')) {
                $dsPath = $this->tempPath($request->file('file_danh_sach'));
                $danhSach = DocDanhSachSatHach::doc($dsPath);
                @unlink($dsPath);
            } elseif ($coDanhSachDaLuu) {
                $danhSach = $banGhiCu->danhSachGoc();
            } else {
                return back()->withInput()->with('error', 'Vui lòng chọn file danh sách thí sinh.');
            }

            $lt = $this->docDiemSbd(
                $request->file('file_lt'),
                $laThiLai ? DocDiemKetQua::CHE_DO_THI_LAI_LT : null,
            );
            $mp = $this->docDiemSbd(
                $request->file('file_mp'),
                $laThiLai ? DocDiemKetQua::CHE_DO_THI_LAI_MP_DAT : null,
            );
            $cc = $this->docDiemCccd(
                $request->file('file_cc'),
                $laThiLai ? DocDiemKetQua::CHE_DO_THI_LAI_CC : null,
            );
            $hDat = $this->docDanhSach($request->file('file_h_dat'), true);
            $hTruot = $this->docDanhSach($request->file('file_h_truot'), false);
            $dtDat = $this->docDanhSach($request->file('file_dt_dat'), true);
            $dtTruot = $this->docDanhSach($request->file('file_dt_truot'), false);

            $coFile = [
                'lt' => $request->hasFile('file_lt'),
                'mp' => $request->hasFile('file_mp'),
                'cc' => $request->hasFile('file_cc'),
                'h' => $request->hasFile('file_h_dat') || $request->hasFile('file_h_truot'),
                'h_dat' => $request->hasFile('file_h_dat'),
                'h_truot' => $request->hasFile('file_h_truot'),
                'duong' => $request->hasFile('file_dt_dat') || $request->hasFile('file_dt_truot'),
                'duong_dat' => $request->hasFile('file_dt_dat'),
                'duong_truot' => $request->hasFile('file_dt_truot'),
            ];
            $ketQuaCu = $this->ketQuaCuChoGhep($banGhiCu, $laThiLai, $baoCaoGocId);
            $ghep = GhepBaoCaoChiTiet::ghep($danhSach, $lt, $mp, $cc, $hDat, $hTruot, $dtDat, $dtTruot, $coFile, $ketQuaCu);

            $daCoMon = KtmBaoCaoChiTiet::coMonDaNhap($ketQuaCu);
            $thieuFile = [];
            foreach ([
                ['file_lt', 'lt', 'Lý thuyết'],
                ['file_mp', 'mp', 'Mô phỏng'],
                ['file_cc', 'cc', 'CC (chữ chi)'],
                ['file_h_dat', 'h', 'Hình (đạt / trượt)', ['file_h_truot']],
                ['file_dt_dat', 'duong', 'Đường trường (đạt / trượt)', ['file_dt_truot']],
            ] as $cfg) {
                $input = $cfg[0];
                $field = $cfg[1];
                $ten = $cfg[2];
                $alt = $cfg[3] ?? [];
                $daTai = $request->hasFile($input);
                foreach ($alt as $a) {
                    $daTai = $daTai || $request->hasFile($a);
                }
                if ($daTai || ($daCoMon[$field] ?? false)) {
                    continue;
                }
                $thieuFile[] = 'Chưa tải file: '.$ten;
            }
            if ($thieuFile !== []) {
                $ghep['canh_bao'] = array_merge($ghep['canh_bao'], $thieuFile);
            }

            $ghep['thi_sinh'] = self::locVaDanhSoThiSinh($ghep['thi_sinh']);

            $lichSuMoi = LichSuUploadKtmChiTiet::tuLanUpload(
                $request,
                $ghep['thi_sinh'],
                $lt,
                $mp,
                $cc,
                $hDat,
                $hTruot,
                $dtDat,
                $dtTruot,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không xử lý được file: '.$e->getMessage());
        }

        $diaDiem = trim((string) ($data['dia_diem_sat_hach'] ?? ''));
        if ($diaDiem === '') {
            $diaDiem = $danhSach['dia_diem'] !== '' ? $danhSach['dia_diem'] : 'TRUNG TÂM SÁT HẠCH LÁI XE MẠNH LINH';
        }

        $tenKhoaTuFile = $request->hasFile('file_danh_sach')
            ? $danhSach['ten_khoa']
            : (string) ($banGhiCu?->TenKhoaTuFile ?? $danhSach['ten_khoa']);

        $bangDaTai = [];
        if ($request->hasFile('file_lt')) {
            $bangDaTai[] = $lt;
        }
        if ($request->hasFile('file_mp')) {
            $bangDaTai[] = $mp;
        }
        if ($request->hasFile('file_cc')) {
            $bangDaTai[] = $cc;
        }
        if ($request->hasFile('file_h_dat')) {
            $bangDaTai[] = $hDat;
        }
        if ($request->hasFile('file_h_truot')) {
            $bangDaTai[] = $hTruot;
        }
        if ($request->hasFile('file_dt_dat')) {
            $bangDaTai[] = $dtDat;
        }
        if ($request->hasFile('file_dt_truot')) {
            $bangDaTai[] = $dtTruot;
        }

        $fileVuaTai = [];
        foreach ($lichSuMoi as $dong) {
            $fileVuaTai[] = ($dong['loai'] ?? '').' — '.($dong['ten_file'] ?? '');
        }

        $xemTruoc = [
            'dong' => GhepBaoCaoChiTiet::xemTruoc5Dong($ghep['thi_sinh'], $bangDaTai),
            'file_vua_tai' => $fileVuaTai,
            'uu_tien_tu_file' => $bangDaTai !== [],
        ];

        return [
            'record_id' => $banGhiCu?->id,
            'ngay_ky_thi' => $data['ngay_ky_thi'],
            'ten_khoa_thi' => trim($data['ten_khoa_thi']),
            'dia_diem' => $diaDiem,
            'la_thi_lai' => $laThiLai,
            'bao_cao_goc_id' => $baoCaoGocId,
            'ten_khoa_tu_file' => $tenKhoaTuFile,
            'thi_sinh' => $ghep['thi_sinh'],
            'canh_bao' => $ghep['canh_bao'],
            'lich_su_moi' => $lichSuMoi,
            'xem_truoc' => $xemTruoc,
            'uu_tien_tu_file' => $bangDaTai !== [],
            'so_thi_sinh' => count($ghep['thi_sinh']),
        ];
    }

    /**
     * @param  DocDiemKetQua::CHE_DO_THI_LAI_LT|DocDiemKetQua::CHE_DO_THI_LAI_MP_DAT|null  $cheDoThiLai
     * @return array<string, string>
     */
    private function docDiemSbd(?UploadedFile $file, ?string $cheDoThiLai = null): array
    {
        if ($file === null) {
            return [];
        }
        $path = $this->tempPath($file);
        try {
            return DocDiemKetQua::docTheoSbd($path, $cheDoThiLai);
        } finally {
            @unlink($path);
        }
    }

    /**
     * @param  DocDiemKetQua::CHE_DO_THI_LAI_CC|null  $cheDoThiLai
     * @return array<string, string>
     */
    private function docDiemCccd(?UploadedFile $file, ?string $cheDoThiLai = null): array
    {
        if ($file === null) {
            return [];
        }
        $path = $this->tempPath($file);
        try {
            return DocDiemKetQua::docChuChi($path, $cheDoThiLai);
        } finally {
            @unlink($path);
        }
    }

    /** @return array<string, string> */
    private function docDanhSach(?UploadedFile $file, bool $dat): array
    {
        if ($file === null) {
            return [];
        }
        $path = $this->tempPath($file);
        try {
            return $dat ? DocDiemDanhSach::docDat($path) : DocDiemDanhSach::docTruot($path);
        } finally {
            @unlink($path);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $thiSinh
     * @return list<array<string, mixed>>
     */
    private static function locVaDanhSoThiSinh(array $thiSinh): array
    {
        $out = [];
        foreach ($thiSinh as $ts) {
            $sbd = (string) ($ts['sbd'] ?? '');
            $hoTen = (string) ($ts['hoTen'] ?? '');
            if (! DocDanhSachSatHach::dongThiSinhHopLe($sbd, $hoTen)) {
                continue;
            }
            $ts['stt'] = count($out) + 1;
            $out[] = $ts;
        }

        return $out;
    }

    private function tempPath(UploadedFile $file): string
    {
        $path = storage_path('app/tmp/ktm-chi-tiet-'.uniqid('', true).'.'.$file->getClientOriginalExtension());
        @mkdir(dirname($path), 0755, true);
        $file->move(dirname($path), basename($path));

        return $path;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, KtmBaoCaoChiTiet> */
    private function danhSachKyGoc(): \Illuminate\Database\Eloquent\Collection
    {
        return KtmBaoCaoChiTiet::query()
            ->orderByDesc('NgayKyThi')
            ->orderByDesc('id')
            ->limit(150)
            ->get();
    }

    /**
     * Kết quả cũ để ghép: ưu tiên bản ghi hiện tại; lần đầu thi lại thì lấy từ kỳ gốc.
     *
     * @return array<string, array<string, mixed>>
     */
    private function ketQuaCuChoGhep(?KtmBaoCaoChiTiet $banGhiCu, bool $laThiLai, ?int $baoCaoGocId): array
    {
        if ($banGhiCu !== null && $banGhiCu->thiSinh() !== []) {
            return $banGhiCu->ketQuaTheoCccd();
        }
        if (! $laThiLai || $baoCaoGocId === null) {
            return [];
        }
        $goc = KtmBaoCaoChiTiet::query()->find($baoCaoGocId);

        return $goc !== null ? $goc->ketQuaTheoCccd() : [];
    }

    /**
     * CCCD thí sinh đã tổng hợp Đ ở kỳ gốc (đợt 1) — để tô nền trên báo cáo thi lại.
     *
     * @return array<string, true>
     */
    private function cccdDaDatKyGoc(KtmBaoCaoChiTiet $record): array
    {
        if (! $record->LaThiLai || $record->BaoCaoGocId === null) {
            return [];
        }
        $goc = $record->relationLoaded('baoCaoGoc')
            ? $record->baoCaoGoc
            : KtmBaoCaoChiTiet::query()->find($record->BaoCaoGocId);
        if ($goc === null) {
            return [];
        }
        $out = [];
        foreach ($goc->thiSinh() as $ts) {
            if (($ts['tongHop'] ?? '') !== 'Đ') {
                continue;
            }
            $cccd = preg_replace('/\D/', '', (string) ($ts['soDinhDanh'] ?? '')) ?? '';
            if ($cccd !== '') {
                $out[$cccd] = true;
            }
        }

        return $out;
    }
}