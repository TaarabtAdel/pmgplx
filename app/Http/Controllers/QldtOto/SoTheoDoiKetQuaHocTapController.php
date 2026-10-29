<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\SoTheoDoiKetQuaHocTapDocxExporter;
use App\Support\QldtOto\SoTheoDoiKetQuaHocTapHocVien;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SoTheoDoiKetQuaHocTapController extends Controller
{
    private const SLUG = '1-so-theo-doi-ket-qua-hoc-tap-cua-hoc-vien';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'HangDT', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.so-theo-doi-ket-qua-hoc-tap', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'form' => [
                'ma_kh' => trim((string) $request->query('ma_kh', '')),
                'ten_khoa' => '',
                'ngay_lap' => '',
            ],
        ]);
    }

    public function export(Request $request, SoTheoDoiKetQuaHocTapDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ten_khoa' => ['required', 'string', 'max:80'],
            'ngay_lap' => ['required', 'date'],
        ]);

        $khoa = KhoaHoc::query()->find($validated['ma_kh']);
        if (! $khoa instanceof KhoaHoc) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $rows = SoTheoDoiKetQuaHocTapHocVien::dongXuat(
            $validated['ma_kh'],
            trim($validated['ten_khoa']),
        );
        if ($rows === []) {
            return back()->withErrors(['ma_kh' => 'Khoá không có học viên trên PMGPLX.'])->withInput();
        }

        $ngayLap = BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($validated['ngay_lap']));

        $tmp = storage_path('app/tmp/so-theo-doi-kq-'.uniqid('', true).'.docx');
        $exporter->generate(trim($validated['ten_khoa']), $ngayLap, $rows, $tmp);

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $validated['ma_kh']) ?: 'khoa';

        return response()->download($tmp, 'so-theo-doi-ket-qua-hoc-tap-'.$safe.'.docx')->deleteFileAfterSend(true);
    }

    public function khoaJson(string $maKh): \Illuminate\Http\JsonResponse
    {
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa instanceof KhoaHoc) {
            return response()->json(['message' => 'Không tìm thấy khoá.'], 404);
        }

        $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
        $tenKh = trim((string) ($khoa->TenKH ?? ''));
        $nhanKhoa = $tenKh !== '' ? $tenKh : (string) $khoa->MaKH;

        return response()->json([
            'ma_kh' => $khoa->MaKH,
            'ten_kh' => $khoa->TenKH,
            'ten_khoa' => $nhanKhoa,
            'so_luong_hv' => $tuKhoa['so_luong_hv'],
        ]);
    }
}
