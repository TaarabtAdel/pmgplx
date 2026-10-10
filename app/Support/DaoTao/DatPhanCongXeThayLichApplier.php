<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongXeThay;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\PMGPLX\LichExcelBienSo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DatPhanCongXeThayLichApplier
{
    public const NGUOI_SUA = 'DAT_XE_THAY';

    public const NGUOI_SUA_REVERT = 'DAT_XE_THAY_HOAN';

    /**
     * @return array{
     *     ma_khoa_hoc: string,
     *     ma_kh: string,
     *     khai_bao: list<array<string, mixed>>,
     *     gv_lich: list<array<string, mixed>>,
     *     xe_lich: list<array<string, mixed>>,
     *     meta: array{gv_bien_count: int, xe_count: int, khai_bao_count: int}
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
                'ma_khoa_hoc' => 'Chưa có khai báo xe thay cho khóa này.',
            ]);
        }

        $planned = $this->planChanges($maKhoaHoc, $khaiBao);

        return [
            'ma_khoa_hoc' => $maKhoaHoc,
            'ma_kh' => $maKhoaHoc,
            'khai_bao' => $khaiBao,
            'gv_lich' => $planned['gv'],
            'xe_lich' => $planned['xe'],
            'meta' => [
                'gv_bien_count' => count($planned['gv']),
                'xe_count' => count($planned['xe']),
                'khai_bao_count' => count($khaiBao),
            ],
        ];
    }

    /**
     * @return array{gv_bien_updated: int, xe_updated: int}
     */
    public function apply(string $maKhoaHoc): array
    {
        $preview = $this->preview($maKhoaHoc);
        $now = Carbon::now();
        $gvUpdated = 0;
        $xeUpdated = 0;

        DB::connection((new KhoaHocXeTap())->getConnectionName())->transaction(function () use ($preview, $now, &$gvUpdated, &$xeUpdated): void {
            foreach ($preview['gv_lich'] as $row) {
                $updated = KhoaHocGiaoVien::query()
                    ->where('MaLichLV', $row['ma_lich'])
                    ->where('BienSoXe', $row['bien_so_cu'])
                    ->update([
                        'BienSoXe' => $row['bien_so_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $gvUpdated += $updated;
            }

            foreach ($preview['xe_lich'] as $row) {
                $updated = KhoaHocXeTap::query()
                    ->where('MaLichSD', $row['ma_lich'])
                    ->where('BienSoXe', $row['bien_so_cu'])
                    ->update([
                        'BienSoXe' => $row['bien_so_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $xeUpdated += $updated;
            }
        });

        return [
            'gv_bien_updated' => $gvUpdated,
            'xe_updated' => $xeUpdated,
        ];
    }

    /**
     * @return array{gv_bien_updated: int, xe_updated: int}
     */
    public function applyKhaiBao(DatPhanCongXeThay $record): array
    {
        $sub = $this->khaiBaoFromModel($record);
        $maKh = trim((string) $record->MaKhoaHoc);
        if ($maKh === '') {
            return ['gv_bien_updated' => 0, 'xe_updated' => 0];
        }

        $planned = $this->planChanges($maKh, [$sub]);
        $now = Carbon::now();
        $gvUpdated = 0;
        $xeUpdated = 0;

        DB::connection((new KhoaHocXeTap())->getConnectionName())->transaction(function () use ($planned, $now, &$gvUpdated, &$xeUpdated): void {
            foreach ($planned['gv'] as $row) {
                $updated = KhoaHocGiaoVien::query()
                    ->where('MaLichLV', $row['ma_lich'])
                    ->where('BienSoXe', $row['bien_so_cu'])
                    ->update([
                        'BienSoXe' => $row['bien_so_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $gvUpdated += $updated;
            }

            foreach ($planned['xe'] as $row) {
                $updated = KhoaHocXeTap::query()
                    ->where('MaLichSD', $row['ma_lich'])
                    ->where('BienSoXe', $row['bien_so_cu'])
                    ->update([
                        'BienSoXe' => $row['bien_so_moi'],
                        'NguoiSua' => self::NGUOI_SUA,
                        'NgaySua' => $now,
                    ]);
                $xeUpdated += $updated;
            }
        });

        return [
            'gv_bien_updated' => $gvUpdated,
            'xe_updated' => $xeUpdated,
        ];
    }

    /**
     * @return array{gv_bien_updated: int, xe_updated: int}
     */
    public function revertKhaiBao(DatPhanCongXeThay $record): array
    {
        $sub = $this->khaiBaoFromModel($record);
        $maKh = trim((string) $record->MaKhoaHoc);
        if ($maKh === '') {
            return ['gv_bien_updated' => 0, 'xe_updated' => 0];
        }

        $planned = $this->planRevertChanges($maKh, $sub);
        $now = Carbon::now();
        $gvUpdated = 0;
        $xeUpdated = 0;

        DB::connection((new KhoaHocXeTap())->getConnectionName())->transaction(function () use ($planned, $now, &$gvUpdated, &$xeUpdated): void {
            foreach ($planned['gv'] as $row) {
                $updated = KhoaHocGiaoVien::query()
                    ->where('MaLichLV', $row['ma_lich'])
                    ->where('BienSoXe', $row['bien_so_cu'])
                    ->update([
                        'BienSoXe' => $row['bien_so_moi'],
                        'NguoiSua' => self::NGUOI_SUA_REVERT,
                        'NgaySua' => $now,
                    ]);
                $gvUpdated += $updated;
            }

            foreach ($planned['xe'] as $row) {
                $updated = KhoaHocXeTap::query()
                    ->where('MaLichSD', $row['ma_lich'])
                    ->where('BienSoXe', $row['bien_so_cu'])
                    ->update([
                        'BienSoXe' => $row['bien_so_moi'],
                        'NguoiSua' => self::NGUOI_SUA_REVERT,
                        'NgaySua' => $now,
                    ]);
                $xeUpdated += $updated;
            }
        });

        return [
            'gv_bien_updated' => $gvUpdated,
            'xe_updated' => $xeUpdated,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     ma_giao_vien_goc: string,
     *     bien_so_xe_goc: string,
     *     bien_so_xe_goc_norm: string,
     *     bien_so_xe: string,
     *     bien_so_xe_norm: string,
     *     tu_ngay: string,
     *     den_ngay: string|null
     * }
     */
    private function khaiBaoFromModel(DatPhanCongXeThay $row): array
    {
        return [
            'id' => (int) $row->Id,
            'ma_giao_vien_goc' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGiaoVienGoc),
            'bien_so_xe_goc' => trim((string) $row->BienSoXeGoc),
            'bien_so_xe_goc_norm' => DatPhanCongHocVienSaver::normalizeBienSo((string) $row->BienSoXeGoc),
            'bien_so_xe' => trim((string) $row->BienSoXe),
            'bien_so_xe_norm' => DatPhanCongHocVienSaver::normalizeBienSo((string) $row->BienSoXe),
            'tu_ngay' => Carbon::parse($row->TuNgay)->toDateString(),
            'den_ngay' => $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $sub
     * @return array{gv: list<array<string, mixed>>, xe: list<array<string, mixed>>}
     */
    private function planRevertChanges(string $maKh, array $sub): array
    {
        $gvChanges = [];
        $xeChanges = [];

        $maGoc = $sub['ma_giao_vien_goc'];
        $xeGoc = $sub['bien_so_xe_goc'];
        $xeThayNorm = $sub['bien_so_xe_norm'];

        $gvRows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('LoaiGV', 'TH')
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->orderBy('NgayBD')
            ->get(['MaLichLV', 'MaGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        $xeRows = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->orderBy('NgayBD')
            ->get(['MaLichSD', 'MaGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        foreach ($gvRows as $row) {
            if (! $this->maGvMatches((string) ($row->MaGV ?? ''), $maGoc)) {
                continue;
            }
            if (! $this->bienSoMatches((string) ($row->BienSoXe ?? ''), $xeThayNorm)) {
                continue;
            }
            if ($this->bienSoMatches((string) ($row->BienSoXe ?? ''), $sub['bien_so_xe_goc_norm'])) {
                continue;
            }
            if (! $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay'])) {
                continue;
            }

            $gvChanges[(int) $row->MaLichLV] = [
                'ma_lich' => (int) $row->MaLichLV,
                'bien_so_cu' => (string) $row->BienSoXe,
                'bien_so_moi' => $xeGoc,
            ];
        }

        foreach ($xeRows as $row) {
            if (! $this->maGvMatches((string) ($row->MaGV ?? ''), $maGoc)) {
                continue;
            }
            if (! $this->bienSoMatches((string) ($row->BienSoXe ?? ''), $xeThayNorm)) {
                continue;
            }
            if (! $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay'])) {
                continue;
            }

            $xeChanges[(int) $row->MaLichSD] = [
                'ma_lich' => (int) $row->MaLichSD,
                'bien_so_cu' => (string) $row->BienSoXe,
                'bien_so_moi' => $xeGoc,
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
        return DatPhanCongXeThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderBy('MaGiaoVienGoc')
            ->orderBy('BienSoXeGoc')
            ->orderBy('TuNgay')
            ->get()
            ->map(fn (DatPhanCongXeThay $row): array => [
                'id' => (int) $row->Id,
                'ma_giao_vien_goc' => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGiaoVienGoc),
                'bien_so_xe_goc' => trim((string) $row->BienSoXeGoc),
                'bien_so_xe_goc_norm' => DatPhanCongHocVienSaver::normalizeBienSo((string) $row->BienSoXeGoc),
                'bien_so_xe' => trim((string) $row->BienSoXe),
                'bien_so_xe_norm' => DatPhanCongHocVienSaver::normalizeBienSo((string) $row->BienSoXe),
                'tu_ngay' => Carbon::parse($row->TuNgay)->toDateString(),
                'den_ngay' => $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null,
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $khaiBao
     * @return array{gv: list<array<string, mixed>>, xe: list<array<string, mixed>>}
     */
    private function planChanges(string $maKh, array $khaiBao): array
    {
        $gvChanges = [];
        $xeChanges = [];

        $gvRows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('LoaiGV', 'TH')
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->orderBy('NgayBD')
            ->get(['MaLichLV', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        $xeRows = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->orderBy('NgayBD')
            ->get(['MaLichSD', 'MaGV', 'TenGV', 'BienSoXe', 'NgayBD', 'NgayKT']);

        foreach ($khaiBao as $sub) {
            $maGoc = $sub['ma_giao_vien_goc'];
            $xeGocNorm = $sub['bien_so_xe_goc_norm'];
            $xeMoi = $sub['bien_so_xe'];

            foreach ($gvRows as $row) {
                if (! $this->rowMatchesSubstitute($row, $maGoc, $xeGocNorm, $sub)) {
                    continue;
                }
                if ($this->bienSoMatches((string) $row->BienSoXe, $sub['bien_so_xe_norm'])) {
                    continue;
                }

                $gvChanges[(int) $row->MaLichLV] = $this->changeRow(
                    (int) $row->MaLichLV,
                    'gv',
                    $row,
                    (string) $row->BienSoXe,
                    $xeMoi,
                    $sub
                );
            }

            foreach ($xeRows as $row) {
                if (! $this->rowMatchesSubstitute($row, $maGoc, $xeGocNorm, $sub)) {
                    continue;
                }
                if ($this->bienSoMatches((string) $row->BienSoXe, $sub['bien_so_xe_norm'])) {
                    continue;
                }

                $xeChanges[(int) $row->MaLichSD] = $this->changeRow(
                    (int) $row->MaLichSD,
                    'xe',
                    $row,
                    (string) $row->BienSoXe,
                    $xeMoi,
                    $sub
                );
            }
        }

        return [
            'gv' => array_values($gvChanges),
            'xe' => array_values($xeChanges),
        ];
    }

    /**
     * @param  array<string, mixed>  $sub
     */
    private function rowMatchesSubstitute(object $row, string $maGoc, string $xeGocNorm, array $sub): bool
    {
        if (! $this->maGvMatches((string) ($row->MaGV ?? ''), $maGoc)) {
            return false;
        }

        if (! $this->bienSoMatches((string) ($row->BienSoXe ?? ''), $xeGocNorm)) {
            return false;
        }

        return $this->lichOverlapsSubstitute($row->NgayBD, $row->NgayKT, $sub['tu_ngay'], $sub['den_ngay']);
    }

    /**
     * @param  array<string, mixed>  $sub
     * @return array<string, mixed>
     */
    private function changeRow(int $maLich, string $loai, object $row, string $bienCu, string $bienMoi, array $sub): array
    {
        return [
            'ma_lich' => $maLich,
            'loai' => $loai,
            'ngay_bd' => $this->formatDateTime($row->NgayBD),
            'ngay_kt' => $this->formatDateTime($row->NgayKT),
            'ma_gv' => trim((string) ($row->MaGV ?? '')),
            'ten_gv' => trim((string) ($row->TenGV ?? '')),
            'bien_so_cu' => $bienCu,
            'bien_so_moi' => $bienMoi,
            'tu_ngay_thay' => $sub['tu_ngay'],
            'den_ngay_thay' => $sub['den_ngay'],
            'khai_bao_id' => $sub['id'],
            'ma_giao_vien_goc' => $sub['ma_giao_vien_goc'],
            'bien_so_xe_goc' => $sub['bien_so_xe_goc'],
        ];
    }

    private function maGvMatches(string $a, string $b): bool
    {
        return DatPhanCongHocVienSaver::normalizeMaGiaoVien($a) === DatPhanCongHocVienSaver::normalizeMaGiaoVien($b);
    }

    private function bienSoMatches(string $a, string $bNorm): bool
    {
        $aNorm = DatPhanCongHocVienSaver::normalizeBienSo($a);
        if ($aNorm !== '' && $aNorm === $bNorm) {
            return true;
        }

        return LichExcelBienSo::normalize($a) === LichExcelBienSo::normalize($bNorm);
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
}
