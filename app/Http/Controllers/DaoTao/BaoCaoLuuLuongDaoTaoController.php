<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Models\PMGPLX\NguoiLXHoSo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BaoCaoLuuLuongDaoTaoController extends Controller
{
    /** Hạn mức từ thành lập đến hết 26/07/2026 */
    private const HAN_MUC_GIAI_DOAN_1 = 499;

    /** Hạn mức từ 27/07/2026 trở đi */
    private const HAN_MUC_GIAI_DOAN_2 = 1000;

    private const MOC_HAN_MUC = '2026-07-27';

    public function index(Request $request): View
    {
        $filters = [
            'ngay_kiem_tra' => (string) ($request->input('ngay_kiem_tra') ?? now()->format('Y-m-d')),
            'gio' => (string) ($request->input('gio') ?? now()->format('H:i')),
            'hang_gplx' => trim((string) ($request->input('hang_gplx') ?? '')),
        ];

        $searched = ! $request->boolean('lam_moi');

        $summary = null;
        /** @var list<array{hang: string, so_luong: int}> */
        $items = [];
        /** @var list<array{ma_kh: string, ten_kh: string, ngay_bat_dau: string, ngay_ket_thuc: string, so_hoc_vien: int, hang_hoc: string}> */
        $khoaHocs = [];

        if ($searched) {
            $at = Carbon::parse($filters['ngay_kiem_tra'].' '.$filters['gio']);
            $activeMaKhs = $this->activeCourseIds($at);
            $items = $this->countByHang($activeMaKhs, $filters['hang_gplx']);
            $khoaHocs = $this->matchingCourses($at, $activeMaKhs, $filters['hang_gplx']);
            $tongSo = array_sum(array_column($items, 'so_luong'));
            $hanMuc = $this->hanMuc($at);

            $summary = [
                'tong_so' => $tongSo,
                'han_muc' => $hanMuc,
                'trong_han_muc' => $tongSo <= $hanMuc,
                'ngay_hien_thi' => $at->format('d/m/Y'),
                'gio_hien_thi' => $at->format('H:i'),
                'so_khoa_hoc' => count($khoaHocs),
                'han_muc_mo_ta' => $this->hanMucMoTa($at),
            ];
        }

        return view('DaoTao.bao-cao.luu-luong-dao-tao', [
            'filters' => $filters,
            'hangOptions' => $this->hangOptions(),
            'searched' => $searched,
            'summary' => $summary,
            'items' => $items,
            'khoaHocs' => $khoaHocs,
        ]);
    }

    private function hanMuc(Carbon $at): int
    {
        $moc = Carbon::parse(self::MOC_HAN_MUC)->startOfDay();

        return $at->lt($moc) ? self::HAN_MUC_GIAI_DOAN_1 : self::HAN_MUC_GIAI_DOAN_2;
    }

    private function hanMucMoTa(Carbon $at): string
    {
        if ($at->lt(Carbon::parse(self::MOC_HAN_MUC)->startOfDay())) {
            return 'Hạn mức giai đoạn 1: '.number_format(self::HAN_MUC_GIAI_DOAN_1).' (đến 26/07/2026)';
        }

        return 'Hạn mức giai đoạn 2: '.number_format(self::HAN_MUC_GIAI_DOAN_2).' (từ 27/07/2026)';
    }

    /**
     * @return list<string>
     */
    private function hangOptions(): array
    {
        return NguoiLXHoSo::query()
            ->whereNotNull('HangGPLX')
            ->where('HangGPLX', '<>', '')
            ->distinct()
            ->orderBy('HangGPLX')
            ->pluck('HangGPLX')
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    private function activeCourseIds(Carbon $at): Collection
    {
        return KhoaHoc::query()
            ->activeAt($at)
            ->pluck('MaKH');
    }

    /**
     * @param  Collection<int, string>  $maKhs
     * @return list<array{hang: string, so_luong: int}>
     */
    private function countByHang(Collection $maKhs, string $hangFilter = ''): array
    {
        if ($maKhs->isEmpty()) {
            return [];
        }

        $query = $this->studentQuery($maKhs, $hangFilter);

        $rows = $query
            ->select([
                'h.HangGPLX as hang',
                DB::raw('COUNT(*) as so_luong'),
            ])
            ->groupBy('h.HangGPLX')
            ->orderBy('h.HangGPLX')
            ->get();

        return $rows->map(function ($row): array {
            return [
                'hang' => trim((string) $row->hang) !== '' ? (string) $row->hang : '—',
                'so_luong' => (int) $row->so_luong,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, string>  $maKhs
     * @return list<array{ma_kh: string, ten_kh: string, ngay_bat_dau: string, ngay_ket_thuc: string, so_hoc_vien: int, hang_hoc: string}>
     */
    private function matchingCourses(Carbon $at, Collection $maKhs, string $hangFilter = ''): array
    {
        if ($maKhs->isEmpty()) {
            return [];
        }

        $counts = $this->studentQuery($maKhs, $hangFilter)
            ->select([
                'h.MaKhoaHoc',
                DB::raw('COUNT(*) as so_luong'),
            ])
            ->groupBy('h.MaKhoaHoc')
            ->pluck('so_luong', 'MaKhoaHoc');

        return KhoaHoc::query()
            ->whereIn('MaKH', $maKhs)
            ->orderBy('NgayKG')
            ->orderBy('MaKH')
            ->get(['MaKH', 'TenKH', 'NgayKG', 'NgayBG', 'HangGPLX', 'HangDT'])
            ->map(function (KhoaHoc $kh) use ($counts): array {
                $hang = trim((string) ($kh->HangGPLX ?: $kh->HangDT));

                return [
                    'ma_kh' => (string) $kh->MaKH,
                    'ten_kh' => trim((string) $kh->TenKH) !== '' ? (string) $kh->TenKH : (string) $kh->MaKH,
                    'ngay_bat_dau' => optional($kh->NgayKG)->format('d/m/Y') ?? '—',
                    'ngay_ket_thuc' => optional($kh->NgayBG)->format('d/m/Y') ?? '—',
                    'so_hoc_vien' => (int) ($counts[$kh->MaKH] ?? 0),
                    'hang_hoc' => $hang !== '' ? $hang : '—',
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, string>  $maKhs
     */
    private function studentQuery(Collection $maKhs, string $hangFilter = '')
    {
        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->whereIn('h.MaKhoaHoc', $maKhs);

        if ($hang = trim($hangFilter)) {
            $query->where('h.HangGPLX', $hang);
        }

        return $query;
    }
}
