<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\DanhSachHocSinhLaiXeExcelExporter;
use App\Support\QldtOto\DanhSachHocSinhLaiXeHocVien;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DanhSachHocSinhLaiXeController extends Controller
{
    private const SLUG = '1-danh-sach-hoc-sinh-lai-xe';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $now = Carbon::now();
        $defaults = [
            'so_quyet_dinh' => '638',
            'ngay_quyet_dinh' => $now->format('Y-m-d'),
            'ma_kh' => $maKh,
            'hang_gplx' => '',
            'khoa_so' => '',
            'so_luong_hv' => '',
        ];

        if ($khoa instanceof KhoaHoc) {
            $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
            $defaults = array_merge($defaults, [
                'hang_gplx' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'khoa_so' => $tuKhoa['khoa_so'],
                'so_luong_hv' => (string) $tuKhoa['so_luong_hv'],
            ]);
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'HangDT', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.danh-sach-hoc-sinh-lai-xe', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'form' => $defaults,
        ]);
    }

    public function export(Request $request, DanhSachHocSinhLaiXeExcelExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'so_quyet_dinh' => ['required', 'string', 'max:20'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'ma_kh' => ['required', 'string', 'max:50'],
            'hang_gplx' => ['required', 'string', 'max:80'],
            'khoa_so' => ['required', 'string', 'max:20'],
        ]);

        $khoa = KhoaHoc::query()->find($validated['ma_kh']);
        if (! $khoa instanceof KhoaHoc) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $hocVien = DanhSachHocSinhLaiXeHocVien::dongXuat($validated['ma_kh']);
        if ($hocVien === []) {
            return back()->withErrors(['ma_kh' => 'Khoá không có học viên trên PMGPLX.'])->withInput();
        }

        $ngay = Carbon::parse($validated['ngay_quyet_dinh']);
        $soQdFull = trim($validated['so_quyet_dinh']).'/QĐ-TTML';
        $hangTieuDe = DanhSachHocSinhLaiXeHocVien::hangTieuDe($khoa->HangGPLX ?? null, $khoa->HangDT ?? null);

        $meta = [
            'tieu_de' => DanhSachHocSinhLaiXeExcelExporter::tieuDe($hangTieuDe, $validated['khoa_so']),
            'dong_quyet_dinh' => DanhSachHocSinhLaiXeExcelExporter::dongQuyetDinh(
                $soQdFull,
                $ngay,
                $validated['hang_gplx'],
                $validated['khoa_so'],
            ),
        ];

        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', $validated['ma_kh']) ?: 'khoa';
        $downloadName = 'danh-sach-hoc-sinh-lai-xe-'.$safeMa.'.xlsx';

        return $exporter->download($meta, $hocVien, $downloadName);
    }

    public function khoaJson(string $maKh): \Illuminate\Http\JsonResponse
    {
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa instanceof KhoaHoc) {
            return response()->json(['message' => 'Không tìm thấy khoá.'], 404);
        }

        $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);

        return response()->json([
            'ma_kh' => $khoa->MaKH,
            'ten_kh' => $khoa->TenKH,
            'khoa_so' => $tuKhoa['khoa_so'],
            'hang_gplx' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
            'so_luong_hv' => $tuKhoa['so_luong_hv'],
        ]);
    }
}
