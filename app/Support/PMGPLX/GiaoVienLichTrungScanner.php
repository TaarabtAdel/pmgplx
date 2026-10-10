<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\DaoTao\DatPhanCongHocVienSaver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Dò trùng khung giờ giáo viên trên lịch PMGPLX (TH / lịch xe), tách biệt khỏi danh sách lịch lớn.
 */
class GiaoVienLichTrungScanner
{
    public const MODE_PROBE = 'probe';

    public const MODE_BY_GV = 'by_gv';

    public const MODE_BY_RANGE = 'by_range';

    public const MAX_RANGE_DAYS = 93;

    public const MAX_SLOTS = 12_000;

    public const MAX_PAIRS = 500;

    /**
     * @param  array{
     *     mode: string,
     *     ma_gv?: string,
     *     ma_kh?: string,
     *     tu_ngay?: string,
     *     den_ngay?: string,
     *     probe_ngay_bd?: string,
     *     probe_ngay_kt?: string,
     *     include_xe?: bool,
     *     loai_gv?: string,
     *     only_cross_khoa?: bool,
     *     trang_thai?: string|int|null,
     * }  $input
     * @return array{
     *     ok: bool,
     *     errors: list<string>,
     *     mode: string,
     *     filters: array<string, mixed>,
     *     pairs: list<array<string, mixed>>,
     *     probe_hits: list<array<string, mixed>>,
     *     stats: array<string, int|bool>
     * }
     */
    public static function scan(array $input): array
    {
        $mode = (string) ($input['mode'] ?? self::MODE_BY_GV);
        $includeXe = ! empty($input['include_xe']);
        $onlyCrossKhoa = ! isset($input['only_cross_khoa']) || (bool) $input['only_cross_khoa'];
        $loaiGv = trim((string) ($input['loai_gv'] ?? 'TH'));
        $trangThai = $input['trang_thai'] ?? '1';
        $maKh = trim((string) ($input['ma_kh'] ?? ''));
        $maGv = trim((string) ($input['ma_gv'] ?? ''));

        $errors = [];
        if (! in_array($mode, [self::MODE_PROBE, self::MODE_BY_GV, self::MODE_BY_RANGE], true)) {
            $errors[] = 'Chế độ kiểm tra không hợp lệ.';
        }

        if ($mode === self::MODE_PROBE) {
            if ($maGv === '') {
                $errors[] = 'Chọn giáo viên cần thử.';
            }
            if ($maKh === '') {
                $errors[] = 'Chọn khóa học của buổi cần thử.';
            }
            $probeBd = trim((string) ($input['probe_ngay_bd'] ?? ''));
            $probeKt = trim((string) ($input['probe_ngay_kt'] ?? ''));
            if ($probeBd === '' || $probeKt === '') {
                $errors[] = 'Nhập đầy đủ thời gian bắt đầu và kết thúc buổi thử.';
            } else {
                try {
                    $bd = Carbon::parse($probeBd);
                    $kt = Carbon::parse($probeKt);
                    if ($kt->lte($bd)) {
                        $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
                    }
                } catch (\Throwable) {
                    $errors[] = 'Thời gian buổi thử không hợp lệ.';
                }
            }

            if ($errors !== []) {
                return self::emptyResult($mode, $input, $errors);
            }

            $hits = GiaoVienLichCrossKhoaChecker::findAllConflicts(
                $maGv,
                $probeBd,
                $probeKt,
                $maKh,
                null,
                null,
                $includeXe
            );

            return [
                'ok' => true,
                'errors' => [],
                'mode' => $mode,
                'filters' => self::filtersSnapshot($input),
                'pairs' => [],
                'probe_hits' => $hits,
                'stats' => [
                    'probe_hit_count' => count($hits),
                    'slots_loaded' => 0,
                    'slots_truncated' => false,
                    'pairs_total' => 0,
                    'pairs_shown' => 0,
                ],
            ];
        }

        $tuNgay = trim((string) ($input['tu_ngay'] ?? ''));
        $denNgay = trim((string) ($input['den_ngay'] ?? ''));
        if ($tuNgay === '' || $denNgay === '') {
            $errors[] = 'Chọn khoảng ngày (từ ngày – đến ngày).';
        } else {
            try {
                $from = Carbon::parse($tuNgay)->startOfDay();
                $to = Carbon::parse($denNgay)->endOfDay();
                if ($to->lt($from)) {
                    $errors[] = 'Đến ngày phải sau hoặc bằng từ ngày.';
                } elseif ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
                    $errors[] = 'Khoảng ngày tối đa '.self::MAX_RANGE_DAYS.' ngày (thu hẹp bộ lọc nếu cần).';
                }
            } catch (\Throwable) {
                $errors[] = 'Khoảng ngày không hợp lệ.';
            }
        }

