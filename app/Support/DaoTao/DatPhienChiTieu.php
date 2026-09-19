<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use Illuminate\Support\Collection;

class DatPhienChiTieu
{
    /**
     * Chỉ tiêu từng phiên: tự động (LaTuDong), đêm (lịch Ban đêm + LaBanDem), cao tốc (lịch).
     *
     * @param  Collection<int, DatDSPhien>  $sessions
     * @return array<int, array{
     *     gio_tu_dong: float,
     *     km_tu_dong: float,
     *     gio_dem: float,
     *     km_dem: float,
     *     gio_cao_toc: float
     * }>
     */
    public static function forSessions(Collection $sessions, bool $fresh = true): array
    {
        $byId = [];

        foreach ($sessions as $session) {
            $id = (int) $session->Id;
            $isTuDong = (bool) ($session->LaTuDong ?? false);
            $gio = (float) ($session->ThoiGianThucHanhGio ?? 0);
            $km = (float) ($session->QuangDuongThucHanhKm ?? 0);

            $byId[$id] = [
                'gio_tu_dong' => $isTuDong ? $gio : 0.0,
                'km_tu_dong' => $isTuDong ? $km : 0.0,
                'gio_dem' => 0.0,
                'km_dem' => 0.0,
                'gio_cao_toc' => 0.0,
            ];
        }

        $resetCache = $fresh;
        $byCourse = $sessions->groupBy(
            fn (DatDSPhien $session): string => trim((string) ($session->MaKhoaHoc ?? ''))
        );

        foreach ($byCourse as $maKhoaHoc => $courseSessions) {
            $maKhoaHoc = (string) $maKhoaHoc;
            if ($maKhoaHoc === '') {
                continue;
            }

            $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc, resetCache: $resetCache);
            $resetCache = false;

            foreach ($courseSessions as $session) {
                $id = (int) $session->Id;
                $gio = (float) ($session->ThoiGianThucHanhGio ?? 0);
                $km = (float) ($session->QuangDuongThucHanhKm ?? 0);

                if (DatPhienLichXeMatcher::matchesGhiChu(
                    $session,
                    $scheduleRows,
                    ['ban đêm', 'ban dem'],
                    'LaBanDem'
                )) {
                    $byId[$id]['gio_dem'] = $gio;
                    $byId[$id]['km_dem'] = $km;
                }

                if (DatPhienLichXeMatcher::matchesGhiChu(
                    $session,
                    $scheduleRows,
                    ['cao tốc', 'cao toc']
                )) {
                    $byId[$id]['gio_cao_toc'] = $gio;
                }
            }
        }

        return $byId;
    }
}
