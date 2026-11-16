<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;

/** Tuần và ký tự ô lịch toàn khóa (LT, HI, CB, TH, KT, DP). */
final class KeHoachDaoTaoLich
{
  /**
   * @param  array<string, Carbon|null>  $moc
   * @return list<array{tu: Carbon, den: Carbon}>
   */
    public static function danhSachTuan(Carbon $kg, Carbon $ket, int $soCot): array
    {
        $out = [];
        $cursor = $kg->copy()->startOfDay();
        $ket = $ket->copy()->startOfDay();

        for ($i = 0; $i < $soCot; $i++) {
            if ($cursor->gt($ket)) {
                break;
            }
            $tu = $cursor->copy();
            $den = $cursor->copy()->addDays(6);
            if ($den->gt($ket)) {
                $den = $ket->copy();
            }
            $out[] = ['tu' => $tu, 'den' => $den];
            $cursor->addDays(7);
        }

        return $out;
    }

    public static function ngayKetKhoa(array $moc): Carbon
    {
        $max = $moc['ngay_khai_giang']?->copy();
        foreach ($moc as $d) {
            if ($d instanceof Carbon && ($max === null || $d->gt($max))) {
                $max = $d->copy();
            }
        }

        return $max ?? Carbon::today();
    }

    public static function thoiGianThang(Carbon $tu, Carbon $den): string
    {
        $thang = (int) $tu->diffInDays($den) + 1;
        $soThang = round($thang / 30, 1);

        return str_replace('.', ',', (string) $soThang).' tháng';
    }

    /**
     * @param  array<string, Carbon|null>  $moc
     * @return list<array{lt: string, hi: string, cb: string, th: string, kt: string, dp: string}>
     */
    public static function kyHieuTheoTuan(array $moc, array $tuan): array
    {
        $out = [];
        foreach ($tuan as $w) {
            $ketLt = self::truMot($moc['ngay_bd_thuc_hanh']) ?? $moc['ngay_bd_thuc_hanh'];
            $out[] = [
                'lt' => self::kyHieu($w['tu'], $w['den'], $moc['ngay_khai_giang'], $ketLt),
                'hi' => self::kyHieu($w['tu'], $w['den'], $moc['ngay_bd_hinh_tren'], $moc['ngay_kt_hinh_tren'], 'HI'),
                'cb' => self::kyHieu($w['tu'], $w['den'], $moc['ngay_bd_cabin'], $moc['ngay_kt_cabin'], 'CB'),
                'th' => self::kyHieu($w['tu'], $w['den'], $moc['ngay_bd_dat'], $moc['ngay_kt_dat'], 'TH')
                    ?: self::kyHieu($w['tu'], $w['den'], $moc['ngay_bd_hinh_duoi'], $moc['ngay_kt_hinh_duoi'], 'TH'),
                'kt' => self::kyHieuNgay($w['tu'], $w['den'], $moc['ngay_kiem_tra'], 'KT'),
                'dp' => self::kyHieu($w['tu'], $w['den'], $moc['ngay_bd_on_luyen'], $moc['ngay_kt_on_luyen'], 'DP')
                    ?: self::kyHieu($w['tu'], $w['den'], $moc['ngay_bo_sung_dat'], $moc['ngay_kt_bo_sung_dat'], 'DP'),
            ];
        }

        return $out;
    }

    private static function truMot(?Carbon $d): ?Carbon
    {
        return $d?->copy()->subDay();
    }

    private static function kyHieu(Carbon $tuTuan, Carbon $denTuan, ?Carbon $bd, ?Carbon $kt, string $macDinh = 'LT'): string
    {
        if ($bd === null) {
            return '';
        }
        $kt ??= $bd;

        return self::chongNgay($tuTuan, $denTuan, $bd, $kt) ? $macDinh : '';
    }

    private static function kyHieuNgay(Carbon $tuTuan, Carbon $denTuan, ?Carbon $ngay, string $macDinh): string
    {
        if ($ngay === null) {
            return '';
        }

        return $ngay->between($tuTuan, $denTuan) ? $macDinh : '';
    }

    private static function chongNgay(Carbon $tuTuan, Carbon $denTuan, Carbon $bd, Carbon $kt): bool
    {
        return $tuTuan->lte($kt) && $denTuan->gte($bd);
    }

    public static function dinhDangDdMm(?Carbon $d): string
    {
        return $d ? $d->format('d/m') : '';
    }

    public static function dinhDangDdMmYyyy(?Carbon $d): string
    {
        return $d ? $d->format('d/m/Y') : '';
    }

    /**
     * @param  array<string, Carbon|null>  $moc
     */
    public static function chuoiThangTrenLuoi(array $tuan): string
    {
        if ($tuan === []) {
            return '';
        }
        $labels = [];
        $prev = '';
        foreach ($tuan as $w) {
            $l = 'T'.(int) $w['tu']->format('n').'/'.$w['tu']->format('Y');
            if ($l !== $prev) {
                $labels[] = $l;
                $prev = $l;
            }
        }

        return implode(', ', $labels);
    }
}