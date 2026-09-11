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
        string $bienSoXeTuDong = ''
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

        return DB::connection('sqlsrv_manhlinh')->transaction(function () use ($payload, $maKhoaHoc, $maHocVien, $recordId): array {
            if ($recordId !== null) {
                return self::saveExistingRecord($recordId, $maKhoaHoc, $maHocVien, $payload);
            }

            $existing = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $maKhoaHoc)
                ->where('MaHocVien', $maHocVien)
                ->first();

            if ($existing !== null) {
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
}
