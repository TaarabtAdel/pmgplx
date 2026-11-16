<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\LichHocLyThuyetThucHanhFormNgay;
use App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay;
use App\Support\QldtOto\LichHocLyThuyetThucHanhMocMoTa;
use App\Support\QldtOto\LichHocLyThuyetThucHanhPreview;
use App\Support\QldtOto\LichHocLyThuyetThucHanhTungGiaoVienExporter;
use App\Support\QldtOto\LichHocThucHanhGiaoVienDanhSach;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Lịch LT+TH — xuất từng giáo viên (mẫu hạng C, workbook hoặc ZIP). */
class LichHocLyThuyetThucHanhTungGiaoVienController extends Controller
{
    private const SLUG = '1-lich-hoc-ly-thuyet-va-lich-hoc-thuc-hanh-cua-tung-giao-vien';

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
        $danhSachGv = [];

        if ($khoa instanceof KhoaHoc) {
            $hang = LichHocLyThuyetThucHanhLichNgay::hangGoiYTuKhoa($khoa)[0]
                ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;
            $tenKhoaDoc = PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa);
            $kg = $khoa->NgayKG ? Carbon::parse($khoa->NgayKG) : null;
            $goiY = LichHocLyThuyetThucHanhLichNgay::goiYTuLichGiaoVien($maKh, $kg, $hang);
            if ($kg) {
                $goiY['ngay_khai_giang'] = $kg->format('Y-m-d');
            }
            foreach (LichHocLyThuyetThucHanhFormNgay::KEYS as $k) {
                $formNgay[$k] = old($k, $request->query($k, $goiY[$k] ?? ''));
            }
            $goiYHienThi = LichHocLyThuyetThucHanhMocMoTa::hienThiGoiY($goiY, $hang);
            $previewDong = LichHocLyThuyetThucHanhPreview::dongXemTruoc($hang, $goiY, $formNgay, $goiYHienThi);
            $danhSachGv = LichHocThucHanhGiaoVienDanhSach::nhomTheoGiaoVien($maKh);
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.lich-hoc-ly-thuyet-thuc-hanh.tung-giao-vien', [
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
            'linkLichChung' => $maKh !== ''
                ? route('qldtoto.lich-du-kien.ly-thuyet.index', ['ma_kh' => $maKh])
                : route('qldtoto.lich-du-kien.ly-thuyet.index'),
            'linkDanhMucKhoaHoc' => $maKh !== ''
                ? route('pmgplx.dm.khoa-hoc.index', ['tu_khoa' => $maKh])
                : route('pmgplx.dm.khoa-hoc.index'),
            'nhanMocTrenFile' => LichHocLyThuyetThucHanhMocMoTa::nhanMocTrenFile($maKh !== '' ? $hang : null),
            'goiYHienThi' => $goiYHienThi,
            'previewDong' => $previewDong,
            'danhSachGv' => $danhSachGv,
        ]);
    }

    public function exportWorkbook(Request $request): StreamedResponse|RedirectResponse
    {
        return $this->export($request, false);
    }

    public function exportZip(Request $request): StreamedResponse|RedirectResponse
    {
        return $this->export($request, true);
    }

    private function export(Request $request, bool $zip): StreamedResponse|RedirectResponse
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $validated = $request->validate([
            'ma_kh' => ['required', 'string'],
            'ten_khoa_doc' => ['nullable', 'string', 'max:200'],
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

        $formNgay = [];
        foreach (LichHocLyThuyetThucHanhFormNgay::KEYS as $k) {
            $formNgay[$k] = $validated[$k] ?? null;
        }

        $kg = $khoa->NgayKG ? Carbon::parse($khoa->NgayKG) : null;
        $goiY = LichHocLyThuyetThucHanhLichNgay::goiYTuLichGiaoVien($maKh, $kg, $hang);
        $moc = LichHocLyThuyetThucHanhLichNgay::hopNhatMocNgay($goiY, $formNgay);
        if ($moc['ngay_khai_giang'] === null && $kg) {
            $moc['ngay_khai_giang'] = $kg->copy()->startOfDay();
        }

        if ($moc['ngay_khai_giang'] === null) {
            return back()->withInput()->with('error', 'Khoá chưa có Ngày KG trên danh mục khoá học.');
        }

        $ten = trim((string) ($validated['ten_khoa_doc'] ?? ''));
        if ($ten === '') {
            $ten = PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa);
        }

        try {
            return $zip
                ? LichHocLyThuyetThucHanhTungGiaoVienExporter::downloadZip($maKh, $ten, $moc, $hang)
                : LichHocLyThuyetThucHanhTungGiaoVienExporter::downloadWorkbook($maKh, $ten, $moc, $hang);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}