<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongHocVien;
use App\Support\PMGPLX\LichExcelBienSo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DatPhanCongHocVienSaver
{
    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    public static function dedupeByMaHocVienKeepLast(array $records): array
    {
        $byMaHocVien = [];

        foreach ($records as $record) {
            $maHocVien = self::normalizeMaHocVien((string) ($record['MaHocVien'] ?? ''));
            if ($maHocVien === '') {
                continue;
            }

            $record['MaHocVien'] = $maHocVien;
            $byMaHocVien[$maHocVien] = $record;
        }

        return array_values($byMaHocVien);
    }

    public static function normalizeMaHocVien(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (is_numeric($value) && ! str_contains($value, '.')) {
            return $value;
        }

        if (is_numeric($value) && str_contains($value, '.')) {
            return (string) (int) (float) $value;
        }

        return $value;
    }

    public static function rowKeyForPhanCong(string $maKhoaHoc, string $maHocVien): string
    {
        return strtoupper(trim($maKhoaHoc)).'|'.self::normalizeMaHocVien($maHocVien);
    }

    public static function normalizeMaGiaoVien(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits !== '' && strlen($digits) <= 8) {
            return str_pad($digits, 8, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    public static function normalizeBienSo(?string $value): string
    {
        $raw = trim((string) $value);
        $normalized = LichExcelBienSo::normalize($raw);
        if ($normalized === '' && $raw !== '') {
            return mb_strtoupper($raw);
        }

        return $normalized;
    }

    /**
     * @return array{created: bool, item: DatPhanCongHocVien}
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public static function upsert(
        string $maKhoaHoc,
        string $maHocVien,
        string $maGiaoVien,
        string $bienSoXe = '',
        ?string $hoTenHocVien = null,
        ?string $fileNguon = null,
        ?int $recordId = null,
        string $bienSoXeTuDong = '',
        bool $blockReassignToOtherGiaoVien = false
    ): array {
        $maKhoaHoc = trim($maKhoaHoc);
        $maHocVien = self::normalizeMaHocVien($maHocVien);
        $maGiaoVien = self::normalizeMaGiaoVien($maGiaoVien);
        $bienSoXe = self::normalizeBienSo($bienSoXe);
        $bienSoXeTuDong = self::normalizeBienSo($bienSoXeTuDong);

        if ($maKhoaHoc === '') {
            throw ValidationException::withMessages(['ma_khoa_hoc' => 'Nhập mã khóa học.']);
        }

        if ($maHocVien === '') {
            throw ValidationException::withMessages(['ma_hoc_vien' => 'Nhập mã học viên.']);
        }

        if ($maGiaoVien === '') {
            throw ValidationException::withMessages(['ma_giao_vien' => 'Nhập mã giáo viên.']);
        }

        $now = Carbon::now();
        $payload = [
            'MaKhoaHoc' => $maKhoaHoc,
            'MaHocVien' => $maHocVien,
            'HoTenHocVien' => trim((string) ($hoTenHocVien ?? '')),
            'MaGiaoVien' => $maGiaoVien,
            'BienSoXe' => $bienSoXe,
            'BienSoXeTuDong' => $bienSoXeTuDong,
            'FileNguon' => $fileNguon,
            'NgayNhap' => $now,
        ];

        return DB::connection('sqlsrv_manhlinh')->transaction(function () use (
            $payload,
            $maKhoaHoc,
            $maHocVien,
            $recordId,
            $maGiaoVien,
            $blockReassignToOtherGiaoVien
        ): array {
            if ($recordId !== null) {
                return self::saveExistingRecord($recordId, $maKhoaHoc, $maHocVien, $payload);
            }

            $existingRows = self::assignmentsForHocVienInKhoa($maKhoaHoc, $maHocVien);

            if ($blockReassignToOtherGiaoVien && count($existingRows) > 1) {
                throw ValidationException::withMessages([
                    'ma_hoc_vien' => 'Mã học viên '.$maHocVien.' có '.count($existingRows)
                        .' dòng phân công trùng trong khóa — xóa bớt trước khi gán lại.',
                ]);
            }

            $existing = $existingRows[0] ?? null;

            if ($existing !== null) {
                if ($blockReassignToOtherGiaoVien) {
                    self::assertSameGiaoVienOrFail($existing, $maGiaoVien, $maHocVien);
                }
                $existing->update($payload);

                return ['created' => false, 'item' => $existing->fresh()];
            }

            $item = DatPhanCongHocVien::query()->create($payload);

            return ['created' => true, 'item' => $item];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{created: bool, item: DatPhanCongHocVien}
     *
     * @throws ValidationException
     */
    private static function saveExistingRecord(int $recordId, string $maKhoaHoc, string $maHocVien, array $payload): array
    {
        $current = DatPhanCongHocVien::query()->find($recordId);
        if ($current === null) {
            throw ValidationException::withMessages(['id' => 'Bản ghi phân công không còn tồn tại.']);
        }

        if (trim((string) $current->MaKhoaHoc) !== $maKhoaHoc) {
            throw ValidationException::withMessages(['ma_khoa_hoc' => 'Không thể đổi mã khóa khi sửa.']);
        }

        $duplicate = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->where('MaHocVien', $maHocVien)
            ->where('Id', '!=', $recordId)
            ->first();

        if ($duplicate !== null) {
            $duplicate->update($payload);
            $current->delete();

            return ['created' => false, 'item' => $duplicate->fresh()];
        }

        $current->update($payload);

        return ['created' => false, 'item' => $current->fresh()];
    }

    public static function findAssignmentInKhoa(string $maKhoaHoc, string $maHocVien): ?DatPhanCongHocVien
    {
        $maKhoaHoc = trim($maKhoaHoc);
        $maHocVien = self::normalizeMaHocVien($maHocVien);
        if ($maKhoaHoc === '' || $maHocVien === '') {
            return null;
        }

        $variants = array_values(array_unique(array_filter([
            $maHocVien,
            ltrim($maHocVien, '0'),
        ])));

        return DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->whereIn('MaHocVien', $variants)
            ->orderBy('Id')
            ->first();
    }

    /**
     * Một mã HV trong khóa gắn nhiều dòng hoặc nhiều giáo viên (trùng phân công).
     *
     * @return list<array{ma_hoc_vien: string, count: int, ma_giao_vien_list: list<string>}>
     */
    public static function duplicateMaHocVienInKhoa(string $maKhoaHoc): array
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            return [];
        }

        $rows = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderBy('Id')
            ->get(['Id', 'MaHocVien', 'MaGiaoVien']);

        /** @var array<string, array{count: int, ma_giao_vien_list: list<string>}> $byNorm */
        $byNorm = [];
        foreach ($rows as $row) {
            $norm = self::normalizeMaHocVien((string) $row->MaHocVien);
            if ($norm === '') {
                continue;
            }
            if (! isset($byNorm[$norm])) {
                $byNorm[$norm] = ['count' => 0, 'ma_giao_vien_list' => []];
            }
            $byNorm[$norm]['count']++;
            $gv = self::normalizeMaGiaoVien((string) $row->MaGiaoVien);
            if ($gv !== '' && ! in_array($gv, $byNorm[$norm]['ma_giao_vien_list'], true)) {
                $byNorm[$norm]['ma_giao_vien_list'][] = $gv;
            }
        }

        $out = [];
        foreach ($byNorm as $norm => $data) {
            if ($data['count'] <= 1 && count($data['ma_giao_vien_list']) <= 1) {
                continue;
            }
            $out[] = [
                'ma_hoc_vien' => $norm,
                'count' => $data['count'],
                'ma_giao_vien_list' => $data['ma_giao_vien_list'],
            ];
        }

        usort($out, static fn (array $a, array $b): int => strcmp($a['ma_hoc_vien'], $b['ma_hoc_vien']));

        return $out;
    }

    /**
     * @return list<DatPhanCongHocVien>
     */
    public static function assignmentsForHocVienInKhoa(string $maKhoaHoc, string $maHocVien): array
    {
        $maKhoaHoc = trim($maKhoaHoc);
        $maHocVien = self::normalizeMaHocVien($maHocVien);
        if ($maKhoaHoc === '' || $maHocVien === '') {
            return [];
        }

        $variants = array_values(array_unique(array_filter([
            $maHocVien,
            ltrim($maHocVien, '0'),
        ])));

        return DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->whereIn('MaHocVien', $variants)
            ->orderBy('Id')
            ->get()
            ->all();
    }

    /**
     * @throws ValidationException
     */
    public static function assertSameGiaoVienOrFail(
        DatPhanCongHocVien $existing,
        string $targetMaGiaoVien,
        string $maHocVien
    ): void {
        $currentGv = self::normalizeMaGiaoVien((string) $existing->MaGiaoVien);
        $targetGv = self::normalizeMaGiaoVien($targetMaGiaoVien);
        if ($currentGv === '' || $targetGv === '' || $currentGv === $targetGv) {
            return;
        }

        $label = trim((string) ($existing->HoTenHocVien ?: $maHocVien));

        throw ValidationException::withMessages([
            'ma_giao_vien' => 'Học viên '.$label.' ('.$maHocVien.') đã phân công giáo viên '
                .$existing->MaGiaoVien.'. Một học viên chỉ thuộc một giáo viên trong khóa — xóa phân công cũ trước khi gán GV khác.',
        ]);
    }
}
