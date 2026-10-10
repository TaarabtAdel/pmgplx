<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\DaoTao\DatPhanCongXeThay;
use Carbon\Carbon;

class DatPhanCongXeThayResolver
{
    /**
     * @return array<string, list<array{
     *     id: int,
     *     bien_so_xe: string,
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
            $rows = DatPhanCongXeThay::query()
                ->whereIn('MaKhoaHoc', $chunk)
                ->orderBy('MaKhoaHoc')
                ->orderBy('MaGiaoVienGoc')
                ->orderBy('BienSoXeGoc')
                ->orderByDesc('TuNgay')
                ->orderByDesc('Id')
                ->get(['Id', 'MaKhoaHoc', 'MaGiaoVienGoc', 'BienSoXeGoc', 'BienSoXe', 'TuNgay', 'DenNgay']);

            foreach ($rows as $row) {
                $key = self::courseMainGvXeKey(
                    (string) $row->MaKhoaHoc,
                    (string) $row->MaGiaoVienGoc,
                    (string) $row->BienSoXeGoc
                );
                $grouped[$key] ??= [];
                $grouped[$key][] = [
                    'id' => (int) $row->Id,
                    'bien_so_xe' => DatPhanCongHocVienSaver::normalizeBienSo((string) $row->BienSoXe),
                    'tu_ngay' => self::formatDate($row->TuNgay),
                    'den_ngay' => self::formatDate($row->DenNgay),
                ];
            }
        }

        return $grouped;
    }

    /**
     * @param  array<string, list<array{id?: int, bien_so_xe: string, tu_ngay: string, den_ngay: string|null}>>  $groupedByKey
     * @return list<array{id?: int, bien_so_xe: string, tu_ngay: string, den_ngay: string|null}>
     */
    public static function substitutesForAssignment(DatPhanCongHocVien $assignment, array $groupedByKey): array
    {
        $key = self::courseMainGvXeKey(
            (string) ($assignment->MaKhoaHoc ?? ''),
            (string) ($assignment->MaGiaoVien ?? ''),
            (string) ($assignment->BienSoXe ?? '')
        );

        return $groupedByKey[$key] ?? [];
    }

    public static function courseMainGvXeKey(string $maKhoaHoc, string $maGiaoVienGoc, string $bienSoXeGoc): string
    {
        return trim($maKhoaHoc)
            .'|'.DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienGoc)
            .'|'.DatPhanCongHocVienSaver::normalizeBienSo($bienSoXeGoc);
    }

    /**
     * @param  list<array{id?: int, bien_so_xe: string, tu_ngay: string, den_ngay: string|null}>  $substitutes
     * @return array{
     *     bien_so_xe: string,
     *     tu_ngay: string|null,
     *     dang_doi_xe: bool
     * }
     */
    public static function resolveForDate(
        DatPhanCongHocVien $assignment,
        ?string $date,
        array $substitutes = []
    ): array {
        $primary = DatPhanCongHocVienSaver::normalizeBienSo((string) ($assignment->BienSoXe ?? ''));
        $normalizedDate = self::normalizeDate($date);

        if ($normalizedDate === null) {
            return [
                'bien_so_xe' => $primary,
                'tu_ngay' => null,
                'dang_doi_xe' => false,
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
                'bien_so_xe' => DatPhanCongHocVienSaver::normalizeBienSo((string) ($substitute['bien_so_xe'] ?? '')),
                'tu_ngay' => $tuNgay,
                'dang_doi_xe' => true,
            ];
        }

        return [
            'bien_so_xe' => $primary,
            'tu_ngay' => null,
            'dang_doi_xe' => false,
        ];
    }

    /**
     * Biển số phiên được phép (xe chính hoặc xe thay theo ngày + xe tự động trên phân công).
     *
     * @param  array<string, list<array{id?: int, bien_so_xe: string, tu_ngay: string, den_ngay: string|null}>>  $groupedByKey
     * @return list<string>
     */
    public static function allowedPlatesForAssignmentOnDate(
        DatPhanCongHocVien $assignment,
        ?string $date,
        array $groupedByKey
    ): array {
        $substitutes = self::substitutesForAssignment($assignment, $groupedByKey);
        $resolved = self::resolveForDate($assignment, $date, $substitutes);

        $allowed = [];
        if ($resolved['bien_so_xe'] !== '') {
            $allowed[] = $resolved['bien_so_xe'];
        }

        $tuDong = DatPhanCongHocVienSaver::normalizeBienSo((string) ($assignment->BienSoXeTuDong ?? ''));
        if ($tuDong !== '') {
            $allowed[] = $tuDong;
        }

        return array_values(array_unique($allowed));
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
