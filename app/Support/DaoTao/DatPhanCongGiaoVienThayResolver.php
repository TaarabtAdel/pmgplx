<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongGiaoVienThay;
use App\Models\DaoTao\DatPhanCongHocVien;
use Carbon\Carbon;

class DatPhanCongGiaoVienThayResolver
{
    /**
     * @return array<string, list<array{
     *     id: int,
     *     ma_giao_vien: string,
     *     tu_ngay: string,
     *     den_ngay: string|null
     * }>>
     */
    public static function groupedForCourses(array $maKhoaHocList): array
    {
        $maKhoaHocList = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $maKhoaHocList
        ))));

        if ($maKhoaHocList === []) {
            return [];
        }

        $grouped = [];

        foreach (array_chunk($maKhoaHocList, 2000) as $chunk) {
            $rows = DatPhanCongGiaoVienThay::query()
                ->whereIn('MaKhoaHoc', $chunk)
                ->orderBy('MaKhoaHoc')
                ->orderBy('MaGiaoVienGoc')
                ->orderByDesc('TuNgay')
                ->orderByDesc('Id')
                ->get(['Id', 'MaKhoaHoc', 'MaGiaoVienGoc', 'MaGiaoVien', 'TuNgay', 'DenNgay', 'TuGio', 'DenGio']);

            foreach ($rows as $row) {
                $key = self::courseMainGvKey(
                    (string) $row->MaKhoaHoc,
                    (string) $row->MaGiaoVienGoc
                );
                $grouped[$key] ??= [];
                $grouped[$key][] = [
                    'id' => (int) $row->Id,
                    'ma_giao_vien' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGiaoVien),
                    'tu_ngay' => self::formatDate($row->TuNgay),
                    'den_ngay' => self::formatDate($row->DenNgay),
                    'tu_gio' => DatPhanCongGiaoVienThay::gioHienThi($row->TuGio),
                    'den_gio' => DatPhanCongGiaoVienThay::gioHienThi($row->DenGio),
                ];
            }
        }

        return $grouped;
    }

    /**
     * @param  array<string, list<array{id?: int, ma_giao_vien: string, tu_ngay: string, den_ngay: string|null}>>  $groupedByKey
     * @return list<array{id?: int, ma_giao_vien: string, tu_ngay: string, den_ngay: string|null}>
     */
    public static function substitutesForAssignment(DatPhanCongHocVien $assignment, array $groupedByKey): array
    {
        $key = self::courseMainGvKey(
            (string) ($assignment->MaKhoaHoc ?? ''),
            (string) ($assignment->MaGiaoVien ?? '')
        );

        return $groupedByKey[$key] ?? [];
    }

    public static function courseMainGvKey(string $maKhoaHoc, string $maGiaoVienGoc): string
    {
        return trim($maKhoaHoc).'|'.DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienGoc);
    }

    /**
     * @param  list<array{id?: int, ma_giao_vien: string, tu_ngay: string, den_ngay: string|null}>  $substitutes
     * @return array{
     *     ma_giao_vien: string,
     *     tu_ngay: string|null,
     *     dang_day_thay: bool
     * }
     */
    public static function resolveForDate(
        DatPhanCongHocVien $assignment,
        ?string $date,
        array $substitutes = [],
        ?string $gio = null
    ): array {
        $primary = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) ($assignment->MaGiaoVien ?? ''));
        $normalizedDate = self::normalizeDate($date);

        if ($normalizedDate === null) {
            return [
                'ma_giao_vien' => $primary,
                'tu_ngay' => null,
                'dang_day_thay' => false,
            ];
        }

        foreach ($substitutes as $substitute) {
            $tuNgay = self::normalizeDate($substitute['tu_ngay'] ?? null);
            if ($tuNgay === null) {
                continue;
            }

            $denNgay = self::normalizeDate($substitute['den_ngay'] ?? null);
            if ($normalizedDate < $tuNgay) {
                continue;
            }

            if ($denNgay !== null && $normalizedDate > $denNgay) {
                continue;
            }

            if (! self::gioTrongKhung($gio, $substitute['tu_gio'] ?? null, $substitute['den_gio'] ?? null)) {
                continue;
            }

            return [
                'ma_giao_vien' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) ($substitute['ma_giao_vien'] ?? '')),
                'tu_ngay' => $tuNgay,
                'dang_day_thay' => true,
            ];
        }

        return [
            'ma_giao_vien' => $primary,
            'tu_ngay' => null,
            'dang_day_thay' => false,
        ];
    }

    /**
     * @param  list<array{id?: int, ma_giao_vien: string, tu_ngay: string, den_ngay: string|null}>  $substitutes
     */
    public static function countForList(array $substitutes): int
    {
        return count($substitutes);
    }

    /**
     * Buổi/phiên thuộc khai báo khi giờ bắt đầu nằm trong [TuGio, DenGio).
     * Không khai báo giờ = cả ngày; thiếu một đầu thì tính từ 00:00 / đến hết ngày.
     */
    public static function gioTrongKhung(?string $gio, ?string $tuGio, ?string $denGio): bool
    {
        $tuGio = DatPhanCongGiaoVienThay::gioHienThi($tuGio);
        $denGio = DatPhanCongGiaoVienThay::gioHienThi($denGio);
        if ($tuGio === null && $denGio === null) {
            return true;
        }

        $gio = DatPhanCongGiaoVienThay::gioHienThi($gio);
        if ($gio === null) {
            return false;
        }

        return $gio >= ($tuGio ?? '00:00') && $gio < ($denGio ?? '24:00');
    }

    public static function khungGioGiaoNhau(?string $tuA, ?string $denA, ?string $tuB, ?string $denB): bool
    {
        $tuA = DatPhanCongGiaoVienThay::gioHienThi($tuA) ?? '00:00';
        $denA = DatPhanCongGiaoVienThay::gioHienThi($denA) ?? '24:00';
        $tuB = DatPhanCongGiaoVienThay::gioHienThi($tuB) ?? '00:00';
        $denB = DatPhanCongGiaoVienThay::gioHienThi($denB) ?? '24:00';

        return $tuA < $denB && $tuB < $denA;
    }

    public static function nhanKhungGio(?string $tuGio, ?string $denGio): string
    {
        $tuGio = DatPhanCongGiaoVienThay::gioHienThi($tuGio);
        $denGio = DatPhanCongGiaoVienThay::gioHienThi($denGio);
        if ($tuGio === null && $denGio === null) {
            return 'cả ngày';
        }

        return ($tuGio ?? '00:00').' – '.($denGio ?? '24:00');
    }

    public static function gioTuThoiDiem(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return ($value instanceof Carbon ? $value : Carbon::parse($value))->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return ($value instanceof Carbon ? $value : Carbon::parse($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function formatDate(mixed $value): ?string
    {
        return self::normalizeDate($value);
    }
}
