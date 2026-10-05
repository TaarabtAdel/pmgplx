<?php

namespace App\Support\DaoTao\LichThucHanh;

use Carbon\Carbon;

final class LichGenerator
{
    /**
     * @param  array<string, mixed>  $cauHinh
     * @param  list<string>  $giuOChinhTay
     * @return array{lich: array<string, mixed>, tom_tat: array<string, mixed>}
     */
    public function generate(array $cauHinh, array $giuOChinhTay = []): array
    {
        $ngayKg = LichNgay::normalizeDate($cauHinh['ngay_khai_giang'] ?? '');
        if ($ngayKg === '') {
            throw new \InvalidArgumentException('Thiếu ngày khai giảng.');
        }

        $gioNgay = (int) ($cauHinh['gio_day_moi_ngay'] ?? 8);
        $mauCa = ($cauHinh['mau_ca'] ?? MauCa::defaults())[(string) $gioNgay] ?? MauCa::khuon8h();

        $giaoViens = $this->flattenGiaoVien($cauHinh);
        if ($giaoViens === []) {
            throw new \InvalidArgumentException('Chưa khai báo cặp xe / giáo viên.');
        }

        if ($this->coKeHoachImport($cauHinh)) {
            return $this->generateFromImportedKeHoach($cauHinh, $giaoViens, $gioNgay);
        }

        return $this->generateTuThuTuc($cauHinh, $giaoViens, $gioNgay, $mauCa);
    }

    /** @param array<string, mixed> $cauHinh */
    private function coKeHoachImport(array $cauHinh): bool
    {
        $ke = $cauHinh['ke_hoach_theo_gv'] ?? [];

        return is_array($ke) && $ke !== [];
    }

    /** @param array<string, mixed> $cauHinh */
    private function ngayKetThucMeta(array $cauHinh): string
    {
        $moc = LichMocLich::fromCauHinh($cauHinh);
        if ($moc['kiem_tra'] !== '') {
            return $moc['kiem_tra'];
        }

        return LichNgay::normalizeDate($cauHinh['ngay_ket_thuc_du_kien'] ?? $cauHinh['lich_den_ngay'] ?? '');
    }

