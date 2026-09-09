<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienCanhBao;
use App\Models\DaoTao\DatDSPhien;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Kiểm tra chất lượng / hợp lệ phiên DAT trên danh sách quản lý.
 */
class DatDSPhienKiemTra
{
    public const LOI_THOI_GIAN_NGAN = 'thoi_gian_ngan';

    public const LOI_THOI_GIAN_DAI = 'thoi_gian_dai';

    public const LOI_TI_LE_ND = 'ti_le_nd';

    public const LOI_PHIEN_LIEN_KE = 'phien_lien_ke';

    public const LOI_TRUNG_HV = 'trung_hv';

    public const LOI_TRUNG_GV = 'trung_gv';

    public const LOI_LICH_XE = 'lich_xe';

    /**
     * @return array{
     *     min_phut: int,
     *     max_phut: int,
     *     khoang_phut: int,
     *     ti_le: float
     * }
     */
    public static function settings(): array
    {
        return DatDieuKienCanhBao::hienTai()->toSettingsArray();
    }

    /**
     * @return array<string, array{label: string, badge: string}>
     */
    public static function definitions(): array
    {
        $s = self::settings();

        return [
            self::LOI_THOI_GIAN_NGAN => [
                'label' => 'Thời gian phiên < '.$s['min_phut'].' phút',
                'badge' => 'badge-warning',
            ],
            self::LOI_THOI_GIAN_DAI => [
                'label' => self::formatMaxDurationLabel($s['max_phut']),
                'badge' => 'badge-warning',
            ],
            self::LOI_TI_LE_ND => [
                'label' => 'Tỉ lệ nhận diện < '.self::formatTiLe($s['ti_le']).'% (ảnh điều kiện chưa đạt)',
                'badge' => 'badge-danger',
            ],
            self::LOI_PHIEN_LIEN_KE => [
                'label' => 'Phiên liền kề cách < '.$s['khoang_phut'].' phút (cùng học viên, phiên ngắn hơn)',
                'label_lines' => [
                    'Phiên liền kề cách < '.$s['khoang_phut'].' phút',
                    '(cùng học viên, phiên ngắn hơn)',
                ],
                'badge' => 'badge-warning',
            ],
            self::LOI_TRUNG_HV => [
                'label' => 'Học viên trùng khung giờ với phiên khác',
                'badge' => 'badge-danger',
            ],
            self::LOI_TRUNG_GV => [
                'label' => 'Giáo viên trùng khung giờ với phiên khác',
                'badge' => 'badge-danger',
            ],
            self::LOI_LICH_XE => [
                'label' => 'Không khớp lịch xe tập (PMGPLX — ngày / khung giờ)',
                'label_lines' => [
                    'Không khớp lịch xe tập',
                    '(PMGPLX — ngày / khung giờ / biển số)',
                ],
                'badge' => 'badge-warning',
            ],
        ];
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @return array<int, list<string>>
     */
    public static function analyze(Collection $sessions): array
    {
        $violations = [];
        $s = self::settings();

        foreach ($sessions as $session) {
            $id = (int) $session->Id;
            $violations[$id] = self::singleSessionViolations($session, $s);
        }

        self::applyAdjacentViolations($sessions, $violations, $s['khoang_phut']);
        self::applyOverlapViolations($sessions, $violations, 'MaHocVien', self::LOI_TRUNG_HV);
        self::applyOverlapViolations($sessions, $violations, 'MaGiaoVien', self::LOI_TRUNG_GV);
        self::applyLichXeViolations($sessions, $violations);

        return $violations;
    }

    public static function datAnhDieuKien(?float $tiLe): bool
    {
        $s = self::settings();

        return $tiLe !== null && $tiLe >= $s['ti_le'];
    }

    /**
     * Phiên đạt khi không vi phạm bất kỳ điều kiện cảnh báo nào.
     *
     * @param  array<int, list<string>>  $violationsById
     */
    public static function datPhien(array $violationsById, int $id): bool
    {
        return ($violationsById[$id] ?? []) === [];
    }

    /**
     * @param  array<int, list<string>>  $violationsById
     */
    public static function matchesDatFilter(array $violationsById, int $id, string $filter): bool
    {
        $dat = self::datPhien($violationsById, $id);

        return $filter === 'dat' ? $dat : ! $dat;
    }

    /**
     * @param  array<int, list<string>>  $violationsById
     * @param  list<string>  $selectedLoai
     */
    public static function matchesFilter(array $violationsById, int $id, array $selectedLoai): bool
    {
        if ($selectedLoai === []) {
            return true;
        }

        $rowViolations = $violationsById[$id] ?? [];

        foreach ($selectedLoai as $code) {
            if (in_array($code, $rowViolations, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{min_phut: int, max_phut: int, khoang_phut: int, ti_le: float}  $settings
     * @return list<string>
     */
    private static function singleSessionViolations(DatDSPhien $session, array $settings): array
    {
        $loi = [];
        $minutes = self::durationMinutes($session);

        if ($minutes !== null) {
            if ($minutes < $settings['min_phut']) {
                $loi[] = self::LOI_THOI_GIAN_NGAN;
            }
            if ($minutes > $settings['max_phut']) {
                $loi[] = self::LOI_THOI_GIAN_DAI;
            }
        }

        if (! self::datAnhDieuKien($session->TiLeNhanDien !== null ? (float) $session->TiLeNhanDien : null)) {
            $loi[] = self::LOI_TI_LE_ND;
        }

        return $loi;
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  array<int, list<string>>  $violations
     */
    private static function applyAdjacentViolations(Collection $sessions, array &$violations, int $khoangPhut): void
    {
        $groups = $sessions
            ->filter(fn (DatDSPhien $s): bool => self::maHocVienKey($s) !== '')
            ->groupBy(fn (DatDSPhien $s): string => self::maHocVienKey($s));

        foreach ($groups as $group) {
            $ordered = $group
                ->sortBy(fn (DatDSPhien $s) => self::startAt($s)?->timestamp ?? 0)
                ->values();

            for ($i = 0; $i < $ordered->count() - 1; $i++) {
                $current = $ordered[$i];
                $next = $ordered[$i + 1];
                $end = self::endAt($current);
                $startNext = self::startAt($next);

                if ($end === null || $startNext === null) {
                    continue;
                }

                $gapMinutes = $end->diffInMinutes($startNext, false);
                if ($gapMinutes < 0 || $gapMinutes >= $khoangPhut) {
                    continue;
                }

                $durCurrent = self::durationMinutes($current) ?? 0.0;
                $durNext = self::durationMinutes($next) ?? 0.0;
                $shorterId = $durCurrent <= $durNext ? (int) $current->Id : (int) $next->Id;

                $violations[$shorterId] ??= [];
                if (! in_array(self::LOI_PHIEN_LIEN_KE, $violations[$shorterId], true)) {
                    $violations[$shorterId][] = self::LOI_PHIEN_LIEN_KE;
                }
            }
        }
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  array<int, list<string>>  $violations
     */
    private static function applyOverlapViolations(
        Collection $sessions,
        array &$violations,
        string $field,
        string $code
    ): void {
        $groups = $sessions
            ->filter(fn (DatDSPhien $s): bool => trim((string) ($s->{$field} ?? '')) !== '')
            ->groupBy(fn (DatDSPhien $s): string => trim((string) $s->{$field}));

        foreach ($groups as $group) {
            $rows = $group->values()->all();
            $count = count($rows);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if (! self::overlaps($rows[$i], $rows[$j])) {
                        continue;
                    }

                    foreach ([(int) $rows[$i]->Id, (int) $rows[$j]->Id] as $id) {
                        $violations[$id] ??= [];
                        if (! in_array($code, $violations[$id], true)) {
                            $violations[$id][] = $code;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  array<int, list<string>>  $violations
     */
    private static function applyLichXeViolations(Collection $sessions, array &$violations): void
    {
        $byCourse = $sessions->groupBy(
            fn (DatDSPhien $session): string => trim((string) ($session->MaKhoaHoc ?? ''))
        );

        foreach ($byCourse as $maKhoaHoc => $group) {
            if ($maKhoaHoc === '') {
                continue;
            }

            $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc);

            foreach ($group as $session) {
                $result = DatPhienLichXeMatcher::evaluate($session, $scheduleRows);
                if ($result['valid']) {
                    continue;
                }

                $id = (int) $session->Id;
                $violations[$id] ??= [];
                if (! in_array(self::LOI_LICH_XE, $violations[$id], true)) {
                    $violations[$id][] = self::LOI_LICH_XE;
                }
            }
        }
    }

    private static function formatMaxDurationLabel(int $minutes): string
    {
        if ($minutes % 60 === 0 && $minutes >= 60) {
            $hours = (int) ($minutes / 60);

            return 'Thời gian phiên > '.$hours.' giờ';
        }

        return 'Thời gian phiên > '.$minutes.' phút';
    }

    private static function formatTiLe(float $tiLe): string
    {
        return rtrim(rtrim(number_format($tiLe, 2, '.', ''), '0'), '.');
    }

    private static function maHocVienKey(DatDSPhien $session): string
    {
        return trim((string) ($session->MaHocVien ?? ''));
    }

    private static function overlaps(DatDSPhien $a, DatDSPhien $b): bool
    {
        $startA = self::startAt($a);
        $endA = self::endAt($a);
        $startB = self::startAt($b);
        $endB = self::endAt($b);

        if ($startA === null || $endA === null || $startB === null || $endB === null) {
            return false;
        }

        return $startA->lt($endB) && $startB->lt($endA);
    }

    private static function durationMinutes(DatDSPhien $session): ?float
    {
        $start = self::startAt($session);
        $end = self::endAt($session);

        if ($start === null || $end === null) {
            return null;
        }

        $minutes = $start->diffInRealMinutes($end, false);

        return $minutes >= 0 ? (float) $minutes : null;
    }

    private static function startAt(DatDSPhien $session): ?Carbon
    {
        $value = $session->ThoiGianBatDauPhienHoc;

        return $value instanceof Carbon ? $value : ($value ? Carbon::parse($value) : null);
    }

    private static function endAt(DatDSPhien $session): ?Carbon
    {
        $value = $session->ThoiGianKetThucPhienHoc;

        return $value instanceof Carbon ? $value : ($value ? Carbon::parse($value) : null);
    }
}
