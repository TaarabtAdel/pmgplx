<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\GiaoVien;
use App\Models\DaoTao\KhoaDaoTao;
use App\Models\DaoTao\PhanCongDaoTao;
use App\Models\DaoTao\XeTapLai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachPhanCongDaoTaoController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'ten_khoa' => trim((string) ($request->input('ten_khoa') ?? '')),
            'ho_ten' => trim((string) ($request->input('ho_ten') ?? '')),
            'bien_so' => trim((string) ($request->input('bien_so') ?? '')),
        ];

        $query = PhanCongDaoTao::query()->with(['giaoVien', 'xeTapLai', 'khoaDaoTao']);

        if ($filters['ten_khoa'] !== '') {
            $khoaId = KhoaDaoTao::query()->where('TenKhoa', $filters['ten_khoa'])->value('Id');
            $query->where('KhoaDaoTaoId', $khoaId ?? 0);
        }

        if ($filters['ho_ten'] !== '') {
            $gvIds = GiaoVien::query()
                ->where('HoTen', 'like', '%'.$filters['ho_ten'].'%')
                ->pluck('Id');
            $query->whereIn('GiaoVienId', $gvIds->isEmpty() ? [-1] : $gvIds);
        }

        if ($filters['bien_so'] !== '') {
            $xeIds = XeTapLai::query()
                ->where('BienSo', 'like', '%'.$filters['bien_so'].'%')
                ->pluck('Id');
            $query->whereIn('XeTapLaiId', $xeIds->isEmpty() ? [-1] : $xeIds);
        }

        $items = $query
            ->orderBy('KhoaDaoTaoId')
            ->orderBy('SoTT')
            ->orderBy('TuNgay')
            ->paginate(50)
            ->withQueryString();

        return view('DaoTao.phan-cong-dao-tao.danh-sach', [
            'filters' => $filters,
            'items' => $items,
            'khoaOptions' => KhoaDaoTao::query()->orderBy('TenKhoa')->pluck('TenKhoa'),
        ]);
    }
}
