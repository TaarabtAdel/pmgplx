<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatDSPhien;
use Illuminate\Database\Eloquent\Builder;

class DatHocVienTongHop
{
    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    public static function validSessionIdsFromFilters(array $filters): array
    {
        $sessions = DatDSPhienBoLoc::filteredQuery($filters, orderBy: null)->get();
        $violationsById = DatDSPhienKiemTra::analyze($sessions);

        $ids = [];
        foreach ($sessions as $session) {
            if (DatDSPhienKiemTra::datPhien($violationsById, (int) $session->Id)) {
                $ids[] = (int) $session->Id;
            }
        }

        return $ids;
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
            AND SUM(COALESCE({$sessionTable}.ThoiGianLaiBanDemGio, 0)) >= (
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
