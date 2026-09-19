<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatDSPhien;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DatHocVienTongHop
{
    public static function validSessionsFromFilters(array $filters): Collection
    {
        $sessions = DatDSPhienBoLoc::filteredQuery($filters, orderBy: null)->get();
        $violationsById = DatDSPhienKiemTra::analyze($sessions);

        return $sessions->filter(function (DatDSPhien $session) use ($violationsById): bool {
            return DatDSPhienKiemTra::datPhien($violationsById, (int) $session->Id);
        })->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    public static function validSessionIdsFromFilters(array $filters): array
    {
        return self::validSessionsFromFilters($filters)
            ->map(fn (DatDSPhien $session): int => (int) $session->Id)
            ->all();
    }

    /**
     * Giờ ban đêm theo từng học viên: khớp lịch xe tập (khóa · GV · xe · ngày,
     * ghi chú Ban đêm) và LaBanDem — cùng quy tắc theo dõi DAT.
     *
     * @param  Collection<int, DatDSPhien>  $sessions
     * @return array<string, float>
     */
    public static function gioBanDemByHocVien(Collection $sessions): array
    {
        $hours = [];
        $byCourse = $sessions->groupBy(
            fn (DatDSPhien $session): string => trim((string) ($session->MaKhoaHoc ?? ''))
        );

        foreach ($byCourse as $maKhoaHoc => $courseSessions) {
            $maKhoaHoc = (string) $maKhoaHoc;
            if ($maKhoaHoc === '') {
                continue;
            }

            $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc, resetCache: false);

            foreach ($courseSessions as $session) {
                if (! DatPhienLichXeMatcher::matchesGhiChu(
                    $session,
                    $scheduleRows,
                    ['ban đêm', 'ban dem'],
                    'LaBanDem'
                )) {
                    continue;
                }

                $key = self::hocVienRowKey($session);
                $hours[$key] = ($hours[$key] ?? 0.0) + (float) ($session->ThoiGianThucHanhGio ?? 0);
            }
        }

        return $hours;
    }

    public static function hocVienRowKey(object $row): string
    {
        return trim((string) ($row->MaHocVien ?? ''))."\0"
            .trim((string) ($row->MaKhoaHoc ?? ''))."\0"
            .(string) ($row->LoaiKhoaHoc ?? '');
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<string, DatDieuKienDat>  $dieuKienByHang
     * @return Collection<int, object>
     */
    public static function filterDatChuongTrinh(Collection $rows, string $datCt, Collection $dieuKienByHang): Collection
    {
        if (! in_array($datCt, ['dat', 'chua_dat'], true)) {
            return $rows;
        }

        return $rows->filter(function (object $row) use ($datCt, $dieuKienByHang): bool {
            $dieuKien = $dieuKienByHang->get($row->LoaiKhoaHoc ?? '');
            $pass = self::datChuongTrinh($row, $dieuKien);
            if ($pass === null) {
                return false;
            }

            return $datCt === 'dat' ? $pass : ! $pass;
        })->values();
    }

    /**
     * @param  list<int>  $sessionIds
     */
    public static function restrictToSessionIds(Builder $query, array $sessionIds): void
    {
        if ($sessionIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $sub) use ($sessionIds): void {
            foreach (array_chunk($sessionIds, 2000) as $chunk) {
                $sub->orWhereIn('Id', $chunk);
            }
        });
    }

    /**
     * @param  object  $row  Dòng tổng hợp (TongGioHoc, TongQuangDuongKm, …)
     */
    public static function datChuongTrinh(object $row, ?DatDieuKienDat $dieuKien): ?bool
    {
        if ($dieuKien === null) {
            return null;
        }

        return (float) ($row->TongGioHoc ?? 0) >= (float) $dieuKien->SoGioHoc
            && (float) ($row->TongQuangDuongKm ?? 0) >= (float) $dieuKien->TongQuangDuongKm
            && (float) ($row->TongBanDemGio ?? 0) >= (float) $dieuKien->TapLaiBanDemGio
            && (float) ($row->TongXeSoTuDongGio ?? 0) >= (float) $dieuKien->XeSoTuDongGio;
    }

    public static function formatNumber(float|int|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.');
    }

    public static function applyDatCtFilter(Builder $query, string $datCt): void
    {
        if (! in_array($datCt, ['dat', 'chua_dat'], true)) {
            return;
        }

        $sessionTable = (new DatDSPhien)->getTable();
        $dieuKienTable = (new DatDieuKienDat)->getTable();
        $tongXeSoTuDongSql = DatXeSoTuDong::sqlSumGioTuDong($sessionTable);
        $tongBanDemSql = DatXeSoTuDong::sqlSumGioBanDem($sessionTable);

        $passSql = "
            EXISTS (SELECT 1 FROM {$dieuKienTable} dk WHERE dk.Hang = {$sessionTable}.LoaiKhoaHoc)
            AND SUM(COALESCE({$sessionTable}.ThoiGianThucHanhGio, 0)) >= (
                SELECT TOP 1 dk.SoGioHoc FROM {$dieuKienTable} dk
                WHERE dk.Hang = {$sessionTable}.LoaiKhoaHoc ORDER BY dk.ThuTu
            )
            AND SUM(COALESCE({$sessionTable}.QuangDuongThucHanhKm, 0)) >= (
                SELECT TOP 1 dk.TongQuangDuongKm FROM {$dieuKienTable} dk
                WHERE dk.Hang = {$sessionTable}.LoaiKhoaHoc ORDER BY dk.ThuTu
            )
            AND {$tongBanDemSql} >= (
                SELECT TOP 1 dk.TapLaiBanDemGio FROM {$dieuKienTable} dk
                WHERE dk.Hang = {$sessionTable}.LoaiKhoaHoc ORDER BY dk.ThuTu
            )
            AND {$tongXeSoTuDongSql} >= (
                SELECT TOP 1 dk.XeSoTuDongGio FROM {$dieuKienTable} dk
                WHERE dk.Hang = {$sessionTable}.LoaiKhoaHoc ORDER BY dk.ThuTu
            )
        ";

        if ($datCt === 'dat') {
            $query->havingRaw($passSql);
        } else {
            $query->havingRaw("EXISTS (SELECT 1 FROM {$dieuKienTable} dk WHERE dk.Hang = {$sessionTable}.LoaiKhoaHoc) AND NOT ({$passSql})");
        }
    }
}
