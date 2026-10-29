<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\QuyetDinhThanhLapLopHocDocxExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuyetDinhThanhLapLopHocController extends Controller
{
    private const SLUG = '1-quyet-dinh-thanh-lap-lop-hoc-danh-sach-hoc-vien';

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
            'ngay_kg' => '',
            'ngay_bg' => '',
            'giao_vien_hd' => 'Cô Hoàng Ngọc Hà',
        ];

        if ($khoa instanceof KhoaHoc) {
            $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
            $defaults = array_merge($defaults, [
                'hang_gplx' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'khoa_so' => $tuKhoa['khoa_so'],
                'so_luong_hv' => (string) $tuKhoa['so_luong_hv'],
                'ngay_kg' => $tuKhoa['ngay_kg'],
                'ngay_bg' => $tuKhoa['ngay_bg'],
            ]);
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'HangDT', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.quyet-dinh-thanh-lap-lop-hoc', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'form' => $defaults,
        ]);
    }

    public function export(Request $request, QuyetDinhThanhLapLopHocDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'so_quyet_dinh' => ['required', 'string', 'max:20'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'ma_kh' => ['required', 'string', 'max:50'],
            'hang_gplx' => ['required', 'string', 'max:80'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'so_luong_hv' => ['required', 'integer', 'min:1'],
            'ngay_kg' => ['required', 'string', 'max:20'],
            'ngay_bg' => ['required', 'string', 'max:20'],
            'giao_vien_hd' => ['required', 'string', 'max:120'],
        ]);

        if (! KhoaHoc::query()->where('MaKH', $validated['ma_kh'])->exists()) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $ngay = Carbon::parse($validated['ngay_quyet_dinh']);
        $soQdFull = trim($validated['so_quyet_dinh']).'/QĐ-TTML';
        $ngayDong = BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay);

        $tmp = storage_path('app/tmp/quyet-dinh-thanh-lap-'.uniqid('', true).'.docx');
        $exporter->generate([
            'so_qd_full' => $soQdFull,
            'ngay_dong' => $ngayDong,
            'hang_gplx' => $validated['hang_gplx'],
            'khoa_so' => $validated['khoa_so'],
            'so_luong_hv' => (string) $validated['so_luong_hv'],
            'ngay_kg' => $validated['ngay_kg'],
            'ngay_bg' => $validated['ngay_bg'],
            'giao_vien_hd' => $validated['giao_vien_hd'],
        ], $tmp);

        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', $validated['ma_kh']) ?: 'khoa';
        $downloadName = 'quyet-dinh-thanh-lap-lop-'.$safeMa.'.docx';

        return response()->download($tmp, $downloadName)->deleteFileAfterSend(true);
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
            'ngay_kg' => $tuKhoa['ngay_kg'],
            'ngay_bg' => $tuKhoa['ngay_bg'],
        ]);
    }
}
