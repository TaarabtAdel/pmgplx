<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongHocVien;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatPhanCongHocVienImporter
{
    /**
     * @param  array<string, mixed>  $preview
     * @return array{saved: int, updated: int, deleted: int, ma_khoa_hoc: string}
     *
     * @throws Throwable
     */
    public function importFromPreview(array $preview): array
    {
        $meta = $preview['meta'] ?? [];
        $maKhoaHoc = trim((string) ($meta['ma_khoa_hoc'] ?? ''));
        $allRecords = $meta['all_records'] ?? [];
        $fileName = (string) ($preview['file_name'] ?? '');

        if ($maKhoaHoc === '' || ! is_array($allRecords) || $allRecords === []) {
            throw new \InvalidArgumentException('Không có dữ liệu hợp lệ để lưu.');
        }

        $saveable = array_values(array_filter(
            $allRecords,
            static fn (array $row): bool => ! empty($row['can_save'])
        ));

        $saveable = DatPhanCongHocVienSaver::dedupeByMaHocVienKeepLast($saveable);

        if ($saveable === []) {
            throw new \InvalidArgumentException('Không có dòng nào đủ điều kiện lưu.');
        }

        $now = Carbon::now();
        $maHocVienList = array_column($saveable, 'MaHocVien');

        $deleted = 0;
        $updated = 0;
        $saved = 0;

        DB::connection('sqlsrv_manhlinh')->transaction(function () use (
            $maKhoaHoc,
            $saveable,
            $fileName,
            $now,
            $maHocVienList,
            &$deleted,
            &$updated,
            &$saved
        ): void {
            $deleted = DatPhanCongHocVien::query()
                ->where('MaKhoaHoc', $maKhoaHoc)
                ->whereNotIn('MaHocVien', $maHocVienList)
                ->delete();

            foreach ($saveable as $record) {
                $maHocVien = (string) ($record['MaHocVien'] ?? '');
                $payload = [
                    'MaKhoaHoc' => $maKhoaHoc,
                    'MaHocVien' => $maHocVien,
                    'HoTenHocVien' => trim((string) ($record['HoTenHocVien'] ?? '')),
                    'MaGiaoVien' => (string) ($record['MaGiaoVien'] ?? ''),
                    'BienSoXe' => (string) ($record['BienSoXe'] ?? ''),
                    'BienSoXeTuDong' => (string) ($record['BienSoXeTuDong'] ?? ''),
                    'FileNguon' => $fileName,
                    'NgayNhap' => $now,
                ];

                $existing = DatPhanCongHocVien::query()
                    ->where('MaKhoaHoc', $maKhoaHoc)
                    ->where('MaHocVien', $maHocVien)
                    ->first();

                if ($existing !== null) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    DatPhanCongHocVien::query()->create($payload);
                    $saved++;
                }
            }
        });

        return [
            'saved' => $saved,
            'updated' => $updated,
            'deleted' => $deleted,
            'ma_khoa_hoc' => $maKhoaHoc,
        ];
    }
}
