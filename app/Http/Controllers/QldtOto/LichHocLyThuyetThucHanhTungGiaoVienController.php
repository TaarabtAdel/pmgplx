<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\PMGPLX\PmgplxLichGiaoVienBangNhapFile;
use App\Support\QldtOto\LichDuKienThucHanhPmgplxExcelExporter;
use App\Support\QldtOto\LichHocThucHanhGiaoVienDanhSach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Xuất lịch giáo viên PMGPLX theo mẫu file nhập lịch (7 cột / giáo viên). */
class LichHocLyThuyetThucHanhTungGiaoVienController extends Controller
{
    private const SLUG = '1-lich-hoc-ly-thuyet-va-lich-hoc-thuc-hanh-cua-tung-giao-vien';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;
        $danhSachGv = $maKh !== '' ? LichHocThucHanhGiaoVienDanhSach::nhomTheoGiaoVien($maKh) : [];

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
            'tenKhoa' => $khoa instanceof KhoaHoc ? trim((string) $khoa->TenKH) : '',
            'danhSachGv' => $danhSachGv,
            'linkLichGv' => $maKh !== '' ? route('pmgplx.lich.gv.index', ['ma_kh' => $maKh]) : route('pmgplx.lich.gv.index'),
            'linkNhapLich' => route('pmgplx.lich.nhap-file.create'),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
        ]);

        $maKh = trim($validated['ma_kh']);
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa) {
            return back()->with('error', 'Không tìm thấy khoá.');
        }

        try {
            $bang = PmgplxLichGiaoVienBangNhapFile::build($maKh);
            $stem = 'lich-gv-pmgplx-'.(preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', (string) $khoa->TenKH) ?: 'khoa').'-'.$maKh;

            return LichDuKienThucHanhPmgplxExcelExporter::downloadWorkbookMotGiaoVienMotSheet(
                $maKh,
                $bang,
                $stem,
                static fn (string $maGv, array $full): ?array => PmgplxLichGiaoVienBangNhapFile::thuBangChoMotGiaoVien($full, $maGv)
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}