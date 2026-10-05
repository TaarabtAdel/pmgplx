<?php

namespace App\Support\DaoTao\LichThucHanh;

/**
 * Mốc ngày cố định (hình đầu/cuối, ôn tập, KT, bổ sung…) — tùy từng dự án / khóa.
 * Mốc có thể khai trên màn hoặc import từ Excel; hạng B có bộ mặc định khi chưa khai.
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
        if ($hinhDau === [] && self::laHangBSan($cauHinh)) {
            $hinhDau = self::bonNgayLamDau($cauHinh);
        }

        $hinhCuoi = self::normalizeDateList($moc['hinh_cuoi'] ?? []);
        $boSung = self::normalizeDateList($moc['bo_sung'] ?? []);
        $cabinNeo = self::firstDate($moc['cabin_neo'] ?? null);
        $autoNeo = self::firstDate($moc['auto_neo'] ?? null);

        if (self::laHangBSan($cauHinh) && ! self::coKeHoachImport($cauHinh)) {
            $oracle = self::mocMacDinhHangB();
            if ($cabinNeo === null) {
                $cabinNeo = $oracle['cabin_neo'];
            }
            if ($autoNeo === null) {
                $autoNeo = $oracle['auto_neo'];
            }
            if ($hinhCuoi === []) {
                $hinhCuoi = $oracle['hinh_cuoi'];
            }
            if ($onTap === []) {
                $onTap = $oracle['on_tap'];
            }
            if ($kt === '') {
                $kt = $oracle['kiem_tra'];
            }
            if ($boSung === []) {
                $boSung = $oracle['bo_sung'];
            }
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

    /** @param array<string, mixed> $cauHinh */
    private static function coKeHoachImport(array $cauHinh): bool
    {
        $ke = $cauHinh['ke_hoach_theo_gv'] ?? [];

        return is_array($ke) && $ke !== [];
    }

    /** Mốc lịch tham chiếu hạng B (từ file Excel mẫu đã import — ngày có thể sửa trên cấu hình). */
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

    /**
     * B sàn: 4 ngày làm việc đầu khóa (cặp 1) — giai đoạn HÌNH đầu.
     *
     * @param  array<string, mixed>  $cauHinh
     * @return list<string>
     */
    private static function bonNgayLamDau(array $cauHinh): array
    {
        $timeline = new LichTimeline($cauHinh);
        $dates = $timeline->calendarDates();
        if ($dates === []) {
            return [];
        }
        $capStt = 1;
        foreach ($cauHinh['cap_xe'] ?? [] as $cap) {
            if (! is_array($cap)) {
                continue;
            }
            $stt = (int) ($cap['stt'] ?? 0);
            if ($stt > 0) {
                $capStt = $stt;
                break;
            }
        }
        $work = $timeline->workDaysForCap($dates, $capStt);

        return array_slice($work, 0, 4);
    }
}