        if ($mode === self::MODE_BY_GV && $maGv === '') {
            $errors[] = 'Chọn giáo viên cần quét.';
        }

        if ($errors !== []) {
            return self::emptyResult($mode, $input, $errors);
        }

        $from = Carbon::parse($tuNgay)->startOfDay();
        $to = Carbon::parse($denNgay)->endOfDay();

        $maGvNorm = $maGv !== '' ? DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv) : '';

        $loaded = self::loadSlotsForScan(
            $from,
            $to,
            $maGvNorm !== '' ? $maGvNorm : null,
            $maKh !== '' ? $maKh : null,
            $includeXe,
            $loaiGv,
            $trangThai
        );

        $slots = $loaded['slots'];
        $pairs = self::findConflictPairs($slots, $onlyCrossKhoa, $maKh !== '' ? $maKh : null);
        $pairsTotal = count($pairs);
        if ($pairsTotal > self::MAX_PAIRS) {
            $pairs = array_slice($pairs, 0, self::MAX_PAIRS);
        }

        return [
            'ok' => true,
            'errors' => [],
            'mode' => $mode,
            'filters' => self::filtersSnapshot($input),
            'pairs' => $pairs,
            'probe_hits' => [],
            'stats' => [
                'probe_hit_count' => 0,
                'slots_loaded' => $slots->count(),
                'slots_truncated' => $loaded['truncated'],
                'pairs_total' => $pairsTotal,
                'pairs_shown' => count($pairs),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ok: bool, errors: list<string>, mode: string, filters: array<string, mixed>, pairs: list<array<string, mixed>>, probe_hits: list<array<string, mixed>>, stats: array<string, int|bool>}
     */
    private static function emptyResult(string $mode, array $input, array $errors): array
    {
        return [
            'ok' => false,
            'errors' => $errors,
            'mode' => $mode,
            'filters' => self::filtersSnapshot($input),
            'pairs' => [],
            'probe_hits' => [],
            'stats' => [
                'probe_hit_count' => 0,
                'slots_loaded' => 0,
                'slots_truncated' => false,
                'pairs_total' => 0,
                'pairs_shown' => 0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private static function filtersSnapshot(array $input): array
    {
        return [
            'mode' => (string) ($input['mode'] ?? ''),
            'ma_gv' => trim((string) ($input['ma_gv'] ?? '')),
            'ma_kh' => trim((string) ($input['ma_kh'] ?? '')),
            'tu_ngay' => trim((string) ($input['tu_ngay'] ?? '')),
            'den_ngay' => trim((string) ($input['den_ngay'] ?? '')),
            'probe_ngay_bd' => trim((string) ($input['probe_ngay_bd'] ?? '')),
            'probe_ngay_kt' => trim((string) ($input['probe_ngay_kt'] ?? '')),
            'include_xe' => ! empty($input['include_xe']),
            'loai_gv' => trim((string) ($input['loai_gv'] ?? 'TH')),
            'only_cross_khoa' => ! isset($input['only_cross_khoa']) || (bool) $input['only_cross_khoa'],
            'trang_thai' => (string) ($input['trang_thai'] ?? '1'),
        ];
    }

    /**
     * @return array{slots: Collection<int, array<string, mixed>>, truncated: bool}
     */
    private static function loadSlotsForScan(
        Carbon $from,
        Carbon $to,
        ?string $maGvNorm,
        ?string $scopeMaKh,
        bool $includeXe,
        string $loaiGv,
        mixed $trangThai
    ): array {
        $rangeStart = $from->format('Y-m-d H:i:s');
        $rangeEnd = $to->format('Y-m-d H:i:s');

        if ($scopeMaKh !== null && $scopeMaKh !== '') {
            $seed = self::fetchGvSlots($rangeStart, $rangeEnd, null, $scopeMaKh, $loaiGv, $trangThai, self::MAX_SLOTS + 1);
            if ($includeXe) {
                $seed = $seed->merge(self::fetchXeSlots($rangeStart, $rangeEnd, null, $scopeMaKh, $trangThai, self::MAX_SLOTS + 1));
            }

            $gvCodes = $seed->pluck('ma_gv_norm')->unique()->filter()->values();
            if ($maGvNorm !== null && $maGvNorm !== '') {
                $gvCodes = collect([$maGvNorm]);
            }

            if ($gvCodes->isEmpty()) {
                return ['slots' => collect(), 'truncated' => false];
            }

            $slots = collect();
            foreach ($gvCodes as $code) {
                $remaining = self::MAX_SLOTS + 1 - $slots->count();
                if ($remaining <= 0) {
                    break;
                }
                $slots = $slots->merge(self::fetchGvSlots($rangeStart, $rangeEnd, $code, null, $loaiGv, $trangThai, $remaining));
                if ($includeXe) {
                    $remaining = self::MAX_SLOTS + 1 - $slots->count();
                    if ($remaining > 0) {
                        $slots = $slots->merge(self::fetchXeSlots($rangeStart, $rangeEnd, $code, null, $trangThai, $remaining));
                    }
                }
            }
        } else {
            $slots = self::fetchGvSlots($rangeStart, $rangeEnd, $maGvNorm, null, $loaiGv, $trangThai, self::MAX_SLOTS + 1);
            if ($includeXe) {
                $remaining = self::MAX_SLOTS + 1 - $slots->count();
                if ($remaining > 0) {
                    $slots = $slots->merge(self::fetchXeSlots($rangeStart, $rangeEnd, $maGvNorm, null, $trangThai, $remaining));
                }
            }
        }

        $truncated = $slots->count() > self::MAX_SLOTS;
        if ($truncated) {
            $slots = $slots->take(self::MAX_SLOTS);
        }

        return [
            'slots' => $slots->sortBy('ngay_bd')->values(),
            'truncated' => $truncated,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function fetchGvSlots(
        string $rangeStart,
        string $rangeEnd,
        ?string $maGvNorm,
        ?string $maKh,
        string $loaiGv,
        mixed $trangThai,
        int $limit
    ): Collection {
        $query = KhoaHocGiaoVien::query()
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->where('NgayBD', '<=', $rangeEnd)
            ->where('NgayKT', '>=', $rangeStart);

        if ($maKh !== null && $maKh !== '') {
            $query->where('MaKH', $maKh);
        }

        if ($loaiGv !== '' && $loaiGv !== 'all') {
            $query->where('LoaiGV', $loaiGv);
        }

        if ($trangThai !== '' && $trangThai !== null) {
            $query->where('TrangThai', (int) $trangThai);
        }

        if ($maGvNorm !== null && $maGvNorm !== '') {
            self::applyMaGvFilter($query, 'MaGV', $maGvNorm);
        }

        $rows = $query
            ->orderBy('NgayBD')
            ->limit($limit)
            ->get(['MaLichLV', 'MaKH', 'MaGV', 'TenGV', 'LoaiGV', 'NgayBD', 'NgayKT']);

        return $rows->map(function (KhoaHocGiaoVien $row): array {
            $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGV);

            return self::slotFromGvRow($row, $norm);
        })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function fetchXeSlots(
        string $rangeStart,
        string $rangeEnd,
        ?string $maGvNorm,
        ?string $maKh,
        mixed $trangThai,
        int $limit
    ): Collection {
        $query = KhoaHocXeTap::query()
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->where('NgayBD', '<=', $rangeEnd)
            ->where('NgayKT', '>=', $rangeStart);

        if ($maKh !== null && $maKh !== '') {
            $query->where('MaKH', $maKh);
        }

        if ($trangThai !== '' && $trangThai !== null) {
            $query->where('TrangThai', (int) $trangThai);
        }

        if ($maGvNorm !== null && $maGvNorm !== '') {
            self::applyMaGvFilter($query, 'MaGV', $maGvNorm);
        }

        $rows = $query
            ->orderBy('NgayBD')
            ->limit($limit)
            ->get(['MaLichSD', 'MaKH', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        return $rows->map(function (KhoaHocXeTap $row): ?array {
            $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGV);
            if ($norm === '') {
                return null;
            }

            return self::slotFromXeRow($row, $norm);
        })->filter()->values();
    }

    /**
     * @return array<string, mixed>
     */
    private static function slotFromGvRow(KhoaHocGiaoVien $row, string $maGvNorm): array
    {
        return [
            'key' => 'gv:'.(int) $row->MaLichLV,
            'nguon' => 'gv',
            'ma_lich' => (int) $row->MaLichLV,
            'ma_kh' => trim((string) $row->MaKH),
            'ma_gv' => trim((string) $row->MaGV),
            'ma_gv_norm' => $maGvNorm,
            'ten_gv' => trim((string) ($row->TenGV ?? '')),
            'loai_gv' => trim((string) ($row->LoaiGV ?? '')),
            'bien_so_xe' => '',
            'ngay_bd' => Carbon::parse($row->NgayBD)->format('Y-m-d H:i:s'),
            'ngay_kt' => Carbon::parse($row->NgayKT ?? $row->NgayBD)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function slotFromXeRow(KhoaHocXeTap $row, string $maGvNorm): array
    {
        return [
            'key' => 'xe:'.(int) $row->MaLichSD,
            'nguon' => 'xe',
            'ma_lich' => (int) $row->MaLichSD,
            'ma_kh' => trim((string) $row->MaKH),
            'ma_gv' => trim((string) $row->MaGV),
            'ma_gv_norm' => $maGvNorm,
            'ten_gv' => trim((string) ($row->TenGV ?? '')),
            'loai_gv' => 'TH',
            'bien_so_xe' => trim((string) ($row->BienSoXe ?? '')),
            'ngay_bd' => Carbon::parse($row->NgayBD)->format('Y-m-d H:i:s'),
            'ngay_kt' => Carbon::parse($row->NgayKT ?? $row->NgayBD)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $slots
     * @return list<array<string, mixed>>
     */
    private static function findConflictPairs(Collection $slots, bool $onlyCrossKhoa, ?string $mustTouchMaKh): array
    {
        $pairs = [];
        $seen = [];

        foreach ($slots->groupBy('ma_gv_norm') as $maGvNorm => $group) {
            if ($maGvNorm === '') {
                continue;
            }
            $list = $group->values()->all();
            $count = count($list);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $list[$i];
                    $b = $list[$j];
                    if ($onlyCrossKhoa && $a['ma_kh'] === $b['ma_kh']) {
                        continue;
                    }
                    if (! self::timesOverlap($a['ngay_bd'], $a['ngay_kt'], $b['ngay_bd'], $b['ngay_kt'])) {
                        continue;
                    }
                    if ($mustTouchMaKh !== null && $mustTouchMaKh !== ''
                        && $a['ma_kh'] !== $mustTouchMaKh && $b['ma_kh'] !== $mustTouchMaKh) {
                        continue;
                    }

                    $pairKey = self::pairKey($a['key'], $b['key']);
                    if (isset($seen[$pairKey])) {
                        continue;
                    }
                    $seen[$pairKey] = true;

                    $pairs[] = [
                        'ma_gv_norm' => $maGvNorm,
                        'ten_gv' => $a['ten_gv'] !== '' ? $a['ten_gv'] : ($b['ten_gv'] ?? ''),
                        'slot_a' => $a,
                        'slot_b' => $b,
                        'overlap_label' => self::overlapLabel($a, $b),
                    ];
                }
            }
        }

        usort($pairs, fn (array $x, array $y): int => strcmp($x['slot_a']['ngay_bd'], $y['slot_a']['ngay_bd']));

        return $pairs;
    }

    private static function pairKey(string $a, string $b): string
    {
        return $a < $b ? $a.'|'.$b : $b.'|'.$a;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private static function applyMaGvFilter($query, string $column, string $maGvNorm): void
    {
        $variants = array_values(array_unique(array_filter([
            $maGvNorm,
            ltrim($maGvNorm, '0'),
        ])));

        $query->whereIn($column, $variants);
    }

    private static function timesOverlap(string $bdA, string $ktA, string $bdB, string $ktB): bool
    {
        try {
            $a0 = Carbon::parse($bdA);
            $a1 = Carbon::parse($ktA);
            $b0 = Carbon::parse($bdB);
            $b1 = Carbon::parse($ktB);
        } catch (\Throwable) {
            return false;
        }

        return $a0->lt($b1) && $a1->gt($b0);
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private static function overlapLabel(array $a, array $b): string
    {
        try {
            $start = Carbon::parse(max($a['ngay_bd'], $b['ngay_bd']));
            $end = Carbon::parse(min($a['ngay_kt'], $b['ngay_kt']));

            return $start->format('d/m/Y H:i').' – '.$end->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }
}
