<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\DaoTao\DatDieuKienDoPhien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\PMGPLX\LichExcelBienSo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DatPhienLichXeMatcher
{
    /**
     * @return Collection<int, KhoaHocXeTap>
     */
    public static function scheduleForCourse(string $maKhoaHoc): Collection
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            return collect();
        }

        return KhoaHocXeTap::query()
            ->where('MaKH', $maKhoaHoc)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->orderBy('MaLichSD')
            ->get();
    }

    /**
     * Dò phiên với lịch xe: map theo mã khóa, mã GV, biển số, ngày.
     * Nhiều khung cùng ngày → ưu tiên khung mà phiên nằm trọn trong lịch;
     * nếu không có thì lấy dòng có TG bắt đầu gần TG bắt đầu phiên nhất.
     *
     * @return array{
     *     valid: bool,
     *     message: string,
     *     matched: ?KhoaHocXeTap,
     *     displaySchedule: ?KhoaHocXeTap
     * }
     */
    public static function evaluate(DatDSPhien $session, Collection $scheduleRows): array
    {
        $maKhoaHoc = trim((string) ($session->MaKhoaHoc ?? ''));
        $maGiaoVien = self::normalizeMaGv((string) ($session->MaGiaoVien ?? ''));
        $bienSo = LichExcelBienSo::normalize((string) ($session->BienSoXe ?? ''));
        $start = self::toCarbon($session->ThoiGianBatDauPhienHoc);

        if ($start === null) {
            return self::invalid('Phiên thiếu thời gian bắt đầu');
        }

        if ($maKhoaHoc === '') {
            return self::invalid('Phiên thiếu mã khóa học');
        }

        if ($maGiaoVien === '') {
            return self::invalid('Phiên thiếu mã giáo viên');
        }

        if ($bienSo === '') {
            return self::invalid('Phiên thiếu biển số xe');
        }

        if ($scheduleRows->isEmpty()) {
            return self::invalid('Không có lịch xe tập cho mã khóa '.$maKhoaHoc);
        }

        $end = self::toCarbon($session->ThoiGianKetThucPhienHoc);
        $tolerance = DatDieuKienDoPhien::hienTai()->toSettingsArray();

        $matched = self::findSchedule($scheduleRows, $maKhoaHoc, $maGiaoVien, $bienSo, $start, $end, $tolerance);
        if ($matched === null) {
            return self::invalid(
                'Không có lịch xe (khóa '.$maKhoaHoc.', GV '.$maGiaoVien.', xe '.$session->BienSoXe
                .', ngày '.$start->format('d/m/Y').')'
            );
        }

        $timeMessage = self::timeMismatchMessage($start, $end, $matched, $tolerance);

        if ($timeMessage !== null) {
            return [
                'valid' => false,
                'message' => $timeMessage,
                'matched' => $matched,
                'displaySchedule' => $matched,
            ];
        }

        return [
            'valid' => true,
            'message' => '',
            'matched' => $matched,
            'displaySchedule' => $matched,
        ];
    }

    /**
     * @param  Collection<int, KhoaHocXeTap>  $scheduleRows
     */
    public static function findSchedule(
        Collection $scheduleRows,
        string $maKhoaHoc,
        string $maGiaoVien,
        string $bienSo,
        Carbon $sessionStart,
        ?Carbon $sessionEnd = null,
        ?array $tolerance = null
    ): ?KhoaHocXeTap {
        $tolerance ??= DatDieuKienDoPhien::hienTai()->toSettingsArray();
        $sessionDate = $sessionStart->toDateString();

        $candidates = $scheduleRows->filter(function (KhoaHocXeTap $lich) use (
            $maKhoaHoc,
            $maGiaoVien,
            $bienSo,
            $sessionDate
        ): bool {
            if (trim((string) ($lich->MaKH ?? '')) !== $maKhoaHoc) {
                return false;
            }

            if (self::normalizeMaGv((string) ($lich->MaGV ?? '')) !== $maGiaoVien) {
                return false;
            }

            if (LichExcelBienSo::normalize((string) ($lich->BienSoXe ?? '')) !== $bienSo) {
                return false;
            }

            $lichStart = self::toCarbon($lich->NgayBD);

            return $lichStart !== null && $lichStart->toDateString() === $sessionDate;
        });

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($sessionEnd !== null) {
            $fitting = $candidates->filter(
                fn (KhoaHocXeTap $lich): bool => self::sessionFitsSchedule(
                    $sessionStart,
                    $sessionEnd,
                    $lich,
                    $tolerance
                )
            );

            if ($fitting->isNotEmpty()) {
                return self::pickNearestByStart($fitting, $sessionStart);
            }
        }

        return self::pickNearestByStart($candidates, $sessionStart);
    }

    /**
     * @param  Collection<int, KhoaHocXeTap>  $candidates
     */
    private static function pickNearestByStart(Collection $candidates, Carbon $sessionStart): ?KhoaHocXeTap
    {
        return $candidates
            ->sortBy(function (KhoaHocXeTap $lich) use ($sessionStart): int {
                $lichStart = self::toCarbon($lich->NgayBD);

                return $lichStart === null
                    ? PHP_INT_MAX
                    : (int) abs($sessionStart->diffInSeconds($lichStart));
            })
            ->first();
    }

    private static function sessionFitsSchedule(
        Carbon $sessionStart,
        Carbon $sessionEnd,
        KhoaHocXeTap $lich,
        array $tolerance
    ): bool {
        $window = self::allowedTimeWindow($lich, $tolerance);

        if ($window === null) {
            return false;
        }

        $sessionStartCompare = self::normalizeSessionTime($sessionStart, $tolerance);
        $sessionEndCompare = self::normalizeSessionTime($sessionEnd, $tolerance);

        return $sessionStartCompare->gte($window['allowedStart'])
            && $sessionEndCompare->lte($window['allowedEnd']);
    }

    private static function timeMismatchMessage(
        Carbon $sessionStart,
        ?Carbon $sessionEnd,
        KhoaHocXeTap $lich,
        array $tolerance
    ): ?string {
        if ($sessionEnd === null) {
            return 'Phiên thiếu thời gian kết thúc';
        }

        $window = self::allowedTimeWindow($lich, $tolerance);

        if ($window === null) {
            return 'Lịch xe thiếu thời gian bắt đầu hoặc kết thúc';
        }

        $sessionStartCompare = self::normalizeSessionTime($sessionStart, $tolerance);
        $sessionEndCompare = self::normalizeSessionTime($sessionEnd, $tolerance);
        $timeFormat = self::usesSecondPrecision($tolerance) ? 'H:i:s' : 'H:i';

        $issues = [];

        if ($sessionStartCompare->lt($window['allowedStart'])) {
            $issues[] = 'bắt đầu phiên '.$sessionStartCompare->format($timeFormat)
                .' sớm hơn lịch '.$window['lichStart']->format($timeFormat);
        }

        if ($sessionEndCompare->gt($window['allowedEnd'])) {
            $issues[] = 'kết thúc phiên '.$sessionEndCompare->format($timeFormat)
                .' muộn hơn lịch '.$window['lichEnd']->format($timeFormat);
        }

        if ($issues === []) {
            return null;
        }

        return 'Lệch thời gian so với lịch xe ('.implode('; ', $issues).')';
    }

    /**
     * @return array{
     *     lichStart: Carbon,
     *     lichEnd: Carbon,
     *     allowedStart: Carbon,
     *     allowedEnd: Carbon
     * }|null
     */
    private static function allowedTimeWindow(KhoaHocXeTap $lich, array $tolerance): ?array
    {
        $lichStart = self::toCarbon($lich->NgayBD);
        $lichEnd = self::toCarbon($lich->NgayKT);

        if ($lichStart === null || $lichEnd === null) {
            return null;
        }

        if (self::usesSecondPrecision($tolerance)) {
            return [
                'lichStart' => $lichStart,
                'lichEnd' => $lichEnd,
                'allowedStart' => $lichStart->copy()->subSeconds($tolerance['som_phut'] * 60),
                'allowedEnd' => $lichEnd->copy()->addSeconds($tolerance['muon_phut'] * 60),
            ];
        }

        $lichStartMinute = $lichStart->copy()->startOfMinute();
        $lichEndMinute = $lichEnd->copy()->startOfMinute();

        return [
            'lichStart' => $lichStartMinute,
            'lichEnd' => $lichEndMinute,
            'allowedStart' => $lichStartMinute->copy()->subMinutes($tolerance['som_phut']),
            'allowedEnd' => $lichEndMinute->copy()->addMinutes($tolerance['muon_phut']),
        ];
    }

    private static function usesSecondPrecision(array $tolerance): bool
    {
        return (bool) ($tolerance['do_theo_giay'] ?? false);
    }

    private static function normalizeSessionTime(Carbon $time, array $tolerance): Carbon
    {
        if (self::usesSecondPrecision($tolerance)) {
            return $time;
        }

        return $time->copy()->startOfMinute();
    }

    /**
     * @return array{valid: bool, message: string, matched: null, displaySchedule: null}
     */
    private static function invalid(string $message): array
    {
        return [
            'valid' => false,
            'message' => $message,
            'matched' => null,
            'displaySchedule' => null,
        ];
    }

    private static function normalizeMaGv(string $maGv): string
    {
        return mb_strtoupper(trim($maGv));
    }

    private static function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
