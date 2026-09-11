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
                ->get(['Id', 'MaKhoaHoc', 'MaGiaoVienGoc', 'MaGiaoVien', 'TuNgay', 'DenNgay']);

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
        array $substitutes = []
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
