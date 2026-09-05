<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachDatDSPhienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'ma_hoc_vien' => trim((string) $request->input('ma_hoc_vien', '')),
            'ma_khoa_hoc' => trim((string) $request->input('ma_khoa_hoc', '')),
            'ma_giao_vien' => trim((string) $request->input('ma_giao_vien', '')),
            'tu_ngay' => trim((string) $request->input('tu_ngay', '')),
            'den_ngay' => trim((string) $request->input('den_ngay', '')),
        ];

        $query = DatDSPhien::query()->orderByDesc('ThoiGianBatDauPhienHoc')->orderByDesc('Id');

        if ($filters['ma_hoc_vien'] !== '') {
            $query->where('MaHocVien', 'like', '%'.$filters['ma_hoc_vien'].'%');
        }

        if ($filters['ma_khoa_hoc'] !== '') {
            $query->where('MaKhoaHoc', 'like', '%'.$filters['ma_khoa_hoc'].'%');
        }

        if ($filters['ma_giao_vien'] !== '') {
            $query->where('MaGiaoVien', 'like', '%'.$filters['ma_giao_vien'].'%');
        }

        if ($filters['tu_ngay'] !== '') {
            $query->whereDate('ThoiGianBatDauPhienHoc', '>=', $filters['tu_ngay']);
        }

        if ($filters['den_ngay'] !== '') {
            $query->whereDate('ThoiGianBatDauPhienHoc', '<=', $filters['den_ngay']);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q): void {
                $sub->where('MaPhienHoc', 'like', '%'.$q.'%')
                    ->orWhere('HoTenHocVien', 'like', '%'.$q.'%')
                    ->orWhere('HoTenGiaoVien', 'like', '%'.$q.'%')
                    ->orWhere('BienSoXe', 'like', '%'.$q.'%')
                    ->orWhere('MaThietBi', 'like', '%'.$q.'%');
            });
        }

        $items = $query->paginate(50)->withQueryString();

        return view('DaoTao.dat.danh-sach', [
            'items' => $items,
            'filters' => $filters,
        ]);
    }
}
