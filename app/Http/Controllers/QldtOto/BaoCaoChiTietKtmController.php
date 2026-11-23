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
use App\Support\QldtOto\KtmChiTiet\XuatBaoCaoChiTietExcel;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BaoCaoChiTietKtmController extends Controller
{
    public const SLUG = 'bao-cao-chi-tiet';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $danhSachKy = KtmBaoCaoChiTiet::query()
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
        ]);
    }

    public function store(Request $request): RedirectResponse
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

            $lt = $this->docDiemSbd($request->file('file_lt'));
            $mp = $this->docDiemSbd($request->file('file_mp'));
            $cc = $this->docDiemCccd($request->file('file_cc'));
            $hDat = $this->docDanhSach($request->file('file_h_dat'), true);
            $hTruot = $this->docDanhSach($request->file('file_h_truot'), false);
            $dtDat = $this->docDanhSach($request->file('file_dt_dat'), true);
            $dtTruot = $this->docDanhSach($request->file('file_dt_truot'), false);

            $coFile = [
                'lt' => $request->hasFile('file_lt'),
                'mp' => $request->hasFile('file_mp'),
                'cc' => $request->hasFile('file_cc'),
                'h' => $request->hasFile('file_h_dat') || $request->hasFile('file_h_truot'),
                'duong' => $request->hasFile('file_dt_dat') || $request->hasFile('file_dt_truot'),
            ];
            $ketQuaCu = $banGhiCu !== null ? $banGhiCu->ketQuaTheoCccd() : [];
            $ghep = GhepBaoCaoChiTiet::ghep($danhSach, $lt, $mp, $cc, $hDat, $hTruot, $dtDat, $dtTruot, $coFile, $ketQuaCu);

            $daCoMon = KtmBaoCaoChiTiet::coMonDaNhap($ketQuaCu);
            $thieuFile = [];
            foreach ([
                ['file_lt', 'lt', 'Lý thuyết'],
                ['file_mp', 'mp', 'Mô phỏng'],
                ['file_cc', 'cc', 'Cabin'],
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

        $record = KtmBaoCaoChiTiet::query()->updateOrCreate(
            [
                'NgayKyThi' => $data['ngay_ky_thi'],
                'TenKhoaThi' => trim($data['ten_khoa_thi']),
            ],
            [
                'TenKhoaTuFile' => $tenKhoaTuFile,
                'DiaDiemSatHach' => $diaDiem,
                'DuLieu' => json_encode($ghep['thi_sinh'], JSON_UNESCAPED_UNICODE),
                'CanhBao' => json_encode($ghep['canh_bao'], JSON_UNESCAPED_UNICODE),
            ]
        );

        return redirect()
            ->route('qldtoto.bao-cao-chi-tiet.show', ['id' => $record->id])
            ->with('success', 'Đã lưu báo cáo chi tiết.');
    }

    public function show(int $id): View|RedirectResponse
    {
        $record = KtmBaoCaoChiTiet::query()->find($id);
        if ($record === null) {
            return redirect()->route('qldtoto.bao-cao-chi-tiet.index')->with('error', 'Không tìm thấy kỳ thi.');
        }

        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        return view('QldtOto.bao-cao-chi-tiet.show', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'record' => $record,
            'thiSinh' => $record->thiSinh(),
            'canhBao' => $record->canhBaoList(),
        ]);
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

    /** @return array<string, string> */
    private function docDiemSbd(?UploadedFile $file): array
    {
        if ($file === null) {
            return [];
        }
        $path = $this->tempPath($file);
        try {
            return DocDiemKetQua::docTheoSbd($path);
        } finally {
            @unlink($path);
        }
    }

    /** @return array<string, string> */
    private function docDiemCccd(?UploadedFile $file): array
    {
        if ($file === null) {
            return [];
        }
        $path = $this->tempPath($file);
        try {
            return DocDiemKetQua::docTheoCccd($path);
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
}