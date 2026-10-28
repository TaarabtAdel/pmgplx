<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyDocxExporter;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BaoCaoDangKyController extends Controller
{
    private const SLUG = '1-bao-cao-dang-ky';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $now = Carbon::now();
        $defaults = [
            'so_cong_van' => '637',
            'ngay_bao_cao' => $now->format('Y-m-d'),
            'ma_kh' => $maKh,
            'hang_dt' => '',
            'khoa_so' => '',
            'khoa_lop' => '',
            'hang_gplx' => '',
            'so_luong_hv' => '',
            'ngay_kg' => '',
            'ngay_bg' => '',
            'ghi_chu' => 'Đào tạo mới',
        ];

        if ($khoa instanceof KhoaHoc) {
            $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
            $defaults = array_merge($defaults, [
                'hang_dt' => $tuKhoa['hang_dt'],
                'khoa_so' => $tuKhoa['khoa_so'],
                'khoa_lop' => $tuKhoa['khoa_lop'],
                'hang_gplx' => $tuKhoa['hang_gplx'],
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

        return view('QldtOto.bao-cao-dang-ky', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'form' => $defaults,
            'namHienTai' => (int) $now->format('Y'),
        ]);
    }

    public function export(Request $request, BaoCaoDangKyDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'so_cong_van' => ['required', 'string', 'max:20'],
            'ngay_bao_cao' => ['required', 'date'],
            'ma_kh' => ['required', 'string', 'max:50'],
            'hang_dt' => ['required', 'string', 'max:20'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'khoa_lop' => ['required', 'string', 'max:50'],
            'hang_gplx' => ['required', 'string', 'max:80'],
            'so_luong_hv' => ['required', 'integer', 'min:1'],
            'ngay_kg' => ['required', 'string', 'max:20'],
            'ngay_bg' => ['required', 'string', 'max:20'],
            'ghi_chu' => ['nullable', 'string', 'max:255'],
        ]);

        $exists = KhoaHoc::query()->where('MaKH', $validated['ma_kh'])->exists();
        if (! $exists) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $ngay = Carbon::parse($validated['ngay_bao_cao']);
        $nam = (int) $ngay->format('Y');
        $soCvFull = trim($validated['so_cong_van']).'/TTML-'.$nam;
        $ngayDong = BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay);

        $ghiChu = trim((string) ($validated['ghi_chu'] ?? ''));
        if ($ghiChu === '') {
            $ghiChu = 'Đào tạo mới';
        }

        $tmp = storage_path('app/tmp/bao-cao-dang-ky-'.uniqid('', true).'.docx');
        $exporter->generate([
            'so_cv_full' => $soCvFull,
            'ngay_dong' => $ngayDong,
            'hang_dt' => $validated['hang_dt'],
            'khoa_so' => $validated['khoa_so'],
            'khoa_lop' => $validated['khoa_lop'],
            'hang_gplx' => $validated['hang_gplx'],
            'so_luong_hv' => (string) $validated['so_luong_hv'],
            'ngay_kg' => $validated['ngay_kg'],
            'ngay_bg' => $validated['ngay_bg'],
            'ghi_chu' => $ghiChu,
        ], $tmp);

        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', $validated['ma_kh']) ?: 'khoa';
        $downloadName = 'bao-cao-dang-ky-'.$safeMa.'.docx';

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
            ...$tuKhoa,
            'so_luong_hv' => $tuKhoa['so_luong_hv'],
        ]);
    }
}
