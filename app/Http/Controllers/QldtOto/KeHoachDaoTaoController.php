<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\KeHoachDaoTaoDocx;
use App\Support\QldtOto\LichHocLyThuyetThucHanhFormNgay;
use App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay;
use App\Support\QldtOto\LichHocLyThuyetThucHanhMocMoTa;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KeHoachDaoTaoController extends Controller
{
    private const SLUG = '1-ke-hoach-dao-tao';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $hang = '';
        $formNgay = [];
        $goiY = [];
        $goiYHienThi = [];
        $formMeta = null;

        if ($khoa instanceof KhoaHoc) {
            $hang = LichHocLyThuyetThucHanhLichNgay::hangGoiYTuKhoa($khoa)[0]
                ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;
            $kg = $khoa->NgayKG ? Carbon::parse($khoa->NgayKG) : null;
            $goiY = LichHocLyThuyetThucHanhLichNgay::goiYTuLichGiaoVien($maKh, $kg, $hang);
            if ($kg) {
                $goiY['ngay_khai_giang'] = $kg->format('Y-m-d');
            }
            foreach (LichHocLyThuyetThucHanhFormNgay::KEYS as $k) {
                $formNgay[$k] = old($k, $request->query($k, $goiY[$k] ?? ''));
            }
            $goiYHienThi = LichHocLyThuyetThucHanhMocMoTa::hienThiGoiY($goiY, $hang);

            $dangSd = KeHoachDaoTaoDocx::demGvXeDangSuDung();
            $cfgHang = config('qldt_oto_ke_hoach_dao_tao.'.$hang, []);
            $hangHt = (string) ($cfgHang['hang_hien_thi'] ?? BaoCaoDangKyKhoaHoc::nhanHangGplx($khoa->HangGPLX ?? null));

            $formMeta = [
                'ma_kh' => $maKh,
                'hang' => $hang,
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'hang_hien_thi' => $hangHt,
                'so_hv' => BaoCaoDangKyKhoaHoc::demHocVien($maKh),
                'so_gv_khoa' => KeHoachDaoTaoDocx::demGiaoVienKhoa($maKh),
                'so_gv_dang_sd' => $dangSd['gv'],
                'so_gv_tong' => $dangSd['gv'],
                'so_xe_khoa' => KeHoachDaoTaoDocx::demXeTapKhoa($maKh),
                'so_xe_dang_sd' => $dangSd['xe'],
                'so_xe_tong' => $dangSd['xe'],
            ];
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.ke-hoach-dao-tao', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'hang' => $hang,
            'nhanHang' => LichHocLyThuyetThucHanhLichNgay::nhanHang(),
            'formNgay' => $formNgay,
            'goiYHienThi' => $goiYHienThi,
            'nhanMocTrenFile' => LichHocLyThuyetThucHanhMocMoTa::nhanMocTrenFile($maKh !== '' ? $hang : null),
            'formMeta' => $formMeta,
            'linkLichLtTh' => route('qldtoto.lich-du-kien.ly-thuyet.index', ['ma_kh' => $maKh]),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $validated = $request->validate([
            'ma_kh' => ['required', 'string'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'hang_hien_thi' => ['required', 'string', 'max:60'],
            'so_hv' => ['required', 'integer', 'min:1', 'max:999'],
            'so_gv_khoa' => ['nullable', 'integer', 'min:0', 'max:999'],
            'so_gv_dang_sd' => ['nullable', 'integer', 'min:0', 'max:999'],
            'so_gv_tong' => ['nullable', 'integer', 'min:0', 'max:999'],
            'so_xe_khoa' => ['nullable', 'integer', 'min:0', 'max:999'],
            'so_xe_dang_sd' => ['nullable', 'integer', 'min:0', 'max:999'],
            'so_xe_tong' => ['nullable', 'integer', 'min:0', 'max:999'],
        ] + collect(LichHocLyThuyetThucHanhFormNgay::KEYS)->mapWithKeys(fn (string $k) => [
            $k => ['nullable', 'date'],
        ])->all());

        $maKh = trim($validated['ma_kh']);
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa) {
            return back()->with('error', 'Không tìm thấy khoá.');
        }

        $hang = LichHocLyThuyetThucHanhLichNgay::hangGoiYTuKhoa($khoa)[0]
            ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;

        if (! config()->has('qldt_oto_ke_hoach_dao_tao.'.$hang)) {
            return back()->withInput()->with('error', 'Hạng khoá chưa hỗ trợ xuất kế hoạch đào tạo.');
        }

        $kg = $khoa->NgayKG ? Carbon::parse($khoa->NgayKG) : null;
        $goiY = LichHocLyThuyetThucHanhLichNgay::goiYTuLichGiaoVien($maKh, $kg, $hang);
        $formNgay = [];
        foreach (LichHocLyThuyetThucHanhFormNgay::KEYS as $k) {
            $formNgay[$k] = $validated[$k] ?? null;
        }
        $moc = LichHocLyThuyetThucHanhLichNgay::hopNhatMocNgay($goiY, $formNgay);
        if ($moc['ngay_khai_giang'] === null && $kg) {
            $moc['ngay_khai_giang'] = $kg->copy()->startOfDay();
        }
        if ($moc['ngay_khai_giang'] === null) {
            return back()->withInput()->with('error', 'Khoá chưa có Ngày KG trên danh mục khoá học.');
        }

        $them = [
            'khoa_so' => trim($validated['khoa_so']),
            'hang_hien_thi' => trim($validated['hang_hien_thi']),
            'so_hv' => (int) $validated['so_hv'],
            'so_gv_khoa' => (int) ($validated['so_gv_khoa'] ?? 0),
            'so_gv_dang_sd' => (int) ($validated['so_gv_dang_sd'] ?? 0),
            'so_gv_tong' => (int) ($validated['so_gv_tong'] ?? 0),
            'so_xe_khoa' => (int) ($validated['so_xe_khoa'] ?? 0),
            'so_xe_dang_sd' => (int) ($validated['so_xe_dang_sd'] ?? 0),
            'so_xe_tong' => (int) ($validated['so_xe_tong'] ?? 0),
        ];

        $tmp = storage_path('app/tmp/ke-hoach-dao-tao-'.uniqid('', true).'.docx');
        if (! is_dir(dirname($tmp))) {
            @mkdir(dirname($tmp), 0755, true);
        }

        try {
            KeHoachDaoTaoDocx::generate($maKh, $hang, $moc, $them, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $maKh) ?: 'khoa';

        return response()
            ->download($tmp, 'ke-hoach-dao-tao-'.$them['khoa_so'].'-'.$slug.'.docx')
            ->deleteFileAfterSend(true);
    }
}