<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use App\Support\QldtOto\SoTayGiaoVienThucHanhDocxExporter;
use App\Support\QldtOto\SoTayGiaoVienThucHanhGiaoVien;
use App\Support\QldtOto\SoTheoDoiKetQuaHocTapHocVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SoTayGiaoVienThucHanhController extends Controller
{
    private const SLUG = '1-so-tay-cac-giao-vien-thuc-hanh';

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

        return view('QldtOto.so-tay-giao-vien-thuc-hanh.form', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'form' => [
                'ma_kh' => trim((string) $request->query('ma_kh', '')),
                'ten_lop' => '',
                'mon' => 'Thực hành lái xe',
                'qd_thanh_lap' => '',
                'phuong_thuc' => 'Tập trung',
            ],
        ]);
    }

    public function export(Request $request, SoTayGiaoVienThucHanhDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ten_lop' => ['required', 'string', 'max:80'],
            'mon' => ['nullable', 'string', 'max:120'],
            'qd_thanh_lap' => ['nullable', 'string', 'max:500'],
            'phuong_thuc' => ['nullable', 'string', 'max:200'],
        ]);

        $khoa = KhoaHoc::query()->find($validated['ma_kh']);
        if (! $khoa instanceof KhoaHoc) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $giaoVien = SoTayGiaoVienThucHanhGiaoVien::danhSach($validated['ma_kh']);
        if ($giaoVien === []) {
            return back()->withErrors(['ma_kh' => 'Khoá chưa có giáo viên thực hành trên lịch PMGPLX.'])->withInput();
        }

        $tenLop = trim($validated['ten_lop']);
        $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
        $hangGplx = $tuKhoa['hang_gplx'] !== '—' ? $tuKhoa['hang_gplx'] : trim((string) ($khoa->HangDT ?? ''));

        $hocVienRaw = SoTheoDoiKetQuaHocTapHocVien::dongXuat($validated['ma_kh'], $tenLop);
        $hocVien = array_map(static fn (array $r): array => [
            'stt' => $r['stt'],
            'ho_ten' => $r['ho_ten'],
        ], $hocVienRaw);

        $qdLines = preg_split('/\r\n|\r|\n/', trim((string) ($validated['qd_thanh_lap'] ?? ''))) ?: [];
        $qd1 = trim($qdLines[0] ?? '');
        $qd2 = trim(implode(' ', array_slice($qdLines, 1)));

        $tenGvLop = implode(', ', array_map(static fn (array $g) => $g['ho_ten'], $giaoVien));
        $mon = trim((string) ($validated['mon'] ?? '')) !== '' ? trim((string) $validated['mon']) : 'Thực hành lái xe';
        $phuongThuc = trim((string) ($validated['phuong_thuc'] ?? '')) !== '' ? trim((string) $validated['phuong_thuc']) : 'Tập trung';

        $chung = [
            'MON' => $mon,
            'LOP' => $tenLop,
            'HANG_GPLX' => $hangGplx,
            'KHOA' => $tenLop,
            'QD_THANH_LAP' => $qd1,
            'QD_THANH_LAP_2' => $qd2,
            'SI_SO' => (string) $tuKhoa['so_luong_hv'],
            'GIAO_VIEN_LOP' => $tenGvLop,
            'PHUONG_THUC' => $phuongThuc,
        ];

        $tmpdir = storage_path('app/tmp/so-tay-gv-th-'.uniqid('', true));
        @mkdir($tmpdir, 0755, true);

        $files = [];
        foreach ($giaoVien as $gv) {
            $fields = $chung + ['TEN_GV' => $gv['ho_ten']];
            $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', $gv['ma_gv']) ?: 'gv';
            $path = $tmpdir.'/so-tay-'.$safeMa.'.docx';
            $exporter->generate($fields, $hocVien, $path);
            $files[] = ['path' => $path, 'name' => 'so-tay-'.$validated['ma_kh'].'-'.$safeMa.'.docx'];
        }

        if (count($files) === 1) {
            return response()->download($files[0]['path'], $files[0]['name'])->deleteFileAfterSend(true);
        }

        $zipPath = $tmpdir.'.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Không tạo được file nén.')->withInput();
        }
        foreach ($files as $f) {
            $zip->addFile($f['path'], $f['name']);
        }
        $zip->close();

        $safeKh = preg_replace('/[^A-Za-z0-9_-]+/', '-', $validated['ma_kh']) ?: 'khoa';

        return response()->download($zipPath, 'so-tay-giao-vien-thuc-hanh-'.$safeKh.'.zip')->deleteFileAfterSend(true);
    }

    public function khoaJson(string $maKh): JsonResponse
    {
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa instanceof KhoaHoc) {
            return response()->json(['message' => 'Không tìm thấy khoá.'], 404);
        }

        $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
        $giaoVien = SoTayGiaoVienThucHanhGiaoVien::danhSach($maKh);

        return response()->json([
            'ma_kh' => $khoa->MaKH,
            'ten_kh' => $khoa->TenKH,
            'ten_lop' => PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa),
            'hang_gplx' => $tuKhoa['hang_gplx'],
            'so_luong_hv' => $tuKhoa['so_luong_hv'],
            'so_giao_vien' => count($giaoVien),
            'giao_vien' => array_column($giaoVien, 'ho_ten'),
        ]);
    }
}
