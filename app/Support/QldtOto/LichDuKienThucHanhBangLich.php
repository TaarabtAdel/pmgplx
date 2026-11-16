<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\GiaoVien;
use App\Models\QldtOto\BaiDay;
use Carbon\Carbon;

/**
 * Bảng lịch thực hành đã sinh (dự kiến / chính thức) — hiển thị web và xuất Excel PMGPLX.
 *
 * @param  \App\Models\QldtOto\LichDuKienThucHanh|\App\Models\QldtOto\LichChinhThuc  $lich
 */
final class LichDuKienThucHanhBangLich
{
    /**
     * @param  \App\Models\QldtOto\LichDuKienThucHanh|\App\Models\QldtOto\LichChinhThuc  $lich
     * @return array{cot: list<array<string, mixed>>, dong: list<array<string, mixed>>, so_buoi: int}|null
     */
    public static function build($lich): ?array
    {
        $lich->loadMissing(['ngayNghis']);
        $buois = $lich->buois()->orderBy('Ngay')->orderBy('CapThuTu')->orderBy('Ca', 'desc')->get();
        if ($buois->isEmpty()) {
            return null;
        }

        $tenGv = GiaoVien::query()
            ->whereIn('MaGV', $buois->pluck('MaGv')->filter()->unique()->values()->all())
            ->get(['MaGV', 'HoTenDem', 'TenGV'])
            ->mapWithKeys(fn (GiaoVien $g): array => [trim((string) $g->MaGV) => $g->HoTen])
            ->all();

        $caps = $buois->groupBy('CapThuTu')->sortKeys();
        $cot = [];
        foreach ($caps as $capThuTu => $dsBuoi) {
            foreach (array_keys(BaiDay::CA) as $ca) {
                $buoi = $dsBuoi->firstWhere('Ca', $ca);
                $cot[] = [
                    'key' => $capThuTu.'|'.$ca,
                    'cap' => (int) $capThuTu,
                    'ca' => $ca,
                    'xe' => (string) $dsBuoi->first()->BienSoXe,
                    'ma_gv' => (string) $buoi?->MaGv,
                    'ten_gv' => $buoi ? ($tenGv[$buoi->MaGv] ?? $buoi->MaGv) : '',
                ];
            }
        }

        $theoNgay = $buois->groupBy(fn ($b) => $b->Ngay->format('Y-m-d'));
        $nghi = $lich->ngayNghis->mapWithKeys(fn ($n) => [$n->Ngay->format('Y-m-d') => (string) $n->GhiChu])->all();
        $thu = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $dong = [];
        $ngay = Carbon::parse($buois->first()->Ngay)->startOfDay();
        $cuoi = Carbon::parse($buois->max('Ngay'))->startOfDay();
        for (; $ngay->lte($cuoi); $ngay->addDay()) {
            $ymd = $ngay->format('Y-m-d');
            $dong[] = [
                'ngay' => $ngay->format('d/m/Y'),
                'thu' => $thu[$ngay->dayOfWeek],
                'nghi' => array_key_exists($ymd, $nghi),
                'ly_do_nghi' => $nghi[$ymd] ?? '',
                'o' => ($theoNgay[$ymd] ?? collect())->keyBy(fn ($b) => $b->CapThuTu.'|'.$b->Ca)->all(),
            ];
        }

        return ['cot' => $cot, 'dong' => $dong, 'so_buoi' => $buois->count()];
    }
}