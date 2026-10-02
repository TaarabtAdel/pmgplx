<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DaoTao\Concerns\BuildsPhanCongTuLichFilters;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DanhSachPhanCongTuLichTongHopController extends Controller
{
    use BuildsPhanCongTuLichFilters;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectTenKhoaToMaKh($request, 'daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop')) {
            return $redirect;
        }

        $sets = $this->phanCongTuLichFilterSets($request, includeLoai: false);
        $filters = $sets['filters'];
        $queryFilters = $sets['queryFilters'];
        $listQueryParams = $sets['listQueryParams'];

        $allRows = (new PhanCongTuLichPmgplxQuery())->aggregateByKhoaXe($queryFilters);

        $perPage = 50;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = collect($allRows)->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $items = new LengthAwarePaginator(
            $pageItems,
            count($allRows),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('DaoTao.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop', [
            'filters' => $filters,
            'items' => $items,
            'khoaHocs' => PhanCongTuLichPmgplxQuery::filterKhoaHocOptions(),
            'giaoViens' => PhanCongTuLichPmgplxQuery::filterGiaoVienOptions(),
            'xeTaps' => PhanCongTuLichPmgplxQuery::filterBienSoXeOptions(),
            'listQueryParams' => $listQueryParams,
            'detailUrl' => route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich', $listQueryParams),
        ]);
    }
}
