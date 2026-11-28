<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatKhoaHocSelectOptions;
use App\Support\DaoTao\TongDatKhoaDuLieu;
use App\Support\MenuQldt;
use App\Support\QldtOto\TongDatKhoaExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatCabinTongDatController extends Controller
{
    public const SLUG = '4-dat-cabin-tong-dat';

    public function index(): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        return view('QldtOto.dat-cabin-tong-dat', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaHocOptions' => DatKhoaHocSelectOptions::fromDatDSPhien(),
            'ngay_bao_cao' => old('ngay_bao_cao', now()->format('Y-m-d')),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ngay_bao_cao' => ['required', 'date'],
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
        ]);

        $filters = DatDSPhienBoLoc::parseFilters(new Request([
            'ma_khoa_hoc' => $validated['ma_khoa_hoc'],
            'tu_ngay' => $validated['tu_ngay'] ?? '',
            'den_ngay' => $validated['den_ngay'] ?? '',
        ]));

        try {
            $tomTat = TongDatKhoaDuLieu::tomTatKhoa($filters, $validated['ma_khoa_hoc']);
            if (($tomTat['hoc_vien'] ?? []) === []) {
                throw new \InvalidArgumentException('Không có phiên DAT cho khóa đã chọn.');
            }

            $out = sys_get_temp_dir().'/'.uniqid('tong-dat-', true).'.xlsx';
            TongDatKhoaExcel::taiVe($tomTat, $validated['ngay_bao_cao'], $out);

            return response()->download($out, TongDatKhoaExcel::tenFile($validated['ma_khoa_hoc']))->deleteFileAfterSend(true);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}