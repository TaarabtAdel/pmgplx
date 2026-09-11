<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DatTheoDoiDat
{
    /**
     * @return array{ma_khoa_hoc: string, ngay: string}
     */
    public static function parseFilters(Request $request): array
    {
        return [
            'ma_khoa_hoc' => trim((string) $request->input('ma_khoa_hoc', '')),
            'ngay' => self::normalizeDate((string) $request->input('ngay', '')),
        ];
    }

    /**
     * @return list<array{
     *     ma_giao_vien: string,
     *     ho_ten_giao_vien: string,
     *     bien_so_xe: string,
     *     tong_km_ngay: string,
     *     cung_duong_lich: string,
     *     cung_duong_gv: string,
     *     gio_gvth: string,
     *     km_gvth: string,
     *     gio_trong_ngay: string,
     *     km_trong_ngay: string,
     *     students: list<array{
     *         stt: int,
     *         ma_hoc_vien: string,
     *         ho_ten: string,
     *         gio_tu_dong: string,
     *         km_may_chu: string,
     *         chay_dem: string,
     *         so_km: string
     *     }>
     * }>
     */
    public static function buildGroups(string $maKhoaHoc, string $ngay): array
    {
        if ($maKhoaHoc === '' || $ngay === '') {
            return [];
        }

        $sessions = DatDSPhien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->whereDate('ThoiGianBatDauPhienHoc', $ngay)
            ->orderBy('HoTenGiaoVien')
            ->orderBy('BienSoXe')
            ->orderBy('HoTenHocVien')
            ->get();

        if ($sessions->isEmpty()) {
            return [];
        }

        /** @var array<string, Collection<int, DatDSPhien>> $byGroup */
        $byGroup = $sessions->groupBy(
            fn (DatDSPhien $session): string => self::groupKey(
                (string) ($session->MaGiaoVien ?? ''),
                (string) ($session->BienSoXe ?? '')
            )
        );

        $groups = [];

        foreach ($byGroup as $groupSessions) {
            $first = $groupSessions->first();
            $groupGio = (float) $groupSessions->sum(fn (DatDSPhien $s): float => (float) ($s->ThoiGianThucHanhGio ?? 0));
            $groupKm = (float) $groupSessions->sum(fn (DatDSPhien $s): float => (float) ($s->QuangDuongThucHanhKm ?? 0));

            $students = [];
            $stt = 0;

            foreach ($groupSessions->groupBy('MaHocVien') as $studentSessions) {
                $student = $studentSessions->first();
                if ($student === null) {
                    continue;
                }

                $stt++;
                $gioTuDong = (float) $studentSessions->sum(fn (DatDSPhien $s): float => (float) ($s->ThoiGianLaiXeSoTuDong ?? 0));
                $kmMayChu = (float) $studentSessions->sum(fn (DatDSPhien $s): float => (float) ($s->QuangDuongThucHanhKm ?? 0));
                $chayDem = (float) $studentSessions->sum(fn (DatDSPhien $s): float => (float) ($s->ThoiGianLaiBanDemGio ?? 0));

                $students[] = [
                    'stt' => $stt,
                    'ma_hoc_vien' => (string) ($student->MaHocVien ?? ''),
                    'ho_ten' => trim((string) ($student->HoTenHocVien ?? '')) ?: (string) ($student->MaHocVien ?? ''),
                    'gio_tu_dong' => self::formatGio($gioTuDong),
                    'km_may_chu' => self::formatKm($kmMayChu),
                    'chay_dem' => self::formatGio($chayDem),
                    'so_km' => self::placeholder(),
                ];
            }

            if ($students === []) {
                continue;
            }

            $groups[] = [
                'ma_giao_vien' => (string) ($first->MaGiaoVien ?? ''),
                'ho_ten_giao_vien' => trim((string) ($first->HoTenGiaoVien ?? '')) ?: (string) ($first->MaGiaoVien ?? ''),
                'bien_so_xe' => trim((string) ($first->BienSoXe ?? '')) ?: '—',
                'tong_km_ngay' => self::formatKm($groupKm),
                'cung_duong_lich' => self::placeholder(),
                'cung_duong_gv' => self::placeholder(),
                'gio_gvth' => self::formatGio($groupGio),
                'km_gvth' => self::formatKm($groupKm),
                'gio_trong_ngay' => self::placeholder(),
                'km_trong_ngay' => self::placeholder(),
                'students' => $students,
            ];
        }

        return $groups;
    }

    public static function formatNgayHeading(string $ngay): string
    {
        if ($ngay === '') {
            return '—';
        }

        return Carbon::parse($ngay)->format('d/m/y');
    }

    public static function formatGio(float|int|string|null $hours): string
    {
        if ($hours === null || $hours === '' || (float) $hours <= 0) {
            return self::placeholder();
        }

        $totalMinutes = (int) round((float) $hours * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        if ($h === 0) {
            return '0h'.$m;
        }

        if ($m === 0) {
            return $h.'h';
        }

        return $h.'h'.$m;
    }

    public static function formatKm(float|int|string|null $km): string
    {
        if ($km === null || $km === '' || (float) $km <= 0) {
            return self::placeholder();
        }

        return rtrim(rtrim(number_format((float) $km, 2, ',', ''), '0'), ',');
    }

    public static function placeholder(): string
    {
        return '—';
    }

    private static function groupKey(string $maGiaoVien, string $bienSoXe): string
    {
        return $maGiaoVien.'|'.DatXeSoTuDong::normalizeBienSo($bienSoXe);
    }

    private static function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }
}
