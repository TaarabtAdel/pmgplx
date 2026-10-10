<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\DaoTao\DatPhanCongHocVienSaver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Giáo viên không thể dạy cùng khung giờ (NgayBD–NgayKT) ở hai khóa học (MaKH) khác nhau.
 */
class GiaoVienLichCrossKhoaChecker
{
    /**
     * @return array{
     *     ma_kh: string,
     *     ngay_bd: string,
     *     ngay_kt: string,
     *     nguon: 'gv'|'xe',
     *     ma_lich: int
     * }|null
     */
    public static function findConflict(
        string $maGv,
        string $ngayBD,
        string $ngayKT,
        string $forMaKh,
        ?int $ignoreMaLichLv = null,
        ?int $ignoreMaLichSd = null
    ): ?array {
        $maGvNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv);
        if ($maGvNorm === '') {
            return null;
        }

        $forMaKh = trim($forMaKh);
        if ($forMaKh === '') {
            return null;
        }

        try {
            $bd = Carbon::parse($ngayBD);
            $kt = Carbon::parse($ngayKT);
        } catch (\Throwable) {
            return null;
        }

        $gvHit = KhoaHocGiaoVien::query()
            ->where('IsKhoaHocGiaoVien', 0)
            ->where('MaKH', '!=', $forMaKh)
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd)
            ->when($ignoreMaLichLv !== null, fn ($q) => $q->where('MaLichLV', '!=', $ignoreMaLichLv))
            ->orderBy('MaLichLV')
            ->get(['MaLichLV', 'MaGV', 'MaKH', 'NgayBD', 'NgayKT']);

        foreach ($gvHit as $row) {
            if (self::maGvMatches((string) $row->MaGV, $maGvNorm)) {
                return self::conflictFromRow($row->MaKH, $row->NgayBD, $row->NgayKT, 'gv', (int) $row->MaLichLV);
            }
        }

        $xeHit = KhoaHocXeTap::query()
            ->where('IsKhoaHocXeTap', 0)
            ->where('MaKH', '!=', $forMaKh)
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd)
            ->when($ignoreMaLichSd !== null, fn ($q) => $q->where('MaLichSD', '!=', $ignoreMaLichSd))
            ->orderBy('MaLichSD')
            ->get(['MaLichSD', 'MaGV', 'MaKH', 'NgayBD', 'NgayKT']);

        foreach ($xeHit as $row) {
            if (self::maGvMatches((string) $row->MaGV, $maGvNorm)) {
                return self::conflictFromRow($row->MaKH, $row->NgayBD, $row->NgayKT, 'xe', (int) $row->MaLichSD);
            }
        }

        return null;
    }

    /**
     * @return list<array{
     *     ma_kh: string,
     *     ngay_bd: string,
     *     ngay_kt: string,
     *     nguon: 'gv'|'xe',
     *     ma_lich: int,
     *     ma_gv: string,
     *     ten_gv: string,
     *     bien_so_xe?: string
     * }>
     */
    public static function findAllConflicts(
        string $maGv,
        string $ngayBD,
        string $ngayKT,
        string $forMaKh,
        ?int $ignoreMaLichLv = null,
        ?int $ignoreMaLichSd = null,
        bool $includeXe = true
    ): array {
        $maGvNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv);
        if ($maGvNorm === '') {
            return [];
        }

        $forMaKh = trim($forMaKh);
        if ($forMaKh === '') {
            return [];
        }

        try {
            $bd = Carbon::parse($ngayBD);
            $kt = Carbon::parse($ngayKT);
        } catch (\Throwable) {
            return [];
        }

        $hits = [];

        $gvRows = KhoaHocGiaoVien::query()
            ->where('IsKhoaHocGiaoVien', 0)
            ->where('MaKH', '!=', $forMaKh)
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd)
            ->when($ignoreMaLichLv !== null, fn ($q) => $q->where('MaLichLV', '!=', $ignoreMaLichLv))
            ->orderBy('MaLichLV')
            ->limit(50)
            ->get(['MaLichLV', 'MaGV', 'MaKH', 'TenGV', 'NgayBD', 'NgayKT']);

        foreach ($gvRows as $row) {
            if (! self::maGvMatches((string) $row->MaGV, $maGvNorm)) {
                continue;
            }
            $hits[] = array_merge(
                self::conflictFromRow($row->MaKH, $row->NgayBD, $row->NgayKT, 'gv', (int) $row->MaLichLV),
                [
                    'ma_gv' => trim((string) $row->MaGV),
                    'ten_gv' => trim((string) ($row->TenGV ?? '')),
                ]
            );
        }

        if ($includeXe) {
            $xeRows = KhoaHocXeTap::query()
                ->where('IsKhoaHocXeTap', 0)
                ->where('MaKH', '!=', $forMaKh)
                ->where('NgayBD', '<', $kt)
                ->where('NgayKT', '>', $bd)
                ->when($ignoreMaLichSd !== null, fn ($q) => $q->where('MaLichSD', '!=', $ignoreMaLichSd))
                ->orderBy('MaLichSD')
                ->limit(50)
                ->get(['MaLichSD', 'MaGV', 'MaKH', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

            foreach ($xeRows as $row) {
                if (! self::maGvMatches((string) $row->MaGV, $maGvNorm)) {
                    continue;
                }
                $hits[] = array_merge(
                    self::conflictFromRow($row->MaKH, $row->NgayBD, $row->NgayKT, 'xe', (int) $row->MaLichSD),
                    [
                        'ma_gv' => trim((string) $row->MaGV),
                        'ten_gv' => trim((string) ($row->TenGV ?? '')),
                        'bien_so_xe' => trim((string) ($row->BienSoXe ?? '')),
                    ]
                );
            }
        }

        return $hits;
    }

    /**
     * Kiểm tra GV dạy thay: các buổi TH của GV gốc trên khóa hiện tại (trong khoảng ngày thay)
     * không được trùng giờ với lịch GV dạy thay trên khóa khác.
     *
     * @throws ValidationException
     */
    public static function assertSubstituteThayHasNoCrossKhoaConflict(
        string $maKhoaHoc,
        string $maGiaoVienGoc,
        string $maGiaoVienThay,
        Carbon $tuNgay,
        ?Carbon $denNgay
    ): void {
        $maKh = trim($maKhoaHoc);
        $maGoc = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienGoc);
        $maThay = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienThay);

        if ($maKh === '' || $maGoc === '' || $maThay === '') {
            return;
        }

        $subEnd = ($denNgay ?? $tuNgay->copy())->toDateString();
        $subStart = $tuNgay->toDateString();

        $slots = self::courseMainGvThSlots($maKh, $maGoc, $subStart, $subEnd);
        if ($slots->isEmpty()) {
            return;
        }

        foreach ($slots as $slot) {
            $hit = self::findConflict(
                $maThay,
                $slot['ngay_bd'],
                $slot['ngay_kt'],
                $maKh
            );
            if ($hit === null) {
                continue;
            }

            throw ValidationException::withMessages([
                'ma_giao_vien' => self::formatConflictMessage(
                    $maThay,
                    $maKh,
                    $slot['ngay_bd'],
                    $slot['ngay_kt'],
                    $hit
                ),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $gvRows  Rows slated for insert/update (lich_giao_vien payload)
     * @return list<string>
     */
    public static function validateImportGvRowsForCrossKhoa(array $gvRows): array
    {
        $errors = [];
        $pending = [];

        foreach ($gvRows as $index => $row) {
            $maKh = trim((string) ($row['MaKH'] ?? ''));
            $maGv = trim((string) ($row['MaGV'] ?? ''));
            $ngayBD = (string) ($row['NgayBD'] ?? '');
            $ngayKT = (string) ($row['NgayKT'] ?? '');

            if ($maKh === '' || $maGv === '' || $ngayBD === '' || $ngayKT === '') {
                continue;
            }

            $ignoreLv = null;
            if (($row['_action'] ?? '') === 'update' && ! empty($row['MaLichLV'])) {
                $ignoreLv = (int) $row['MaLichLV'];
            }

            $hit = self::findConflict($maGv, $ngayBD, $ngayKT, $maKh, $ignoreLv);
            if ($hit !== null) {
                $errors[] = 'Dòng GV #'.($index + 1).': '.self::formatConflictMessage(
                    $maGv,
                    $maKh,
                    $ngayBD,
                    $ngayKT,
                    $hit
                );

                continue;
            }

            $pending[] = [
                'line' => $index + 1,
                'ma_gv' => DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv),
                'ma_kh' => $maKh,
                'bd' => Carbon::parse($ngayBD),
                'kt' => Carbon::parse($ngayKT),
            ];
        }

        $count = count($pending);
        for ($a = 0; $a < $count; $a++) {
            for ($b = $a + 1; $b < $count; $b++) {
                $left = $pending[$a];
                $right = $pending[$b];
                if ($left['ma_gv'] !== $right['ma_gv']) {
                    continue;
                }
                if ($left['ma_kh'] === $right['ma_kh']) {
                    continue;
                }
                if ($left['bd']->lt($right['kt']) && $left['kt']->gt($right['bd'])) {
                    $errors[] = sprintf(
                        'Dòng GV #%d và #%d: giáo viên %s trùng khung giờ giữa khóa %s và %s.',
                        $left['line'],
                        $right['line'],
                        $left['ma_gv'],
                        $left['ma_kh'],
                        $right['ma_kh']
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * @return Collection<int, array{ngay_bd: string, ngay_kt: string}>
     */
    private static function courseMainGvThSlots(string $maKh, string $maGvGoc, string $subStart, string $subEnd): Collection
    {
        $slots = collect();

        $gvRows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('LoaiGV', 'TH')
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get(['MaGV', 'NgayBD', 'NgayKT']);

        foreach ($gvRows as $row) {
            if (! self::maGvMatches((string) $row->MaGV, $maGvGoc)) {
                continue;
            }
            if (! self::slotOverlapsDateRange($row->NgayBD, $row->NgayKT, $subStart, $subEnd)) {
                continue;
            }
            $slots->push(self::slotTimes($row->NgayBD, $row->NgayKT));
        }

        $xeRows = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get(['MaGV', 'NgayBD', 'NgayKT']);

        foreach ($xeRows as $row) {
            if (! self::maGvMatches((string) $row->MaGV, $maGvGoc)) {
                continue;
            }
            if (! self::slotOverlapsDateRange($row->NgayBD, $row->NgayKT, $subStart, $subEnd)) {
                continue;
            }
            $slots->push(self::slotTimes($row->NgayBD, $row->NgayKT));
        }

        return $slots->unique(fn (array $s): string => $s['ngay_bd'].'|'.$s['ngay_kt'])->values();
    }

    /**
     * @return array{ngay_bd: string, ngay_kt: string}
     */
    private static function slotTimes(mixed $ngayBd, mixed $ngayKt): array
    {
        $bd = Carbon::parse($ngayBd);
        $kt = Carbon::parse($ngayKt ?? $ngayBd);

        return [
            'ngay_bd' => $bd->format('Y-m-d H:i:s'),
            'ngay_kt' => $kt->format('Y-m-d H:i:s'),
        ];
    }

    private static function slotOverlapsDateRange(mixed $ngayBd, mixed $ngayKt, string $rangeStart, string $rangeEnd): bool
    {
        try {
            $slotStart = Carbon::parse($ngayBd)->toDateString();
            $slotEnd = Carbon::parse($ngayKt ?? $ngayBd)->toDateString();
        } catch (\Throwable) {
            return false;
        }

        return $rangeStart <= $slotEnd && $slotStart <= $rangeEnd;
    }

    /**
     * @param  array{ma_kh: string, ngay_bd: string, ngay_kt: string, nguon: string, ma_lich: int}  $hit
     */
    public static function formatConflictMessage(
        string $maGv,
        string $forMaKh,
        string $slotBd,
        string $slotKt,
        array $hit
    ): string {
        $slotLabel = self::formatRangeLabel($slotBd, $slotKt);
        $otherLabel = self::formatRangeLabel($hit['ngay_bd'], $hit['ngay_kt']);

        return sprintf(
            'Giáo viên %s đã có lịch khóa %s (%s) trùng khung giờ với buổi khóa %s (%s).',
            DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv),
            $hit['ma_kh'],
            $otherLabel,
            $forMaKh,
            $slotLabel
        );
    }

    private static function formatRangeLabel(string $bd, string $kt): string
    {
        try {
            $start = Carbon::parse($bd);
            $end = Carbon::parse($kt);

            return $start->format('d/m/Y H:i').' – '.$end->format('H:i');
        } catch (\Throwable) {
            return $bd.' – '.$kt;
        }
    }

    /**
     * @return array{ma_kh: string, ngay_bd: string, ngay_kt: string, nguon: 'gv'|'xe', ma_lich: int}
     */
    private static function conflictFromRow(
        mixed $maKh,
        mixed $ngayBd,
        mixed $ngayKt,
        string $nguon,
        int $maLich
    ): array {
        $times = self::slotTimes($ngayBd, $ngayKt);

        return [
            'ma_kh' => trim((string) $maKh),
            'ngay_bd' => $times['ngay_bd'],
            'ngay_kt' => $times['ngay_kt'],
            'nguon' => $nguon,
            'ma_lich' => $maLich,
        ];
    }

    private static function maGvMatches(string $a, string $b): bool
    {
        return DatPhanCongHocVienSaver::normalizeMaGiaoVien($a) === DatPhanCongHocVienSaver::normalizeMaGiaoVien($b);
    }
}
