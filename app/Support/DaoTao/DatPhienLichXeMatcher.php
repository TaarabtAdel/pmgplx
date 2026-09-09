<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\PMGPLX\KhoaHoc;
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
     * @return array{
     *     valid: bool,
     *     message: string,
     *     matched: ?KhoaHocXeTap,
     *     displaySchedule: ?KhoaHocXeTap
     * }
     */
    public static function evaluate(DatDSPhien $session, Collection $scheduleRows): array
    {
        $maKh = trim((string) ($session->MaKhoaHoc ?? ''));
        $bienSo = LichExcelBienSo::normalize((string) ($session->BienSoXe ?? ''));
        $start = self::toCarbon($session->ThoiGianBatDauPhienHoc);
        $end = self::toCarbon($session->ThoiGianKetThucPhienHoc);

        if ($start === null || $end === null) {
            return [
                'valid' => false,
                'message' => 'Phiên thiếu thời gian bắt đầu hoặc kết thúc',
                'matched' => null,
                'displaySchedule' => null,
            ];
        }

        if ($bienSo === '') {
            return [
                'valid' => false,
                'message' => 'Phiên thiếu biển số xe',
                'matched' => null,
                'displaySchedule' => null,
            ];
        }

        if ($scheduleRows->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'Không có lịch xe tập cho mã khóa '.$maKh,
                'matched' => null,
                'displaySchedule' => null,
            ];
        }

        $samePlate = $scheduleRows->filter(
            fn (KhoaHocXeTap $lich): bool => LichExcelBienSo::normalize((string) ($lich->BienSoXe ?? '')) === $bienSo
        );

        if ($samePlate->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'Không có lịch xe '.$session->BienSoXe.' trong khóa',
                'matched' => null,
                'displaySchedule' => null,
            ];
        }

        $sessionDate = $start->toDateString();
        $sameDayAndPlate = $samePlate->filter(function (KhoaHocXeTap $lich) use ($sessionDate): bool {
            $lichStart = self::toCarbon($lich->NgayBD);

            return $lichStart !== null && $lichStart->toDateString() === $sessionDate;
        });

        if ($sameDayAndPlate->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'Không có lịch xe '.$session->BienSoXe.' trong ngày '.$start->format('d/m/Y'),
                'matched' => null,
                'displaySchedule' => $samePlate->first(),
            ];
        }

        $displaySchedule = $sameDayAndPlate->first();
        $timeMatchedLich = null;

        foreach ($sameDayAndPlate as $lich) {
            if (! self::sessionFitsSchedule($start, $end, $lich)) {
                continue;
            }

            $timeMatchedLich = $lich;

            if (self::teachersMatch($session, $lich)) {
                return [
                    'valid' => true,
                    'message' => '',
                    'matched' => $lich,
                    'displaySchedule' => $lich,
                ];
            }
        }

        if ($timeMatchedLich !== null) {
            $sessionMaGv = self::normalizeMaGv((string) ($session->MaGiaoVien ?? ''));
            $lichMaGv = self::normalizeMaGv((string) ($timeMatchedLich->MaGV ?? ''));

            return [
                'valid' => false,
                'message' => self::teacherMismatchMessage($sessionMaGv, $lichMaGv, $timeMatchedLich),
                'matched' => null,
                'displaySchedule' => $timeMatchedLich,
            ];
        }

        return [
            'valid' => false,
            'message' => 'Khung giờ phiên ngoài lịch xe tập (cùng ngày, biển số, khung giờ PMGPLX)',
            'matched' => null,
            'displaySchedule' => $displaySchedule,
        ];
    }

    public static function formatKhungGio(?KhoaHocXeTap $lich): ?string
    {
        if ($lich === null) {
            return null;
        }

        $lichStart = self::toCarbon($lich->NgayBD);
        $lichEnd = self::toCarbon($lich->NgayKT);
        if ($lichStart === null || $lichEnd === null) {
            return null;
        }

        $slot = self::resolveKhungGioFromTimes(
            $lichStart->format('H:i'),
            $lichEnd->format('H:i')
        );

        return $slot['start'].' → '.$slot['end'];
    }

    /**
     * @return array{start: string, end: string}
     */
    public static function resolveKhungGioFromTimes(string $startTime, string $endTime): array
    {
        $startTime = substr($startTime, 0, 5);
        $endTime = substr($endTime, 0, 5);

        foreach (KhoaHoc::$TIME_SLOTS as $slot) {
            if ($slot['start'] === $startTime && $slot['end'] === $endTime) {
                return $slot;
            }
        }

        return ['start' => $startTime, 'end' => $endTime];
    }

    /**
     * @return array{start: string, end: string}
     */
    public static function scheduleKhungGio(KhoaHocXeTap $lich): array
    {
        $lichStart = self::toCarbon($lich->NgayBD);
        $lichEnd = self::toCarbon($lich->NgayKT);

        if ($lichStart === null || $lichEnd === null) {
            return ['start' => '', 'end' => ''];
        }

        return self::resolveKhungGioFromTimes(
            $lichStart->format('H:i'),
            $lichEnd->format('H:i')
        );
    }

    private static function sessionFitsSchedule(Carbon $sessionStart, Carbon $sessionEnd, KhoaHocXeTap $lich): bool
    {
        $lichStart = self::toCarbon($lich->NgayBD);

        if ($lichStart === null) {
            return false;
        }

        if (! $sessionStart->isSameDay($lichStart)) {
            return false;
        }

        $slot = self::scheduleKhungGio($lich);
        if ($slot['start'] === '' || $slot['end'] === '') {
            return false;
        }

        $sessionStartMin = $sessionStart->copy()->startOfMinute();
        $sessionEndMin = $sessionEnd->copy()->startOfMinute();
        $slotStart = $sessionStart->copy()->setTimeFromTimeString($slot['start'].':00');
        $slotEnd = $sessionStart->copy()->setTimeFromTimeString($slot['end'].':00');

        // Phiên nằm trong khung lịch (cùng ngày, so theo phút): bat_dau >= bat_dau_khung, ket_thuc <= ket_thuc_khung
        return $sessionStartMin->gte($slotStart) && $sessionEndMin->lte($slotEnd);
    }

    private static function teachersMatch(DatDSPhien $session, KhoaHocXeTap $lich): bool
    {
        $sessionMaGv = self::normalizeMaGv((string) ($session->MaGiaoVien ?? ''));
        $lichMaGv = self::normalizeMaGv((string) ($lich->MaGV ?? ''));

        if ($sessionMaGv === '' || $lichMaGv === '') {
            return false;
        }

        return $sessionMaGv === $lichMaGv;
    }

    private static function normalizeMaGv(string $maGv): string
    {
        return mb_strtoupper(trim($maGv));
    }

    private static function teacherMismatchMessage(string $sessionMaGv, string $lichMaGv, KhoaHocXeTap $lich): string
    {
        if ($sessionMaGv === '' && $lichMaGv !== '') {
            return 'Phiên thiếu mã giáo viên (lịch xe: '.$lichMaGv.')';
        }

        if ($sessionMaGv !== '' && $lichMaGv === '') {
            return 'Lịch xe thiếu mã giáo viên (phiên: '.$sessionMaGv.')';
        }

        $lichTen = trim((string) ($lich->TenGV ?? ''));

        return 'Mã giáo viên không khớp lịch xe (phiên: '.$sessionMaGv
            .', lịch: '.$lichMaGv.($lichTen !== '' ? ' — '.$lichTen : '').')';
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
