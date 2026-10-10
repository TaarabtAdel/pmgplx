<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongGiaoVienThay;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\PMGPLX\GiaoVienLichCrossKhoaChecker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DatPhanCongGiaoVienThayLichApplier
{
    public const NGUOI_SUA = 'DAT_GV_THAY';

    public const NGUOI_SUA_REVERT = 'DAT_GV_THAY_HOAN';

    /**
     * @return array{
     *     ma_khoa_hoc: string,
     *     ma_kh: string,
     *     khai_bao: list<array<string, mixed>>,
     *     gv_lich: list<array<string, mixed>>,
     *     xe_lich: list<array<string, mixed>>,
     *     meta: array{gv_count: int, xe_count: int, khai_bao_count: int}
     * }
     */
    public function preview(string $maKhoaHoc): array
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            throw ValidationException::withMessages(['ma_khoa_hoc' => 'Chọn mã khóa học.']);
        }

        $khaiBao = $this->loadKhaiBao($maKhoaHoc);
        if ($khaiBao === []) {
            throw ValidationException::withMessages([
                'ma_khoa_hoc' => 'Chưa có khai báo giáo viên dạy thay cho khóa này.',
            ]);
        }

        $tenByMa = $this->loadGiaoVienNames($khaiBao);
        $planned = $this->planChanges($maKhoaHoc, $khaiBao, $tenByMa);

        return [
            'ma_khoa_hoc' => $maKhoaHoc,
            'ma_kh' => $maKhoaHoc,
            'khai_bao' => $khaiBao,
            'gv_lich' => $planned['gv'],
            'xe_lich' => $planned['xe'],
            'meta' => [
                'gv_count' => count($planned['gv']),
                'xe_count' => count($planned['xe']),
                'khai_bao_count' => count($khaiBao),
            ],
        ];
    }

    /**
     * @return array{gv_updated: int, xe_updated: int}
     */
    public function apply(string $maKhoaHoc): array
    {
        $preview = $this->preview($maKhoaHoc);
        $this->assertPlannedChangesHaveNoCrossKhoaConflict($maKhoaHoc, $preview['gv_lich'], $preview['xe_lich']);
        $now = Carbon::now();
        $gvUpdated = 0;
        $xeUpdated = 0;

        DB::connection((new KhoaHocGiaoVien())->getConnectionName())->transaction(function () use ($preview, $now, &$gvUpdated, &$xeUpdated): void {
            foreach ($preview['gv_lich'] as $row) {
                $updated = KhoaHocGiaoVien::query()
                    ->where('MaLichLV', $row['ma_lich'])
                    ->where('MaGV', $row['ma_gv_cu'])
                    ->update([
                        'MaGV' => $row['ma_gv_moi'],
                        'TenGV' => $row['ten_gv_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $gvUpdated += $updated;
            }

            foreach ($preview['xe_lich'] as $row) {
                $updated = KhoaHocXeTap::query()
                    ->where('MaLichSD', $row['ma_lich'])
                    ->where('MaGV', $row['ma_gv_cu'])
                    ->update([
                        'MaGV' => $row['ma_gv_moi'],
                        'TenGV' => $row['ten_gv_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $xeUpdated += $updated;
            }
        });

        return [
            'gv_updated' => $gvUpdated,
            'xe_updated' => $xeUpdated,
        ];
    }

    /**
     * @return array{gv_updated: int, xe_updated: int}
     */
    public function applyKhaiBao(DatPhanCongGiaoVienThay $record): array
    {
        $sub = $this->khaiBaoFromModel($record);
        $maKh = trim((string) $record->MaKhoaHoc);
        if ($maKh === '') {
            return ['gv_updated' => 0, 'xe_updated' => 0];
        }

        $tenByMa = $this->loadGiaoVienNames([$sub]);
        $planned = $this->planChanges($maKh, [$sub], $tenByMa);
        $this->assertPlannedChangesHaveNoCrossKhoaConflict($maKh, $planned['gv'], $planned['xe']);
        $now = Carbon::now();
        $gvUpdated = 0;
        $xeUpdated = 0;

        DB::connection((new KhoaHocGiaoVien())->getConnectionName())->transaction(function () use ($planned, $now, &$gvUpdated, &$xeUpdated): void {
            foreach ($planned['gv'] as $row) {
                $updated = KhoaHocGiaoVien::query()
                    ->where('MaLichLV', $row['ma_lich'])
                    ->where('MaGV', $row['ma_gv_cu'])
                    ->update([
                        'MaGV' => $row['ma_gv_moi'],
                        'TenGV' => $row['ten_gv_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $gvUpdated += $updated;
            }

            foreach ($planned['xe'] as $row) {
                $updated = KhoaHocXeTap::query()
                    ->where('MaLichSD', $row['ma_lich'])
                    ->where('MaGV', $row['ma_gv_cu'])
                    ->update([
                        'MaGV' => $row['ma_gv_moi'],
                        'TenGV' => $row['ten_gv_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $xeUpdated += $updated;
            }
        });

        return [
            'gv_updated' => $gvUpdated,
            'xe_updated' => $xeUpdated,
        ];
    }

    /**
     * Hoàn lịch PMGPLX cho một khai báo (GV thay → GV gốc) trước khi xóa bản ghi.
     *
     * @return array{gv_updated: int, xe_updated: int}
     */
    public function revertKhaiBao(DatPhanCongGiaoVienThay $record): array
    {
        $sub = $this->khaiBaoFromModel($record);
        $maKh = trim((string) $record->MaKhoaHoc);
        if ($maKh === '') {
            return ['gv_updated' => 0, 'xe_updated' => 0];
        }

        $tenByMa = $this->loadGiaoVienNames([$sub]);
        $planned = $this->planRevertChanges($maKh, $sub, $tenByMa);
        $now = Carbon::now();
        $gvUpdated = 0;
        $xeUpdated = 0;

        DB::connection((new KhoaHocGiaoVien())->getConnectionName())->transaction(function () use ($planned, $now, &$gvUpdated, &$xeUpdated): void {
            foreach ($planned['gv'] as $row) {
                $updated = KhoaHocGiaoVien::query()
                    ->where('MaLichLV', $row['ma_lich'])
                    ->where('MaGV', $row['ma_gv_cu'])
                    ->update([
                        'MaGV' => $row['ma_gv_moi'],
                        'TenGV' => $row['ten_gv_moi'],
                        'NguoiSua' => self::NGUOI_SUA_REVERT,
                        'NgaySua' => $now,
                    ]);
                $gvUpdated += $updated;
            }

            foreach ($planned['xe'] as $row) {
                $updated = KhoaHocXeTap::query()
                    ->where('MaLichSD', $row['ma_lich'])
                    ->where('MaGV', $row['ma_gv_cu'])
                    ->update([
                        'MaGV' => $row['ma_gv_moi'],
                        'TenGV' => $row['ten_gv_moi'],
                        'NguoiSua' => self::NGUOI_SUA_REVERT,
                        'NgaySua' => $now,
                    ]);
                $xeUpdated += $updated;
            }
        });

        return [
            'gv_updated' => $gvUpdated,
            'xe_updated' => $xeUpdated,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     ma_giao_vien_goc: string,
     *     ma_giao_vien: string,
     *     tu_ngay: string,
     *     den_ngay: string|null
     * }
     */
    private function khaiBaoFromModel(DatPhanCongGiaoVienThay $row): array
    {
        return [
            'id' => (int) $row->Id,
            'ma_giao_vien_goc' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGiaoVienGoc),
            'ma_giao_vien' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGiaoVien),
            'tu_ngay' => Carbon::parse($row->TuNgay)->toDateString(),
            'den_ngay' => $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $sub
     * @param  array<string, string>  $tenByMa
     * @return array{gv: list<array<string, mixed>>, xe: list<array<string, mixed>>}
     */
    private function planRevertChanges(string $maKh, array $sub, array $tenByMa): array
    {
        $gvChanges = [];
        $xeChanges = [];

        $maGoc = $sub['ma_giao_vien_goc'];
        $maThay = $sub['ma_giao_vien'];
        $tenGoc = $tenByMa[$maGoc] ?? $maGoc;

        $gvRows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('LoaiGV', 'TH')
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get(['MaLichLV', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        $xeRows = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get(['MaLichSD', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        foreach ($gvRows as $row) {
            if (! $this->maGvMatches((string) $row->MaGV, $maThay)) {
                continue;
            }
            if (! $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay'])) {
                continue;
            }
            if ($this->maGvMatches((string) $row->MaGV, $maGoc)) {
                continue;
            }

            $gvChanges[(int) $row->MaLichLV] = [
                'ma_lich' => (int) $row->MaLichLV,
                'ma_gv_cu' => (string) $row->MaGV,
                'ten_gv_cu' => trim((string) ($row->TenGV ?? '')),
                'ma_gv_moi' => $maGoc,
                'ten_gv_moi' => $tenGoc,
            ];
        }

        foreach ($xeRows as $row) {
            if (! $this->maGvMatches((string) $row->MaGV, $maThay)) {
                continue;
            }
            if (! $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay'])) {
                continue;
            }

            $xeChanges[(int) $row->MaLichSD] = [
                'ma_lich' => (int) $row->MaLichSD,
                'ma_gv_cu' => (string) $row->MaGV,
                'ten_gv_cu' => trim((string) ($row->TenGV ?? '')),
                'ma_gv_moi' => $maGoc,
                'ten_gv_moi' => $tenGoc,
            ];
        }

        return [
            'gv' => array_values($gvChanges),
            'xe' => array_values($xeChanges),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadKhaiBao(string $maKhoaHoc): array
    {
        return DatPhanCongGiaoVienThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderBy('MaGiaoVienGoc')
            ->orderBy('TuNgay')
            ->get()
            ->map(fn (DatPhanCongGiaoVienThay $row): array => [
                'id' => (int) $row->Id,
                'ma_giao_vien_goc' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGiaoVienGoc),
                'ma_giao_vien' => trim((string) $row->MaGiaoVien),
                'tu_ngay' => Carbon::parse($row->TuNgay)->toDateString(),
                'den_ngay' => $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null,
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $khaiBao
     * @return array<string, string>
     */
    private function loadGiaoVienNames(array $khaiBao): array
    {
        $codes = [];
        foreach ($khaiBao as $row) {
            $codes[$row['ma_giao_vien_goc']] = true;
            $codes[DatPhanCongHocVienSaver::normalizeMaGiaoVien($row['ma_giao_vien'])] = true;
            $codes[trim($row['ma_giao_vien'])] = true;
        }

        $names = [];
        if ($codes !== []) {
            GiaoVien::query()
                ->whereIn('MaGV', array_keys($codes))
                ->get(['MaGV', 'HoTenDem', 'TenGV'])
                ->each(function (GiaoVien $gv) use (&$names): void {
                    $names[DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $gv->MaGV)] = $this->formatTenGv($gv);
                });
        }

        return $names;
    }

    /**
     * @param  list<array<string, mixed>>  $khaiBao
     * @param  array<string, string>  $tenByMa
     * @return array{gv: list<array<string, mixed>>, xe: list<array<string, mixed>>}
     */
    private function planChanges(string $maKh, array $khaiBao, array $tenByMa): array
    {
        $gvChanges = [];
        $xeChanges = [];

        $gvRows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('LoaiGV', 'TH')
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get(['MaLichLV', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        $xeRows = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get(['MaLichSD', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        foreach ($khaiBao as $sub) {
            $maGoc = $sub['ma_giao_vien_goc'];
            $maThay = $sub['ma_giao_vien'];
            $tenThay = $tenByMa[$maThay]
                ?? $tenByMa[DatPhanCongHocVienSaver::normalizeMaGiaoVien($maThay)]
                ?? $maThay;

            foreach ($gvRows as $row) {
                if (! $this->maGvMatches((string) $row->MaGV, $maGoc)) {
                    continue;
                }
                if (! $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay'])) {
                    continue;
                }
                if ($this->maGvMatches((string) $row->MaGV, $maThay)) {
                    continue;
                }

                $gvChanges[(int) $row->MaLichLV] = [
                    'ma_lich' => (int) $row->MaLichLV,
                    'loai' => 'gv',
                    'ngay_bd' => $this->formatDateTime($row->NgayBD),
                    'ngay_kt' => $this->formatDateTime($row->NgayKT),
                    'bien_so_xe' => trim((string) ($row->BienSoXe ?? '')),
                    'ma_gv_cu' => (string) $row->MaGV,
                    'ten_gv_cu' => trim((string) ($row->TenGV ?? '')),
                    'ma_gv_moi' => $maThay,
                    'ten_gv_moi' => $tenThay,
                    'tu_ngay_thay' => $sub['tu_ngay'],
                    'den_ngay_thay' => $sub['den_ngay'],
                    'khai_bao_id' => $sub['id'],
                ];
            }

            foreach ($xeRows as $row) {
                if (! $this->maGvMatches((string) $row->MaGV, $maGoc)) {
                    continue;
                }
                if (! $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay'])) {
                    continue;
                }
                if ($this->maGvMatches((string) $row->MaGV, $maThay)) {
                    continue;
                }

                $xeChanges[(int) $row->MaLichSD] = [
                    'ma_lich' => (int) $row->MaLichSD,
                    'loai' => 'xe',
                    'ngay_bd' => $this->formatDateTime($row->NgayBD),
                    'ngay_kt' => $this->formatDateTime($row->NgayKT),
                    'bien_so_xe' => trim((string) ($row->BienSoXe ?? '')),
                    'ma_gv_cu' => (string) $row->MaGV,
                    'ten_gv_cu' => trim((string) ($row->TenGV ?? '')),
                    'ma_gv_moi' => $maThay,
                    'ten_gv_moi' => $tenThay,
                    'tu_ngay_thay' => $sub['tu_ngay'],
                    'den_ngay_thay' => $sub['den_ngay'],
                    'khai_bao_id' => $sub['id'],
                ];
            }
        }

        return [
            'gv' => array_values($gvChanges),
            'xe' => array_values($xeChanges),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $gvPlanned
     * @param  list<array<string, mixed>>  $xePlanned
     */
    private function assertPlannedChangesHaveNoCrossKhoaConflict(string $maKh, array $gvPlanned, array $xePlanned): void
    {
        $maKh = trim($maKh);
        if ($maKh === '') {
            return;
        }

        foreach (array_merge($gvPlanned, $xePlanned) as $row) {
            $maThay = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) ($row['ma_gv_moi'] ?? ''));
            if ($maThay === '') {
                continue;
            }
            $bd = (string) ($row['ngay_bd'] ?? '');
            $kt = (string) ($row['ngay_kt'] ?? '');
            if ($bd === '' || $kt === '') {
                continue;
            }
            $hit = GiaoVienLichCrossKhoaChecker::findConflict($maThay, $bd, $kt, $maKh);
            if ($hit !== null) {
                throw ValidationException::withMessages([
                    'lich' => GiaoVienLichCrossKhoaChecker::formatConflictMessage($maThay, $maKh, $bd, $kt, $hit),
                ]);
            }
        }
    }

    private function maGvMatches(string $a, string $b): bool
    {
        return DatPhanCongHocVienSaver::normalizeMaGiaoVien($a) === DatPhanCongHocVienSaver::normalizeMaGiaoVien($b);
    }

    private function lichOverlapsSubstitute(mixed $ngayBd, mixed $ngayKt, string $tuNgay, ?string $denNgay): bool
    {
        if ($ngayBd === null) {
            return false;
        }

        try {
            $slotStart = Carbon::parse($ngayBd)->toDateString();
            $slotEnd = Carbon::parse($ngayKt ?? $ngayBd)->toDateString();
        } catch (\Throwable) {
            return false;
        }

        $subEnd = $denNgay ?? '9999-12-31';

        return $tuNgay <= $slotEnd && $slotStart <= $subEnd;
    }

    private function formatDateTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatTenGv(GiaoVien $gv): string
    {
        $ten = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));

        return $ten !== '' ? $ten : trim((string) $gv->MaGV);
    }
}
