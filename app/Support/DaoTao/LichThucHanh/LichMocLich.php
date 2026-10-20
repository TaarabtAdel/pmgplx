<?php

namespace App\Support\DaoTao\LichThucHanh;

/**
 * Mốc ngày theo khóa (hình đầu/cuối, cabin, tự động, ôn, KT…).
 * Ưu tiên giá trị khai trên cấu hình; còn thiếu thì suy ra từ ngày làm việc của cặp xe.
 */
final class LichMocLich
{
    /**
     * @param  array<string, mixed>  $cauHinh
     * @return array{
     *     hinh_dau: list<string>,
     *     hinh_cuoi: list<string>,
     *     on_tap: array<string, string>,
     *     kiem_tra: string,
     *     bo_sung: list<string>,
     *     cabin_neo: ?string,
     *     auto_neo: ?string
     * }
     */
    public static function fromCauHinh(array $cauHinh): array
    {
        $moc = is_array($cauHinh['moc_lich'] ?? null) ? $cauHinh['moc_lich'] : [];
        $onTap = [];
        foreach ($moc['on_tap'] ?? [] as $iso => $mau) {
            $n = LichNgay::normalizeDate($iso);
            if ($n !== '') {
                $onTap[$n] = (string) $mau;
            }
        }

        $kt = LichNgay::normalizeDate($moc['kiem_tra'] ?? $cauHinh['ngay_ket_thuc_du_kien'] ?? $cauHinh['lich_den_ngay'] ?? '');

        $hinhDau = self::normalizeDateList($moc['hinh_dau'] ?? []);
        $hinhCuoi = self::normalizeDateList($moc['hinh_cuoi'] ?? []);
        $boSung = self::normalizeDateList($moc['bo_sung'] ?? []);
        $cabinNeo = self::firstDate($moc['cabin_neo'] ?? null);
        $autoNeo = self::firstDate($moc['auto_neo'] ?? null);

        $computed = self::mocTuNgayLamViec($cauHinh);

        if ($hinhDau === []) {
            $hinhDau = $computed['hinh_dau'];
        }
        if ($cabinNeo === null) {
            $cabinNeo = $computed['cabin_neo'];
        }
        if ($autoNeo === null && self::laHangBSan($cauHinh)) {
            $autoNeo = $computed['auto_neo'];
        }
        if ($hinhCuoi === []) {
            $hinhCuoi = $computed['hinh_cuoi'];
        }
        if ($onTap === []) {
            $onTap = $computed['on_tap'];
        }
        if ($kt === '') {
            $kt = $computed['kiem_tra'];
        }
        if ($boSung === []) {
            $boSung = $computed['bo_sung'];
        }

        return [
            'hinh_dau' => $hinhDau,
            'hinh_cuoi' => $hinhCuoi,
            'on_tap' => $onTap,
            'kiem_tra' => $kt,
            'bo_sung' => $boSung,
            'cabin_neo' => $cabinNeo,
            'auto_neo' => $autoNeo,
        ];
    }

