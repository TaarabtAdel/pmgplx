<?php

namespace App\Http\Controllers\PMGPLXOLD;

use App\Http\Controllers\Controller;
use App\Models\PMGPLXOLD\KhoaHoc;
use App\Support\PMGPLX\DanhMucBangExcelExporter;
use App\Support\PMGPLX\DanhSachHocVienExcelRows;
use App\Support\PMGPLX\DanhSachHocVienExportColumns;
use App\Support\PMGPLX\DanhSachHocVienFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DanhSachHocVienController extends Controller
{
    private const DB_CONNECTION = DanhSachHocVienFilter::CONNECTION_CU;

    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $items = DanhSachHocVienFilter::query($request, banCu: true)
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK')
            ->paginate($perPage)
            ->withQueryString();

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        return view('PMGPLXOLD.danh-muc.hoc-vien', [
            'items' => $items,
            'khoaHocs' => $khoaHocs,
            'exportColumns' => DanhSachHocVienExportColumns::forModal(self::DB_CONNECTION),
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'ma_kh' => $request->input('ma_kh', ''),
                'hang_gplx' => $request->input('hang_gplx', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $keys = DanhSachHocVienExportColumns::sanitize($request->input('cols'), self::DB_CONNECTION);
        $hasRaw = (bool) array_filter($keys, fn (string $k) => str_contains($k, '.'));

        $query = DanhSachHocVienFilter::query($request, banCu: true);
        if ($hasRaw) {
            [, $selects] = DanhSachHocVienFilter::exportColumnSelects(self::DB_CONNECTION);
            $query = DanhSachHocVienFilter::query($request, banCu: true, select: $selects);
        }

        $rawRows = $query
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK')
            ->get();

        $labels = DanhSachHocVienExportColumns::labelsFor($keys, self::DB_CONNECTION);
        $built = DanhSachHocVienExcelRows::build($keys, $rawRows);
        $rows = array_map(function (array $row) use ($keys, $labels): array {
            $out = [];
            foreach ($keys as $i => $key) {
                $out[$labels[$i]] = $row[$key] ?? null;
            }

            return $out;
        }, $built);

        $title = 'Danh mục học viên (bản cũ)';
        if ($maKh = trim((string) $request->input('ma_kh'))) {
            $title .= ' · khóa '.$maKh;
        }

        return DanhMucBangExcelExporter::downloadFromArrays(
            $title,
            'danh-muc-hoc-vien-ban-cu',
            $labels,
            $rows,
        );
    }
}
