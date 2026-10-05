<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DaoTao\Concerns\BuildsPhanCongTuLichFilters;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use App\Support\DaoTao\PhanCongTuLichTongHopExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $allRows = $this->aggregateRows($queryFilters);

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
            'lichXeUrl' => route('pmgplx.lich.xe.index', array_filter([
                'ma_kh' => $filters['ma_kh'] ?? '',
                'ma_gv' => $filters['ma_gv'] ?? '',
                'bien_so_xe' => $filters['bien_so_xe'] ?? '',
            ])),
            'exportUrl' => route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop.export', $listQueryParams),
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->redirectTenKhoaToMaKh($request, 'daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop.export')) {
            return $redirect;
        }

        $sets = $this->phanCongTuLichFilterSets($request, includeLoai: false);

        return PhanCongTuLichTongHopExcelExporter::download(
            $this->aggregateRows($sets['queryFilters']),
            $sets['filters']
        );
    }

    /**
     * @param  array<string, mixed>  $queryFilters
     * @return list<array<string, mixed>>
     */
    private function aggregateRows(array $queryFilters): array
    {
        return (new PhanCongTuLichPmgplxQuery())->aggregateByKhoaXe($queryFilters);
    }
}
