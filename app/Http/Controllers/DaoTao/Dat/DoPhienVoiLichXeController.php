<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatPhienLichXeExcelExporter;
use App\Support\DaoTao\DatPhienLichXeMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoPhienVoiLichXeController extends Controller
{
    public function index(Request $request): View
    {
        $parsed = $this->parseFilters($request);
        $maKhoaHoc = $parsed['ma_khoa_hoc'];
        $ketQua = $parsed['ket_qua'];

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $items = null;
        $scheduleCount = 0;
        $stats = ['total' => 0, 'hop_le' => 0, 'canh_bao' => 0];

        if ($maKhoaHoc !== '') {
            $built = $this->buildRows($maKhoaHoc, $ketQua);
            $rows = $built['rows'];
            $stats = $built['stats'];
            $scheduleCount = $built['scheduleCount'];

            $perPage = 50;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageItems = $rows->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $items = new LengthAwarePaginator(
                $pageItems,
                $rows->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('DaoTao.dat.do-phien-lich-xe', [
            'items' => $items,
            'filters' => [
                'ma_khoa_hoc' => $maKhoaHoc,
                'ket_qua' => $ketQua,
            ],
            'khoaHocOptions' => $khoaHocOptions,
            'scheduleCount' => $scheduleCount,
            'stats' => $stats,
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $parsed = $this->parseFilters($request);
        $maKhoaHoc = $parsed['ma_khoa_hoc'];

        if ($maKhoaHoc === '') {
            return redirect()
                ->route('daotao.pdt.dat.do-phien-lich-xe')
                ->with('error', 'Chọn mã khóa học trước khi xuất Excel.');
        }

        $built = $this->buildRows($maKhoaHoc, $parsed['ket_qua']);

        return DatPhienLichXeExcelExporter::download($built['rows'], $maKhoaHoc);
    }

    /**
     * @return array{ma_khoa_hoc: string, ket_qua: string}
     */
    private function parseFilters(Request $request): array
    {
        $maKhoaHoc = trim((string) $request->input('ma_khoa_hoc', ''));
        $ketQua = trim((string) $request->input('ket_qua', ''));
        if (! in_array($ketQua, ['', 'hop_le', 'canh_bao'], true)) {
            $ketQua = '';
        }

        return [
            'ma_khoa_hoc' => $maKhoaHoc,
            'ket_qua' => $ketQua,
        ];
    }

    /**
     * @return array{
     *     rows: Collection<int, object>,
     *     stats: array{total: int, hop_le: int, canh_bao: int},
     *     scheduleCount: int
     * }
     */
    private function buildRows(string $maKhoaHoc, string $ketQua): array
    {
        $sessions = DatDSPhien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderByDesc('ThoiGianBatDauPhienHoc')
            ->orderByDesc('Id')
            ->get();

        $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc);

        $rows = $sessions->map(function (DatDSPhien $session) use ($scheduleRows): object {
            $result = DatPhienLichXeMatcher::evaluate($session, $scheduleRows);

            return (object) [
                'session' => $session,
                'valid' => $result['valid'],
                'message' => $result['message'],
                'matched' => $result['matched'],
                'displaySchedule' => $result['displaySchedule'],
            ];
        });

        $stats = [
            'total' => $rows->count(),
            'hop_le' => $rows->where('valid', true)->count(),
            'canh_bao' => $rows->count() - $rows->where('valid', true)->count(),
        ];

        if ($ketQua === 'hop_le') {
            $rows = $rows->filter(fn (object $row): bool => $row->valid)->values();
        } elseif ($ketQua === 'canh_bao') {
            $rows = $rows->filter(fn (object $row): bool => ! $row->valid)->values();
        }

        return [
            'rows' => $rows,
            'stats' => $stats,
            'scheduleCount' => $scheduleRows->count(),
        ];
    }
}
