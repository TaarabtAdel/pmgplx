<?php

namespace App\Support\DaoTao\LichThucHanh;

use Carbon\Carbon;

final class StageScheduler
{
    public function __construct(
        private readonly PhucTapBanDemRotator $phucTapRotator = new PhucTapBanDemRotator,
        private readonly BoSungScheduler $boSung = new BoSungScheduler,
    ) {}

    /**
     * @param  array<string, mixed>  $cauHinh
     * @param  list<array<string, mixed>>  $giaoViens
     * @param  array<int, list<string>>  $workDaysByCap
     * @param  array<string, array<string, float>>  $hoursLeft
     * @param  array<string, array<string, mixed>>  $cells
     */
    public function fill(
        array &$cells,
        array $cauHinh,
        array $giaoViens,
        array $workDaysByCap,
        array &$hoursLeft,
        array $mauCa,
        int $gioNgay,
        LichTimeline $timeline,
    ): void {
        $moc = LichMocLich::fromCauHinh($cauHinh);
        $hinhDau = array_flip($moc['hinh_dau']);
        $hinhCuoi = array_flip($moc['hinh_cuoi']);
        $onTap = $moc['on_tap'];
        $kt = $moc['kiem_tra'];
        $boSungDates = array_flip($this->boSung->fixedDates($cauHinh));

        $skipStages = ['hinh_dau', 'cabin', 'tu_dong', 'hinh_cuoi', 'on_stl', 'on_td', 'on_tap', 'kiem_tra', 'bo_sung'];

        foreach ($giaoViens as $gv) {
            $ma = $gv['ma_gv'];
            $cap = (int) $gv['cap_stt'];
            $days = $workDaysByCap[$cap] ?? [];
            $phucTapDays = [];

            foreach ($days as $iso) {
                $key = $iso.'|'.$ma;
                if (isset($cells[$key])) {
                    if (($cells[$key]['mau'] ?? '') === BaiGiang::PHUC_TAP || ($cells[$key]['mau'] ?? '') === BaiGiang::BAN_DEM) {
                        $phucTapDays[] = $iso;
                    }

                    continue;
                }

                if (isset($hinhDau[$iso])) {
                    $cells[$key] = $this->cellHinh($iso, $gv, $mauCa, $gioNgay);
                    $this->deduct($hoursLeft, $ma, 'hinh_dau', $gioNgay);

                    continue;
                }

                if (isset($boSungDates[$iso]) && ($hoursLeft[$ma]['bo_sung'] ?? 0) > 0) {
                    $meta = $this->boSung->metaForGv($gv, $iso, $cauHinh);
                    $cells[$key] = $this->cellBoSung($iso, $gv, $mauCa, $gioNgay, $meta['label'], $meta['bien_override']);
                    $this->deduct($hoursLeft, $ma, 'bo_sung', $gioNgay);

                    continue;
                }

                if (isset($hinhCuoi[$iso])) {
                    $cells[$key] = $this->cellHinh($iso, $gv, $mauCa, $gioNgay);
                    $this->deduct($hoursLeft, $ma, 'hinh_cuoi', $gioNgay);

                    continue;
                }

                if (isset($onTap[$iso])) {
                    $mauOn = $onTap[$iso];
                    $cells[$key] = $this->cellOnTap($iso, $gv, $mauCa, $gioNgay, $mauOn);
                    $this->deduct($hoursLeft, $ma, $mauOn === BaiGiang::ON_STL ? 'on_stl' : 'on_td', $gioNgay);

                    continue;
                }

                if ($iso === $kt) {
                    $cells[$key] = $this->cellKiemTra($iso, $gv);

                    continue;
                }

                if (($hoursLeft[$ma]['phuc_tap'] ?? 0) > 0) {
                    $phucTapDays[] = $iso;
                    $meta = $this->phucTapRotator->cellMeta($iso, $gv, $mauCa, $gioNgay, $timeline, $phucTapDays);
                    $cells[$key] = $this->cellFromMeta($iso, $gv, $meta['mau'], $this->labelPhucTap($meta), $meta['range'], $meta['so_gio'], $meta['noi_dung_phu']);
                    $this->deduct($hoursLeft, $ma, 'phuc_tap', $meta['so_gio']);

                    continue;
                }

                foreach (['doc_qc', 'cao_toc', 'co_tai'] as $stageMa) {
                    if (($hoursLeft[$ma][$stageMa] ?? 0) <= 0) {
                        continue;
                    }
                    $stage = $this->stageRow($cauHinh, $stageMa);
                    $cells[$key] = $this->cellFromStage($iso, $gv, $stage, $mauCa, $gioNgay, $timeline);
                    $deduct = $stageMa === 'cao_toc' ? min($gioNgay, $hoursLeft[$ma][$stageMa]) : $gioNgay;
                    $this->deduct($hoursLeft, $ma, $stageMa, $deduct);
                    break;
                }
            }
        }
    }

    /** @param array<string, array<string, float>> $hoursLeft */
    private function deduct(array &$hoursLeft, string $ma, string $stage, float $gio): void
    {
        if (! isset($hoursLeft[$ma][$stage])) {
            return;
        }
        $hoursLeft[$ma][$stage] = max(0, $hoursLeft[$ma][$stage] - $gio);
    }

    /** @param array<string, mixed> $cauHinh */
    private function stageRow(array $cauHinh, string $ma): array
    {
        foreach ($cauHinh['chuong_trinh'] ?? [] as $row) {
            if (($row['ma'] ?? '') === $ma) {
                return $row;
            }
        }

        return ['ma' => $ma, 'ten' => $ma, 'mau' => $ma];
    }