    /**
     * Sinh lịch từ kế hoạch đã import (Excel hoặc JSON) — áp dụng mọi khóa, không chỉ BK54.
     *
     * @param  array<string, mixed>  $cauHinh
     * @param  list<array<string, mixed>>  $giaoViens
     * @return array{lich: array<string, mixed>, tom_tat: array<string, mixed>}
     */
    private function generateFromImportedKeHoach(array $cauHinh, array $giaoViens, int $gioNgay): array
    {
        /** @var array<string, array<string, array<string, mixed>>> $keHoach */
        $keHoach = $cauHinh['ke_hoach_theo_gv'] ?? [];
        $timeline = new LichTimeline($cauHinh);
        $dates = $timeline->calendarDates();
        $cells = [];
        $ngayNghiDong = [];

        foreach ($giaoViens as $gv) {
            $ma = $gv['ma_gv'];
            foreach ($keHoach[$ma] ?? [] as $iso => $slot) {
                if (! is_array($slot)) {
                    continue;
                }
                $mau = (string) ($slot['mau'] ?? '');
                $bai = (string) ($slot['bai'] ?? '');
                $soGio = (float) ($slot['so_gio'] ?? 0);
                $bd = (string) ($slot['bat_dau'] ?? '');
                $kt = (string) ($slot['ket_thuc'] ?? '');
                $cells[$iso.'|'.$ma] = $this->cellFromKeHoachSlot($iso, $gv, $mau, $bai, $soGio, $bd, $kt, (string) ($slot['noi_dung_phu'] ?? ''));
            }
        }

        foreach ($dates as $iso) {
            $day = Carbon::parse($iso);
            if (LichNgay::laNgayNghi($day, $cauHinh)) {
                $ngayNghiDong[] = $iso;
                foreach ($giaoViens as $gv) {
                    $key = $iso.'|'.$gv['ma_gv'];
                    if (! isset($cells[$key])) {
                        $cells[$key] = $this->emptyCell($iso, $gv, true);
                    }
                }
            }
        }

        $lastDate = $this->ngayKetThucMeta($cauHinh);
        if ($lastDate === '') {
            $lastDate = $this->detectLastWorkingDate($cells, $giaoViens) ?? '';
        }

        $lich = [
            'dates' => $dates,
            'giao_viens' => $giaoViens,
            'cells' => $cells,
            'ngay_nghi_ca_dong' => array_values(array_unique($ngayNghiDong)),
            'meta' => [
                'ngay_ket_thuc_tinh' => $lastDate,
                'gio_moi_ngay' => $gioNgay,
            ],
        ];

        return ['lich' => $lich, 'tom_tat' => $this->buildTomTat($lich, $cauHinh)];
    }
    private function generateTuThuTuc(array $cauHinh, array $giaoViens, int $gioNgay, array $mauCa): array
    {
        $chuongTrinh = $this->sortedChuongTrinh($cauHinh);
        $timeline = new LichTimeline($cauHinh);
        $dates = $timeline->calendarDates();
        $moc = LichMocLich::fromCauHinh($cauHinh);

        $cells = [];
        $ngayNghiDong = [];

        /** @var array<int, list<string>> $workDaysByCap */
        $workDaysByCap = [];
        foreach ($cauHinh['cap_xe'] ?? [] as $cap) {
            if (! is_array($cap)) {
                continue;
            }
            $stt = (int) ($cap['stt'] ?? 0);
            $workDaysByCap[$stt] = $timeline->workDaysForCap($dates, $stt);
        }

        $hoursLeft = $this->initialHoursLeft($giaoViens, $chuongTrinh, $cauHinh);

        $cabinQueue = new CabinQueue;
        $autoQueue = new AutoQueue;
        $this->scheduleCabinByDates($cells, $cauHinh, $giaoViens, $cabinQueue->datesByCap($workDaysByCap, $moc['cabin_neo']), $hoursLeft, $mauCa);
        $this->scheduleAutoByBlocks($cells, $cauHinh, $giaoViens, $autoQueue->blocksByCap($workDaysByCap, $moc['auto_neo']), $hoursLeft, $mauCa, $cauHinh['hang_dao_tao'] ?? 'B');

        (new StageScheduler)->fill($cells, $cauHinh, $giaoViens, $workDaysByCap, $hoursLeft, $mauCa, $gioNgay, $timeline);

        $this->applyMauKeHoachCa($cells, $cauHinh, $giaoViens, 'sang');
        $this->applyMauKeHoachCa($cells, $cauHinh, $giaoViens, 'chieu');

        foreach ($dates as $iso) {
            $day = Carbon::parse($iso);
            if (LichNgay::laNgayNghi($day, $cauHinh)) {
                $ngayNghiDong[] = $iso;
                foreach ($giaoViens as $gv) {
                    $key = $iso.'|'.$gv['ma_gv'];
                    if (! isset($cells[$key])) {
                        $cells[$key] = $this->emptyCell($iso, $gv, true);
                    }
                }
            }
        }

        $lastDate = $this->detectLastWorkingDate($cells, $giaoViens);
        $metaKt = $this->ngayKetThucMeta($cauHinh);
        if ($metaKt !== '') {
            $lastDate = $metaKt;
        }

        $lich = [
            'dates' => $dates,
            'giao_viens' => $giaoViens,
            'cells' => $cells,
            'ngay_nghi_ca_dong' => array_values(array_unique($ngayNghiDong)),
            'meta' => [
                'ngay_ket_thuc_tinh' => $lastDate,
                'gio_moi_ngay' => $gioNgay,
            ],
        ];

        return ['lich' => $lich, 'tom_tat' => $this->buildTomTat($lich, $cauHinh)];
    }
    /** @param array<string, mixed> $gv */
    private function cellFromKeHoachSlot(
        string $iso,
        array $gv,
        string $mau,
        string $bai,
        float $soGio,
        string $bd,
        string $kt,
        string $noiDungPhu
    ): array {
        $day = Carbon::parse($iso);
        $batDau = $bd !== '' ? (str_contains($bd, 'H') ? $bd : MauCa::formatGioExcel($bd)) : '';
        $ketThuc = $kt !== '' ? (str_contains($kt, 'H') ? $kt : MauCa::formatGioExcel($kt)) : '';
        $noiDungPhu = LichCellHienThi::resolve($batDau, $ketThuc, $noiDungPhu);

        return [
            'thu' => LichNgay::thuLabel($day),
            'thoi_gian' => $day->format('Y-m-d').' 00:00:00',
            'so_gio' => $soGio,
            'bai' => $bai,
            'noi_dung_phu' => $noiDungPhu,
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

    /** @param array<string, mixed> $cauHinh
     * @return list<array<string, mixed>>
     */
    private function flattenGiaoVien(array $cauHinh): array
    {
        $maKhoa = strtoupper(trim((string) ($cauHinh['ma_khoa'] ?? '')));
        $out = [];
        foreach ($cauHinh['cap_xe'] ?? [] as $cap) {
            if (! is_array($cap)) {
                continue;
            }
            $bien = trim((string) ($cap['bien_so'] ?? ''));
            $stt = (int) ($cap['stt'] ?? 0);
            foreach (['sang' => 'gv_sang', 'chieu' => 'gv_chieu'] as $ca => $field) {
                $ma = trim((string) ($cap[$field] ?? ''));
                if ($ma === '') {
                    continue;
                }
                $out[] = [
                    'ma_gv' => $ma,
                    'ho_ten' => trim((string) ($cap['ten_'.$field] ?? $ma)),
                    'ca' => $ca,
                    'bien_so' => $bien,
                    'ma_khoa' => $maKhoa,
                    'cap_stt' => $stt,
                ];
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $cauHinh
     * @return list<array<string, mixed>>
     */
    private function sortedChuongTrinh(array $cauHinh): array
    {
        $rows = $cauHinh['chuong_trinh'] ?? CauHinhDefaults::chuongTrinhHangB();
        usort($rows, fn ($a, $b) => ((int) ($a['thu_tu'] ?? 0)) <=> ((int) ($b['thu_tu'] ?? 0)));

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $giaoViens
     * @param  list<array<string, mixed>>  $chuongTrinh
     * @return array<string, array<string, float>>
     */
    private function initialHoursLeft(array $giaoViens, array $chuongTrinh, array $cauHinh): array
    {
        $left = [];
        foreach ($giaoViens as $gv) {
            $ma = $gv['ma_gv'];
            $left[$ma] = [];
            foreach ($chuongTrinh as $stage) {
                $left[$ma][(string) ($stage['ma'] ?? '')] = (float) ($stage['gio'] ?? 0);
            }
            if ($this->onTapHaiNgay($chuongTrinh)) {
                $left[$ma]['on_stl'] = 8.0;
                $left[$ma]['on_td'] = 8.0;
            }
        }

        return $left;
    }

    /** @param list<array<string, mixed>> $chuongTrinh */
    private function onTapHaiNgay(array $chuongTrinh): bool
    {
        $hasStl = false;
        $hasTd = false;
        foreach ($chuongTrinh as $row) {
            if (($row['ma'] ?? '') === 'on_stl') {
                $hasStl = true;
            }
            if (($row['ma'] ?? '') === 'on_td') {
                $hasTd = true;
            }
        }

        return $hasStl && $hasTd;
    }

    /** @param array<string, array<string, mixed>> $cells */
    private function scheduleCabinByDates(
        array &$cells,
        array $cauHinh,
        array $giaoViens,
        array $datesByCap,
        array &$hoursLeft,
        array $mauCa
    ): void {
        foreach ($datesByCap as $stt => $iso) {
            if ($iso === '') {
                continue;
            }
            foreach ($giaoViens as $gv) {
                if ((int) $gv['cap_stt'] !== (int) $stt || ($gv['ca'] ?? '') !== 'sang') {
                    continue;
                }
                $key = $iso.'|'.$gv['ma_gv'];
                if (isset($cells[$key])) {
                    continue;
                }
                $cells[$key] = $this->cellCabin($iso, $gv, $mauCa, $cauHinh);
                $hoursLeft[$gv['ma_gv']]['cabin'] = max(0, ($hoursLeft[$gv['ma_gv']]['cabin'] ?? 0) - 10);
            }
        }
    }

    /** @param array<string, array<string, mixed>> $cells */
    private function scheduleAutoByBlocks(
        array &$cells,
        array $cauHinh,
        array $giaoViens,
        array $blocksByCap,
        array &$hoursLeft,
        array $mauCa,
        string $hang
    ): void {
        $hangNorm = str_replace(['.', ' '], '', strtoupper($hang));
        if (str_contains($hangNorm, 'B01')) {
            return;
        }
        foreach ($blocksByCap as $stt => $block) {
            foreach ($block as $iso) {
                foreach ($giaoViens as $gv) {
                    if ((int) $gv['cap_stt'] !== (int) $stt || ($gv['ca'] ?? '') !== 'sang') {
                        continue;
                    }
                    $key = $iso.'|'.$gv['ma_gv'];
                    if (isset($cells[$key])) {
                        continue;
                    }
                    $cells[$key] = $this->cellTuDong($iso, $gv, $mauCa, $cauHinh);
                    $hoursLeft[$gv['ma_gv']]['tu_dong'] = max(0, ($hoursLeft[$gv['ma_gv']]['tu_dong'] ?? 0) - 5);
                }
            }
        }
    }

    /** @param array<string, mixed> $cauHinh
     * @param array<string, mixed> $gv
     */
    private function cellCabin(string $iso, array $gv, array $mauCa, array $cauHinh): array
    {
        $range = $mauCa['cabin']['ca'] ?? ['07:00', '17:00'];

        $stt = (int) ($gv['cap_stt'] ?? 0);
        $bai = $stt > 0 ? 'CABIN '.$stt : 'CABIN';

        return $this->cellFromRange($iso, $gv, BaiGiang::CABIN, $bai, $range, 10);
    }

    /** @param array<string, mixed> $cauHinh
     * @param array<string, mixed> $gv
     */
    private function cellTuDong(string $iso, array $gv, array $mauCa, array $cauHinh): array
    {
        $range = $gv['ca'] === 'sang'
            ? ($mauCa['tu_dong']['sang'] ?? ['07:00', '12:00'])
            : ($mauCa['tu_dong']['chieu'] ?? ['13:00', '18:00']);
        $bien = trim((string) ($cauHinh['xe_tu_dong_chung']['bien_so'] ?? ''));

        return array_merge(
            $this->cellFromRange($iso, $gv, BaiGiang::TU_DONG, 'TỰ ĐỘNG', $range, 5),
            ['bien_so_override' => $bien !== '' ? $bien : null]
        );
    }

    /**
     * @param  array<string, mixed>  $gv
     * @param  list<string>  $range
     * @return array<string, mixed>
     */
    private function cellFromRange(string $iso, array $gv, string $mau, string $bai, array $range, float $soGio): array
    {
        $day = Carbon::parse($iso);

        return [
            'thu' => LichNgay::thuLabel($day),
            'thoi_gian' => $day->format('Y-m-d').' 00:00:00',
            'so_gio' => $soGio,
            'bai' => $bai,
            'noi_dung_phu' => '',
            'bat_dau' => MauCa::formatGioExcel($range[0] ?? ''),
            'ket_thuc' => MauCa::formatGioExcel($range[count($range) - 1] ?? ''),
            'mau' => $mau,
            'nghi_ca_khoa' => false,
            'ma_gv' => $gv['ma_gv'],
            'ho_ten' => $gv['ho_ten'],
            'bien_so' => $gv['bien_so'],
            'ma_khoa' => $gv['ma_khoa'],
            'cap_stt' => (int) ($gv['cap_stt'] ?? 0),
        ];
    }

    /**
     * Hạng B (không import kế hoạch): áp kế hoạch mẫu theo ca từ mốc ngày (file Data/mau_hang_b_*).
     *
     * @param  array<string, array<string, mixed>>  $cells
     * @param  array<string, mixed>  $cauHinh
     * @param  list<array<string, mixed>>  $giaoViens
     */
    private function applyMauKeHoachCa(array &$cells, array $cauHinh, array $giaoViens, string $ca): void
    {
        if ($this->coKeHoachImport($cauHinh)) {
            return;
        }
        $hang = strtoupper(trim((string) ($cauHinh['hang_dao_tao'] ?? 'B')));
        if ($hang !== 'B') {
            return;
        }
        $flag = $ca === 'sang' ? 'mau_ke_hoach_sang' : 'mau_ke_hoach_chieu';
        $legacy = $ca === 'sang' ? 'mau_sang_bk54' : 'mau_chieu_bk54';
        if (($cauHinh[$flag] ?? $cauHinh[$legacy] ?? true) === false) {
            return;
        }

        $anchor = LichMauKeHoachCa::anchor($ca);
        $byCap = LichMauKeHoachCa::slotsByCapFromAnchor($ca);
        if ($byCap === []) {
            return;
        }

        $bienTuDong = trim((string) ($cauHinh['xe_tu_dong_chung']['bien_so'] ?? ''));

        foreach ($giaoViens as $gv) {
            if (($gv['ca'] ?? '') !== $ca) {
                continue;
            }
            $cap = (int) ($gv['cap_stt'] ?? 0);
            $tpl = $byCap[(string) $cap] ?? $byCap['1'] ?? [];
            foreach ($tpl as $iso => $slot) {
                if (! is_array($slot) || $iso < $anchor) {
                    continue;
                }
                $day = Carbon::parse($iso);
                if (LichNgay::laNgayNghi($day, $cauHinh)) {
                    continue;
                }
                $mau = (string) ($slot['mau'] ?? '');
                $bai = $this->adaptBaiMauKeHoach((string) ($slot['bai'] ?? ''), $cap, $bienTuDong, $ca);
                $bd = (string) ($slot['bat_dau'] ?? '');
                $kt = (string) ($slot['ket_thuc'] ?? '');
                $noiDungPhu = (string) ($slot['noi_dung_phu'] ?? '');
                if ($noiDungPhu === '' && $mau === BaiGiang::PHUC_TAP) {
                    if (str_contains($kt, '18H') && str_contains($kt, '22H')
                        && ! str_contains(str_replace(' ', '', $kt), '20H01')) {
                        $noiDungPhu = 'BAN ĐÊM';
                    } elseif (str_contains($bd, '18H') && str_contains($kt, '22H')
                        && ! str_contains(str_replace(' ', '', $bd.' '.$kt), '20H01')) {
                        $noiDungPhu = 'BAN ĐÊM';
                    }
                }
                $cells[$iso.'|'.$gv['ma_gv']] = $this->cellFromKeHoachSlot(
                    $iso,
                    $gv,
                    $mau,
                    $bai,
                    (float) ($slot['so_gio'] ?? 0),
                    $bd,
                    $kt,
                    $noiDungPhu,
                );
            }
        }
    }

    private function adaptBaiMauKeHoach(string $bai, int $capStt, string $bienTuDong, string $ca): string
    {
        if ($ca === 'sang' && preg_match('/^CABIN\s*\d+$/u', trim($bai)) && $capStt > 0) {
            return 'CABIN '.$capStt;
        }
        if (str_starts_with(mb_strtoupper($bai), 'TỰ ĐỘNG') && $bienTuDong !== '') {
            return 'TỰ ĐỘNG '.$bienTuDong;
        }

        return $bai;
    }

    /** @param array<string, mixed> $gv */
    private function emptyCell(string $iso, array $gv, bool $nghi): array
    {
        $day = Carbon::parse($iso);

        return [
            'thu' => LichNgay::thuLabel($day),
            'thoi_gian' => $day->format('Y-m-d').' 00:00:00',
            'so_gio' => 0,
            'bai' => $nghi ? 'NGHỈ' : '',
            'noi_dung_phu' => '',
            'bat_dau' => '',
            'ket_thuc' => '',
            'mau' => 'NGHI',
            'nghi_ca_khoa' => $nghi,
            'ma_gv' => $gv['ma_gv'],
            'ho_ten' => $gv['ho_ten'],
            'bien_so' => $gv['bien_so'],
            'ma_khoa' => $gv['ma_khoa'],
        ];
    }

    /**
     * @param  array<string, mixed>  $lich
     * @param  array<string, mixed>  $cauHinh
     * @return array<string, mixed>
     */
    private function buildTomTat(array $lich, array $cauHinh): array
    {
        $yeuCau = YeuCauGio::theoGiaoVien($cauHinh);
        $byGv = [];
        foreach ($lich['giao_viens'] ?? [] as $gv) {
            $ma = $gv['ma_gv'];
            $byGv[$ma] = [
                'ho_ten' => $gv['ho_ten'],
                'gio_dat' => 0.0,
                'gio_khong_dat' => 0.0,
                'gio_dem' => 0.0,
                'gio_tu_dong' => 0.0,
                'gio_cao_toc' => 0.0,
                'gio_on_tap' => 0.0,
                'so_ngay_lam' => 0,
                'yeu_cau' => $yeuCau[$ma] ?? null,
            ];
        }

        foreach ($lich['cells'] ?? [] as $cell) {
            if (! is_array($cell)) {
                continue;
            }
            $ma = (string) ($cell['ma_gv'] ?? '');
            if (! isset($byGv[$ma])) {
                continue;
            }
            if (($cell['nghi_ca_khoa'] ?? false) || ($cell['bai'] ?? '') === 'NGHỈ') {
                continue;
            }
            $gio = (float) ($cell['so_gio'] ?? 0);
            if ($gio <= 0) {
                continue;
            }
            $byGv[$ma]['so_ngay_lam']++;
            $mau = (string) ($cell['mau'] ?? '');
            $tinhDat = in_array($mau, [BaiGiang::PHUC_TAP, BaiGiang::TU_DONG, BaiGiang::DOC_QC, BaiGiang::CAO_TOC, BaiGiang::CO_TAI, BaiGiang::BAN_DEM], true);
            if ($tinhDat) {
                $byGv[$ma]['gio_dat'] += $gio;
            } else {
                $byGv[$ma]['gio_khong_dat'] += $gio;
            }
            if ($mau === BaiGiang::TU_DONG) {
                $byGv[$ma]['gio_tu_dong'] += $gio;
            }
            if ($mau === BaiGiang::CAO_TOC) {
                $byGv[$ma]['gio_cao_toc'] += $gio;
            }
            if (in_array($mau, [BaiGiang::ON_STL, BaiGiang::ON_TD], true)) {
                $byGv[$ma]['gio_on_tap'] += $gio;
            }
            if (str_contains(strtoupper((string) ($cell['noi_dung_phu'] ?? '')), 'BAN')) {
                $byGv[$ma]['gio_dem'] += $gio;
            }
        }

        return ['giao_viens' => $byGv];
    }

    /**
     * @param  array<string, array<string, mixed>>  $cells
     * @param  list<array<string, mixed>>  $giaoViens
     */
    private function detectLastWorkingDate(array $cells, array $giaoViens): ?string
    {
        $last = null;
        foreach (array_keys($cells) as $key) {
            [$iso] = explode('|', $key, 2);
            if ($last === null || $iso > $last) {
                $last = $iso;
            }
        }

        return $last;
    }
}
