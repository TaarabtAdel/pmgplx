<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
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
            // ->where('IsKhoaHocXeTap', 0)
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

        $displaySchedule = $samePlate->first();

        foreach ($samePlate as $lich) {
            $lichStart = self::toCarbon($lich->NgayBD);
            $lichEnd = self::toCarbon($lich->NgayKT);

            if ($lichStart === null || $lichEnd === null) {
                continue;
            }

            if ($start->gte($lichStart) && $end->lte($lichEnd)) {
                return [
                    'valid' => true,
                    'message' => '',
                    'matched' => $lich,
                    'displaySchedule' => $lich,
                ];
            }
        }

        return [
            'valid' => false,
            'message' => 'Khung giờ phiên nằm ngoài lịch xe tập (cùng khóa và biển số)',
            'matched' => null,
            'displaySchedule' => $displaySchedule,
        ];
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
