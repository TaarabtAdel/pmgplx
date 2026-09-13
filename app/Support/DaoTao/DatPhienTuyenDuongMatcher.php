<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Support\PMGPLX\LichExcelDiaDiem;
use Illuminate\Support\Collection;

class DatPhienTuyenDuongMatcher
{
    public static function isRouteSchedule(?object $schedule): bool
    {
        if ($schedule === null) {
            return false;
        }

        $diaDiem = trim((string) ($schedule->DiaDiem ?? ''));

        if ($diaDiem === '') {
            return false;
        }

        return $diaDiem === LichExcelDiaDiem::TUYEN_DUONG
            || mb_stripos($diaDiem, 'tuyến đường') !== false;
    }

    /**
     * @param  Collection<int, \App\Models\PMGPLX\KhoaHocXeTap>  $scheduleRows
     * @return array{dat: bool, message: string}
     */
    public static function evaluate(DatDSPhien $session, Collection $scheduleRows): array
    {
        $lichEval = DatPhienLichXeMatcher::evaluate($session, $scheduleRows);

        if (! $lichEval['valid']) {
            return [
                'dat' => false,
                'message' => (string) ($lichEval['message'] ?? 'Không khớp lịch xe'),
            ];
        }

        if (! self::isRouteSchedule($lichEval['matched'] ?? null)) {
            return [
                'dat' => false,
                'message' => 'Lịch xe không phải tuyến đường',
            ];
        }

        return [
            'dat' => true,
            'message' => '',
        ];
    }

    public static function datTuyenDuong(DatDSPhien $session, Collection $scheduleRows): bool
    {
        return self::evaluate($session, $scheduleRows)['dat'];
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @return list<int>
     */
    public static function matchingIds(Collection $sessions, string $datFilter): array
    {
        $ids = [];

        foreach ($sessions->groupBy(fn (DatDSPhien $session): string => trim((string) ($session->MaKhoaHoc ?? ''))) as $maKhoaHoc => $courseSessions) {
            if ($maKhoaHoc === '') {
                continue;
            }

            $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc);

            foreach ($courseSessions as $session) {
                $dat = self::datTuyenDuong($session, $scheduleRows);

                if ($datFilter === 'dat' && ! $dat) {
                    continue;
                }

                if ($datFilter === 'chua_dat' && $dat) {
                    continue;
                }

                $ids[] = (int) $session->Id;
            }
        }

        return $ids;
    }
}