    /**
     * Mốc suy ra từ ngày làm việc của cặp xe đầu (theo chương trình hạng).
     *
     * @param  array<string, mixed>  $cauHinh
     * @return array{
     *     hinh_dau: list<string>,
     *     hinh_cuoi: list<string>,
     *     on_tap: array<string, string>,
     *     kiem_tra: string,
     *     bo_sung: list<string>,
     *     cabin_neo: ?string,
     *     auto_neo: ?string
     * }
     */
    public static function mocTuNgayLamViec(array $cauHinh): array
    {
        $timeline = new LichTimeline($cauHinh);
        $dates = $timeline->calendarDates();
        $work = $timeline->workDaysForCap($dates, self::capSttDau($cauHinh));
        $empty = [
            'hinh_dau' => [],
            'hinh_cuoi' => [],
            'on_tap' => [],
            'kiem_tra' => '',
            'bo_sung' => [],
            'cabin_neo' => null,
            'auto_neo' => null,
        ];
        if ($work === []) {
            return $empty;
        }

        $hangSan = self::laHangBSan($cauHinh);
        $soHinhDau = 4;
        $soHinhCuoi = 6;
        $soBoSung = 2;
        $soOnTap = 2;
        $soKt = 1;
        $soCabin = 1;
        $soTuDong = $hangSan ? 2 : 0;

        $n = count($work);
        $reservedEnd = $soHinhCuoi + $soBoSung + $soOnTap + $soKt;
        $reservedStart = $soHinhDau + $soCabin + $soTuDong;

        if ($n < $reservedStart + $reservedEnd) {
            $soHinhCuoi = max(0, min($soHinhCuoi, $n - $soOnTap - $soKt - $soHinhDau));
            $soBoSung = max(0, min($soBoSung, $n - $soHinhDau - $soOnTap - $soKt - $soHinhCuoi));
        }

        $kt = $work[$n - 1];
        $ktCfg = LichNgay::normalizeDate($cauHinh['ngay_ket_thuc_du_kien'] ?? $cauHinh['lich_den_ngay'] ?? '');
        if ($ktCfg !== '' && in_array($ktCfg, $work, true)) {
            $kt = $ktCfg;
        }

        $ktIdx = array_search($kt, $work, true);
        if ($ktIdx === false) {
            $ktIdx = $n - 1;
            $kt = $work[$ktIdx];
        }

        $onTap = [];
        $cursor = $ktIdx - 1;
        if ($cursor >= 0 && $soOnTap >= 1) {
            $onTap[$work[$cursor]] = BaiGiang::ON_TD;
            $cursor--;
        }
        if ($cursor >= 0 && $soOnTap >= 2) {
            $onTap[$work[$cursor]] = BaiGiang::ON_STL;
            $cursor--;
        }

        $hinhCuoi = [];
        for ($i = 0; $i < $soHinhCuoi && $cursor >= 0; $i++, $cursor--) {
            $hinhCuoi[] = $work[$cursor];
        }
        $hinhCuoi = array_reverse($hinhCuoi);

        $boSung = [];
        for ($i = 0; $i < $soBoSung && $cursor >= 0; $i++, $cursor--) {
            $boSung[] = $work[$cursor];
        }
        $boSung = array_reverse($boSung);

        $hinhDau = array_slice($work, 0, min($soHinhDau, $n));
        $cabinNeo = $work[$soHinhDau] ?? null;
        $autoNeo = $hangSan ? ($work[$soHinhDau + $soCabin] ?? null) : null;

        return [
            'hinh_dau' => $hinhDau,
            'hinh_cuoi' => $hinhCuoi,
            'on_tap' => $onTap,
            'kiem_tra' => $kt,
            'bo_sung' => $boSung,
            'cabin_neo' => $cabinNeo,
            'auto_neo' => $autoNeo,
        ];
    }

    /**
     * Mốc cố định từ file Excel mẫu cũ — chỉ dùng khi import Excel, không dùng khi sinh lịch mới.
     *
     * @return array<string, mixed>
     */
    public static function mocMacDinhHangB(): array
    {
        return [
            'hinh_dau' => ['2026-08-18', '2026-08-19', '2026-08-20', '2026-08-21'],
            'hinh_cuoi' => ['2026-09-26', '2026-09-27', '2026-09-28', '2026-09-29', '2026-09-30', '2026-10-01'],
            'on_tap' => [
                '2026-10-02' => BaiGiang::ON_STL,
                '2026-10-03' => BaiGiang::ON_TD,
            ],
            'kiem_tra' => '2026-10-04',
            'bo_sung' => ['2026-09-23', '2026-09-24'],
            'cabin_neo' => '2026-08-22',
            'auto_neo' => '2026-08-23',
        ];
    }

    /** @param  list<mixed>  $dates
     * @return list<string>
     */
    private static function normalizeDateList(array $dates): array
    {
        $out = [];
        foreach ($dates as $d) {
            $n = LichNgay::normalizeDate($d);
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return $out;
    }

    private static function firstDate(mixed $value): ?string
    {
        $n = LichNgay::normalizeDate($value);

        return $n !== '' ? $n : null;
    }

    /** @param array<string, mixed> $cauHinh */
    private static function laHangBSan(array $cauHinh): bool
    {
        $hang = strtoupper(trim((string) ($cauHinh['hang_dao_tao'] ?? 'B')));

        return $hang === 'B';
    }

    /** @param array<string, mixed> $cauHinh */
    private static function capSttDau(array $cauHinh): int
    {
        foreach ($cauHinh['cap_xe'] ?? [] as $cap) {
            if (! is_array($cap)) {
                continue;
            }
            $stt = (int) ($cap['stt'] ?? 0);
            if ($stt > 0) {
                return $stt;
            }
        }

        return 1;
    }
}
