<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use App\Support\QldtOto\SoTayGiaoVienHocVien;
use App\Support\QldtOto\SoTayGiaoVienLyThuyetDocxExporter;
use App\Support\QldtOto\SoTayQuyetDinhThanhLap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SoTayGiaoVienLyThuyetController extends Controller
{
    private const SLUG = '1-so-tay-cac-giao-vien-ly-thuyet';

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

        return view('QldtOto.so-tay-giao-vien-ly-thuyet.form', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'giaoVienOptions' => PhanCongGvXeTapLaiFormSupport::giaoVienSelectOptions(),
            'form' => [
                'ma_kh' => trim((string) $request->query('ma_kh', '')),
                'ma_gv' => '',
                'ten_lop' => '',
                'tieu_de_mon' => 'TỰ HỌC CÓ HƯỚNG DẪN MÔN LÝ THUYẾT ONLINE',
                'so_quyet_dinh' => '',
                'ngay_quyet_dinh' => '',
                'phuong_thuc' => 'Online tự học có hướng dẫn',
            ],
        ]);
    }

    public function export(Request $request, SoTayGiaoVienLyThuyetDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ten_lop' => ['required', 'string', 'max:80'],
            'ma_gv' => ['required', 'string', 'max:20'],
            'tieu_de_mon' => ['nullable', 'string', 'max:200'],
            'so_quyet_dinh' => ['nullable', 'regex:/^\d+$/', 'max:10'],
            'ngay_quyet_dinh' => ['nullable', 'date', 'required_with:so_quyet_dinh'],
            'phuong_thuc' => ['nullable', 'string', 'max:200'],
        ], [
            'so_quyet_dinh.regex' => 'Số quyết định chỉ gồm chữ số.',
            'ma_gv.required' => 'Chọn giáo viên chủ nhiệm (GVCN).',
        ]);

        $khoa = KhoaHoc::query()->find($validated['ma_kh']);
        if (! $khoa instanceof KhoaHoc) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $tenGvcn = SoTayGiaoVienHocVien::hoTenTuMa($validated['ma_gv']);
        if ($tenGvcn === null) {
            return back()->withErrors(['ma_gv' => 'Không tìm thấy giáo viên trên danh mục PMGPLX.'])->withInput();
        }

        $tenLop = trim($validated['ten_lop']);
        $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
        $hangGplx = $tuKhoa['hang_gplx'] !== '—' ? $tuKhoa['hang_gplx'] : trim((string) ($khoa->HangDT ?? ''));

        $hocVien = SoTayGiaoVienHocVien::bangNghi($validated['ma_kh']);
        if (count($hocVien) > SoTayGiaoVienHocVien::SO_DONG_BANG) {
            return back()->withErrors([
                'ma_kh' => 'Khoá có '.count($hocVien).' học viên; bảng sổ tay tối đa '.SoTayGiaoVienHocVien::SO_DONG_BANG.'.',
            ])->withInput();
        }

        $tieuDe = trim((string) ($validated['tieu_de_mon'] ?? '')) !== ''
            ? trim((string) $validated['tieu_de_mon'])
            : 'TỰ HỌC CÓ HƯỚNG DẪN MÔN LÝ THUYẾT ONLINE';
        $phuongThuc = trim((string) ($validated['phuong_thuc'] ?? '')) !== ''
            ? trim((string) $validated['phuong_thuc'])
            : 'Online tự học có hướng dẫn';

        $fields = [
            'TIEU_DE_MON' => $tieuDe,
            'HANG_GPLX' => $hangGplx,
            'KHOA' => $tenLop,
            'TEN_GV' => $tenGvcn,
            'QD_THANH_LAP' => SoTayQuyetDinhThanhLap::chuoiMucI(
                $validated['so_quyet_dinh'] ?? null,
                $validated['ngay_quyet_dinh'] ?? null,
            ),
            'SI_SO' => (string) count($hocVien),
            'GIAO_VIEN_LOP' => $tenGvcn,
            'PHUONG_THUC' => $phuongThuc,
        ];

        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', $validated['ma_gv']) ?: 'gvcn';
        $path = storage_path('app/tmp/so-tay-gv-lt-'.uniqid('', true).'.docx');
        $exporter->generate($fields, $hocVien, $path);
        $downloadName = 'so-tay-lt-'.$validated['ma_kh'].'-'.$safeMa.'.docx';

        return response()->download($path, $downloadName)->deleteFileAfterSend(true);
    }

    public function khoaJson(string $maKh): JsonResponse
    {
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa instanceof KhoaHoc) {
            return response()->json(['message' => 'Không tìm thấy khoá.'], 404);
        }

        $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);

        return response()->json([
            'ma_kh' => $khoa->MaKH,
            'ten_kh' => $khoa->TenKH,
            'ten_lop' => PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa),
            'hang_gplx' => $tuKhoa['hang_gplx'],
            'so_luong_hv' => SoTayGiaoVienHocVien::soLuong($maKh),
        ]);
    }
}
