<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\DaoTao\DatKhoaHocSelectOptions;
use App\Support\DaoTao\PhuLuc5DatDemHocVien;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhuLuc5DatCabinDocx;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatCabinPhuLuc5Controller extends Controller
{
    public const SLUG = '4-dat-cabin';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKhoa = trim((string) $request->query('ma_khoa_hoc', ''));
        $tuNgay = trim((string) $request->query('tu_ngay', ''));
        $denNgay = trim((string) $request->query('den_ngay', ''));

        $dem = null;
        if ($maKhoa !== '') {
            $dem = PhuLuc5DatDemHocVien::demTheoHang([
                'ma_khoa_hoc' => $maKhoa,
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
            ]);
        }

        return view('QldtOto.dat-cabin-phu-luc-5', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaHocOptions' => DatKhoaHocSelectOptions::fromDatDSPhien(),
            'ma_khoa_hoc' => $maKhoa,
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'dem' => $dem,
            'linkTongHop' => route('daotao.pdt.dat.tong-hop-hoc-vien', array_filter([
                'ma_khoa_hoc' => $maKhoa,
                'tu_ngay' => $tuNgay,
                'den_ngay' => $denNgay,
                'dat_ct' => 'dat',
            ])),
        ]);
    }

    public function thongKe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
        ]);

        return response()->json(
            PhuLuc5DatDemHocVien::demTheoHang($validated)
        );
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'tu_ngay' => ['required', 'date'],
            'den_ngay' => ['required', 'date'],
        ]);

        $dem = PhuLuc5DatDemHocVien::demTheoHang($validated);
        $out = sys_get_temp_dir().'/'.uniqid('phu-luc-5-', true).'.docx';

        try {
            PhuLuc5DatCabinDocx::generate(
                $validated['ma_khoa_hoc'],
                Carbon::parse($validated['tu_ngay']),
                Carbon::parse($validated['den_ngay']),
                $dem,
                $out
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $tenFile = PhuLuc5DatCabinDocx::tenFile($validated['ma_khoa_hoc']);

        return response()->download($out, $tenFile)->deleteFileAfterSend(true);
    }
}