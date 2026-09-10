<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Support\PMGPLX\LichExcelBienSo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DatPhienLichXeThongKe
{
    /**
     * Đếm phiên DAT và tổng giờ theo từng dòng lịch xe (cùng logic ghép với DatPhienLichXeMatcher).
     *
     * @param  Collection<int, object>  $lichRows
     * @return array<int, array{so_phien: int, tong_gio: float}>
     */
    public static function forScheduleRows(Collection $lichRows): array
    {
        if ($lichRows->isEmpty()) {
            return [];
        }

        $stats = [];
        foreach ($lichRows as $row) {
            $maLich = (int) ($row->MaLichSD ?? 0);
            if ($maLich <= 0) {
                continue;
            }
            $stats[$maLich] = ['so_phien' => 0, 'tong_gio' => 0.0];
        }

        if ($stats === []) {
            return [];
        }

        foreach ($lichRows->groupBy(fn (object $row): string => trim((string) ($row->MaKH ?? ''))) as $maKhoaHoc => $group) {
            if ($maKhoaHoc === '') {
                continue;
            }

            $courseSchedules = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc);
            if ($courseSchedules->isEmpty()) {
                continue;
            }

            $dates = $group
                ->map(fn (object $row): ?string => self::scheduleDate($row))
                ->filter()
                ->unique()
                ->values();

            if ($dates->isEmpty()) {
                continue;
            }

            $sessions = DatDSPhien::query()
                ->where('MaKhoaHoc', $maKhoaHoc)
                ->whereNotNull('ThoiGianBatDauPhienHoc')
                ->where(function ($query) use ($dates): void {
                    foreach ($dates as $date) {
                        $query->orWhereDate('ThoiGianBatDauPhienHoc', $date);
                    }
                })
                ->get();

            foreach ($sessions as $session) {
                $start = self::toCarbon($session->ThoiGianBatDauPhienHoc);
                if ($start === null) {
                    continue;
                }

                $maGiaoVien = mb_strtoupper(trim((string) ($session->MaGiaoVien ?? '')));
                $bienSo = LichExcelBienSo::normalize((string) ($session->BienSoXe ?? ''));
                if ($maGiaoVien === '' || $bienSo === '') {
                    continue;
                }

                $end = self::toCarbon($session->ThoiGianKetThucPhienHoc);
                $matched = DatPhienLichXeMatcher::findSchedule(
                    $courseSchedules,
                    $maKhoaHoc,
                    $maGiaoVien,
                    $bienSo,
                    $start,
                    $end
                );

                if ($matched === null) {
                    continue;
                }

                $maLich = (int) $matched->MaLichSD;
                if (! isset($stats[$maLich])) {
                    continue;
                }

                $stats[$maLich]['so_phien']++;
                $stats[$maLich]['tong_gio'] += self::sessionHours($session);
            }
        }

        return $stats;
    }

    private static function sessionHours(DatDSPhien $session): float
    {
        if ($session->ThoiGianThucHanhGio !== null && $session->ThoiGianThucHanhGio !== '') {
            return max(0.0, (float) $session->ThoiGianThucHanhGio);
        }

        $start = self::toCarbon($session->ThoiGianBatDauPhienHoc);
        $end = self::toCarbon($session->ThoiGianKetThucPhienHoc);
        if ($start === null || $end === null) {
            return 0.0;
        }

        return max(0.0, $start->diffInRealMinutes($end) / 60);
    }

    private static function scheduleDate(object $row): ?string
    {
        $ngayBD = $row->NgayBD ?? null;
        if ($ngayBD === null || $ngayBD === '') {
            return null;
        }

        if ($ngayBD instanceof Carbon) {
            return $ngayBD->toDateString();
        }

        return Carbon::parse($ngayBD)->toDateString();
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
