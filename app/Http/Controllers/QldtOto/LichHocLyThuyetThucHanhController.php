<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\LichHocLyThuyetThucHanhHangB01Exporter;
use App\Support\QldtOto\LichHocLyThuyetThucHanhHangBSanExporter;
use App\Support\QldtOto\LichHocLyThuyetThucHanhHangCExporter;
use App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay;
use App\Support\QldtOto\LichHocLyThuyetThucHanhMocMoTa;
use App\Support\QldtOto\LichHocLyThuyetThucHanhPreview;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Lịch LT+TH — bước 1: xuất chung cả khoá (từng GV sau). */
class LichHocLyThuyetThucHanhController extends Controller
{
    private const SLUG = '1-lich-hoc-ly-thuyet-va-lich-hoc-thuc-hanh-cua-tung-giao-vien';

    /** @var list<string> */
    private const FORM_NGAY = [
        'ngay_khai_giang', 'ngay_bd_thuc_hanh',
        'ngay_bd_hinh_tren', 'ngay_kt_hinh_tren',
        'ngay_bd_cabin', 'ngay_kt_cabin',
        'ngay_bd_dat', 'ngay_kt_dat', 'ngay_bo_sung_dat',
        'ngay_bd_hinh_duoi', 'ngay_kt_hinh_duoi',
        'ngay_bd_on_luyen', 'ngay_kt_on_luyen', 'ngay_kiem_tra',
    ];

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $hang = '';
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $formNgay = [];
        $goiY = [];
        $goiYHienThi = [];
        $previewDong = [];
        $tenKhoaDoc = '';

        if ($khoa instanceof KhoaHoc) {
            $hang = LichHocLyThuyetThucHanhLichNgay::hangGoiYTuKhoa($khoa)[0]
                ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;
            $tenKhoaDoc = PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa);
            $kg = $khoa->NgayKG ? Carbon::parse($khoa->NgayKG) : null;
            $goiY = LichHocLyThuyetThucHanhLichNgay::goiYTuLichGiaoVien($maKh, $kg, $hang);
            if ($kg) {
                $goiY['ngay_khai_giang'] = $kg->format('Y-m-d');
            }
            foreach (self::FORM_NGAY as $k) {
                $formNgay[$k] = old($k, $request->query($k, $goiY[$k] ?? ''));
            }
            $goiYHienThi = LichHocLyThuyetThucHanhMocMoTa::hienThiGoiY($goiY, $hang);
            $previewDong = LichHocLyThuyetThucHanhPreview::dongXemTruoc($hang, $goiY, $formNgay, $goiYHienThi);
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.lich-hoc-ly-thuyet-thuc-hanh.form', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'maKh' => $maKh,
            'hang' => $hang,
            'tenKhoaDoc' => $tenKhoaDoc,
            'goiY' => $goiY,
            'formNgay' => $formNgay,
            'nhanHang' => LichHocLyThuyetThucHanhLichNgay::nhanHang(),
            'linkLichGv' => $maKh !== '' ? route('pmgplx.lich.gv.index', ['ma_kh' => $maKh]) : route('pmgplx.lich.gv.index'),
            'linkLichXeTap' => $maKh !== '' ? route('pmgplx.lich.xe.index', ['ma_kh' => $maKh]) : route('pmgplx.lich.xe.index'),
            'linkDanhMucKhoaHoc' => $maKh !== ''
                ? route('pmgplx.dm.khoa-hoc.index', ['tu_khoa' => $maKh])
                : route('pmgplx.dm.khoa-hoc.index'),
            'nhanMocTrenFile' => LichHocLyThuyetThucHanhMocMoTa::nhanMocTrenFile($maKh !== '' ? $hang : null),
            'goiYHienThi' => $goiYHienThi,
            'previewDong' => $previewDong,
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $validated = $request->validate([
            'ma_kh' => ['required', 'string'],
            'ten_khoa_doc' => ['nullable', 'string', 'max:200'],
        ] + collect(self::FORM_NGAY)->mapWithKeys(fn (string $k) => [
            $k => ['nullable', 'date'],
        ])->all());

        $maKh = trim($validated['ma_kh']);
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa) {
            return back()->with('error', 'Không tìm thấy khoá.');
        }

        $hangOptions = LichHocLyThuyetThucHanhLichNgay::hangGoiYTuKhoa($khoa);
        $hang = $hangOptions[0] ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;

        if (! in_array($hang, [
            LichHocLyThuyetThucHanhLichNgay::HANG_C,
            LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN,
            LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG,
        ], true)) {
            return back()->with('error', 'Hạng đào tạo không hỗ trợ xuất lịch chung.');
        }

        $formNgay = [];
        foreach (self::FORM_NGAY as $k) {
            $formNgay[$k] = $validated[$k] ?? null;
        }

        $kg = $khoa->NgayKG ? Carbon::parse($khoa->NgayKG) : null;
        $goiY = LichHocLyThuyetThucHanhLichNgay::goiYTuLichGiaoVien($maKh, $kg, $hang);
        $moc = LichHocLyThuyetThucHanhLichNgay::hopNhatMocNgay($goiY, $formNgay);
        if ($moc['ngay_khai_giang'] === null && $kg) {
            $moc['ngay_khai_giang'] = $kg->copy()->startOfDay();
        }

        if ($moc['ngay_khai_giang'] === null) {
            return back()->withInput()->with('error', 'Khoá chưa có Ngày KG trên danh mục khoá học (/pmgplx/danh-muc/khoa-hoc).');
        }

        $ten = trim((string) ($validated['ten_khoa_doc'] ?? ''));
        if ($ten === '') {
            $ten = PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa);
        }

        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $maKh) ?: 'khoa';

        return match ($hang) {
            LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG => LichHocLyThuyetThucHanhHangB01Exporter::download($ten, $moc, 'lich-lt-th-'.$slug),
            LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN => LichHocLyThuyetThucHanhHangBSanExporter::download($ten, $moc, 'lich-lt-th-'.$slug),
            default => LichHocLyThuyetThucHanhHangCExporter::download($ten, $moc, 'lich-lt-th-'.$slug),
        };
    }
}