    /** @param array<string, mixed> $meta */
    private function labelPhucTap(array $meta): string
    {
        return ($meta['mau'] ?? '') === BaiGiang::BAN_DEM ? 'PHỨC TẠP' : 'PHỨC TẠP';
    }

    /** @param array<string, mixed> $gv */
    private function cellHinh(string $iso, array $gv, array $mauCa, int $gioNgay): array
    {
        $tpl = $mauCa['hinh'] ?? [];
        $range = $gv['ca'] === 'sang'
            ? ($tpl['sang'] ?? ['05:59', '13:59'])
            : ($tpl['chieu'] ?? ['14:00', '22:00']);

        return $this->cellFromMeta($iso, $gv, BaiGiang::HINH, 'HÌNH', $range, (float) $gioNgay, '');
    }

    /** @param array<string, mixed> $gv */
    private function cellBoSung(string $iso, array $gv, array $mauCa, int $gioNgay, string $label, ?string $bienOverride): array
    {
        $tpl = $mauCa['phuc_tap_2'] ?? $mauCa['phuc_tap_sau'] ?? [];
        $range = $gv['ca'] === 'sang'
            ? ($tpl['sang'] ?? ['05:59', '11:59'])
            : ($tpl['chieu'] ?? ['12:00', '18:00']);
        $cell = $this->cellFromMeta($iso, $gv, BaiGiang::BO_SUNG, $label, $range, (float) $gioNgay, '');
        if ($bienOverride) {
            $cell['bien_so_override'] = $bienOverride;
        }

        return $cell;
    }

    /** @param array<string, mixed> $gv */
    private function cellOnTap(string $iso, array $gv, array $mauCa, int $gioNgay, string $mau): array
    {
        $tpl = $mauCa['phuc_tap_2'] ?? [];
        $range = $gv['ca'] === 'sang'
            ? ($tpl['sang'] ?? ['05:59', '11:59'])
            : ($tpl['chieu'] ?? ['12:00', '18:00']);
        $label = $mau === BaiGiang::ON_STL ? 'ÔN LUYỆN STL' : 'ÔN LUYỆN TĐ';

        return $this->cellFromMeta($iso, $gv, $mau, $label, $range, (float) $gioNgay, '');
    }

    /** @param array<string, mixed> $gv */
    private function cellKiemTra(string $iso, array $gv): array
    {
        $day = Carbon::parse($iso);

        return [
            'thu' => LichNgay::thuLabel($day),
            'thoi_gian' => $day->format('Y-m-d').' 00:00:00',
            'so_gio' => 0,
            'bai' => 'KIỂM TRA',
            'noi_dung_phu' => '',
            'bat_dau' => '',
            'ket_thuc' => '',
            'mau' => BaiGiang::KIEM_TRA,
            'nghi_ca_khoa' => false,
            'ma_gv' => $gv['ma_gv'],
            'ho_ten' => $gv['ho_ten'],
            'bien_so' => $gv['bien_so'],
            'ma_khoa' => $gv['ma_khoa'],
            'cap_stt' => (int) ($gv['cap_stt'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $stage
     * @param array<string, mixed> $gv
     */
    private function cellFromStage(string $iso, array $gv, array $stage, array $mauCa, int $gioNgay, LichTimeline $timeline): array
    {
        $mauKey = (string) ($stage['mau'] ?? BaiGiang::PHUC_TAP);
        $label = strtoupper(str_replace('_', ' ', (string) ($stage['ten'] ?? $stage['ma'] ?? '')));
        $tplKey = $mauKey === BaiGiang::PHUC_TAP ? $timeline->mauPhucTapForDate($iso) : 'phuc_tap_2';
        $tpl = $mauCa[$tplKey] ?? $mauCa['phuc_tap_sau'] ?? [];
        $range = $gv['ca'] === 'sang'
            ? array_slice($tpl['sang'] ?? ['05:59', '11:59'], 0, 2)
            : ($tpl['chieu'] ?? ['12:00', '20:00']);

        return $this->cellFromMeta($iso, $gv, $mauKey, $label, $range, (float) $gioNgay, '');
    }

    /**
     * @param  array<string, mixed>  $gv
     * @param  list<string>  $range
     * @return array<string, mixed>
     */
    private function cellFromMeta(string $iso, array $gv, string $mau, string $bai, array $range, float $soGio, string $noiDungPhu): array
    {
        $day = Carbon::parse($iso);

        $batDau = MauCa::formatGioExcel($range[0] ?? '');
        $ketThuc = MauCa::formatGioExcel($range[count($range) - 1] ?? '');

        return [
            'thu' => LichNgay::thuLabel($day),
            'thoi_gian' => $day->format('Y-m-d').' 00:00:00',
            'so_gio' => $soGio,
            'bai' => $bai,
            'noi_dung_phu' => LichCellHienThi::resolve($batDau, $ketThuc, $noiDungPhu),
            'bat_dau' => $batDau,
            'ket_thuc' => $ketThuc,
            'mau' => $mau,
            'nghi_ca_khoa' => false,
            'ma_gv' => $gv['ma_gv'],
            'ho_ten' => $gv['ho_ten'],
            'bien_so' => $gv['bien_so'],
            'ma_khoa' => $gv['ma_khoa'],
            'cap_stt' => (int) ($gv['cap_stt'] ?? 0),
        ];
    }
}
