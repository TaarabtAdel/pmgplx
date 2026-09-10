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
        $lichStart = self::toCarbon($lich->NgayBD);
        $lichEnd = self::toCarbon($lich->NgayKT);

        if ($lichStart === null || $lichEnd === null) {
            return false;
        }

        $sessionStartMinute = $sessionStart->copy()->startOfMinute();
        $sessionEndMinute = $sessionEnd->copy()->startOfMinute();
        $allowedStart = $lichStart->copy()->startOfMinute()->subMinutes($tolerance['som_phut']);
        $allowedEnd = $lichEnd->copy()->startOfMinute()->addMinutes($tolerance['muon_phut']);

        return $sessionStartMinute->gte($allowedStart)
            && $sessionEndMinute->lte($allowedEnd);
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

        $lichStart = self::toCarbon($lich->NgayBD);
        $lichEnd = self::toCarbon($lich->NgayKT);

        if ($lichStart === null || $lichEnd === null) {
            return 'Lịch xe thiếu thời gian bắt đầu hoặc kết thúc';
        }

        $sessionStartMinute = $sessionStart->copy()->startOfMinute();
        $sessionEndMinute = $sessionEnd->copy()->startOfMinute();
        $allowedStart = $lichStart->copy()->startOfMinute()->subMinutes($tolerance['som_phut']);
        $allowedEnd = $lichEnd->copy()->startOfMinute()->addMinutes($tolerance['muon_phut']);

        $issues = [];

        if ($sessionStartMinute->lt($allowedStart)) {
            $issues[] = 'bắt đầu phiên '.$sessionStartMinute->format('H:i')
                .' sớm hơn lịch '.$lichStart->copy()->startOfMinute()->format('H:i');
        }

        if ($sessionEndMinute->gt($allowedEnd)) {
            $issues[] = 'kết thúc phiên '.$sessionEndMinute->format('H:i')
                .' muộn hơn lịch '.$lichEnd->copy()->startOfMinute()->format('H:i');
        }

        if ($issues === []) {
            return null;
        }

        return 'Lệch thời gian so với lịch xe ('.implode('; ', $issues).')';
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
