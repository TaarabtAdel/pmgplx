<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatPhienLichXeMatcher;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DoPhienVoiLichXeController extends Controller
{
    public function index(Request $request): View
    {
        $maKhoaHoc = trim((string) $request->input('ma_khoa_hoc', ''));
        $ketQua = trim((string) $request->input('ket_qua', ''));
        if (! in_array($ketQua, ['', 'hop_le', 'canh_bao'], true)) {
            $ketQua = '';
        }

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
            $sessions = DatDSPhien::query()
                ->where('MaKhoaHoc', $maKhoaHoc)
                ->orderByDesc('ThoiGianBatDauPhienHoc')
                ->orderByDesc('Id')
                ->get();

            $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc);
            $scheduleCount = $scheduleRows->count();

            $rows = $sessions->map(function (DatDSPhien $session) use ($scheduleRows): object {
                $result = DatPhienLichXeMatcher::evaluate($session, $scheduleRows);

                return (object) [
                    'session' => $session,
                    'valid' => $result['valid'],
                    'message' => $result['message'],
                    'matched' => $result['matched'],
                ];
            });

            $stats['total'] = $rows->count();
            $stats['hop_le'] = $rows->where('valid', true)->count();
            $stats['canh_bao'] = $stats['total'] - $stats['hop_le'];

            if ($ketQua === 'hop_le') {
                $rows = $rows->filter(fn (object $row): bool => $row->valid)->values();
            } elseif ($ketQua === 'canh_bao') {
                $rows = $rows->filter(fn (object $row): bool => ! $row->valid)->values();
            }

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
}
