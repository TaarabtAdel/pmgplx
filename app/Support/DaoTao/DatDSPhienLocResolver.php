<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Lọc phiên DAT (DB + Đạt / lỗi) — dùng chung Quản lý phiên & xuất phụ lục. */
final class DatDSPhienLocResolver
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     query: Builder,
     *     violationsById: array<int, list<string>>,
     *     expectedPhanCongById: array<int, array{ma_giao_vien: string, bien_so_xe: string}>,
     *     filteredCount: int|null,
     *     canAnalyzeViolations: bool,
     * }
     */
    public static function resolve(array $filters): array
    {
        $canAnalyzeViolations = ($filters['ma_khoa_hoc'] ?? '') !== '';
        $query = DatDSPhienBoLoc::filteredQuery($filters);

        if (! $canAnalyzeViolations) {
            return [
                'query' => $query,
                'violationsById' => [],
                'expectedPhanCongById' => [],
                'filteredCount' => null,
                'canAnalyzeViolations' => false,
            ];
        }

        $validationSessions = (clone $query)->get();
        $expectedPhanCongById = [];
        $violationsById = DatDSPhienKiemTra::analyze($validationSessions, $expectedPhanCongById);
        $matchingIds = self::matchingIds($validationSessions, $violationsById, $filters, $canAnalyzeViolations);

        if ($matchingIds !== null) {
            if ($matchingIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('Id', $matchingIds);
            }
        }

        return [
            'query' => $query,
            'violationsById' => $violationsById,
            'expectedPhanCongById' => $expectedPhanCongById,
            'filteredCount' => $matchingIds === null ? $validationSessions->count() : count($matchingIds),
            'canAnalyzeViolations' => true,
        ];
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  array<int, list<string>>  $violationsById
     * @param  array<string, mixed>  $filters
     * @return list<int>|null
     */
    public static function matchingIds(
        Collection $sessions,
        array $violationsById,
        array $filters,
        bool $canAnalyzeViolations,
    ): ?array {
        if (! $canAnalyzeViolations) {
            return null;
        }

        if ($filters['loi'] === [] && $filters['dat'] === '') {
            return null;
        }

        $ids = [];

        foreach ($sessions as $session) {
            $id = (int) $session->Id;

            if ($filters['dat'] !== '' && ! DatDSPhienKiemTra::matchesDatFilter($violationsById, $id, $filters['dat'])) {
                continue;
            }

            if ($filters['loi'] !== [] && ! DatDSPhienKiemTra::matchesFilter($violationsById, $id, $filters['loi'])) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }
}