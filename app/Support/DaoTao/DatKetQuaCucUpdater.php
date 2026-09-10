<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\DaoTao\DatPhanLoaiPhien;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatKetQuaCucUpdater
{
    public const PHAN_LOAI_DA_TRUYEN = 'Đã truyền lên cục';

    public const PHAN_LOAI_CUOC_KHONG = 'Cục không chấp nhận';

    /**
     * @return array{
     *     so_khoa: int,
     *     ma_khoa_hoc_list: list<string>,
     *     da_truyen: int,
     *     cuoc_khong: int,
     *     khong_trong_file: int,
     *     file_khong_co_db: int,
     *     tong_phien_db: int,
     *     theo_khoa: array<string, array{
     *         ma_khoa_hoc: string,
     *         da_truyen: int,
     *         cuoc_khong: int,
     *         khong_trong_file: int,
     *         file_khong_co_db: int,
     *         tong_phien_db: int
     *     }>
     * }
     *
     * @throws Throwable
     */
    public function applyFromFile(string $filePath): array
    {
        $parser = new DatKetQuaCucExcelParser();
        $fileRows = $parser->readAllRecordsFromFile($filePath);

        if ($fileRows === []) {
            throw new \RuntimeException('File không có dữ liệu để cập nhật.');
        }

        $byKhoa = [];
        foreach ($fileRows as $row) {
            $byKhoa[$row['MaKhoaHoc']][] = $row;
        }

        $daTruyenId = $this->ensurePhanLoai(self::PHAN_LOAI_DA_TRUYEN)->Id;
        $cuocKhongId = $this->ensurePhanLoai(self::PHAN_LOAI_CUOC_KHONG)->Id;
        $cuocPhanLoaiIds = [$daTruyenId, $cuocKhongId];

        $aggregate = [
            'so_khoa' => count($byKhoa),
            'ma_khoa_hoc_list' => array_keys($byKhoa),
            'da_truyen' => 0,
            'cuoc_khong' => 0,
            'khong_trong_file' => 0,
            'file_khong_co_db' => 0,
            'tong_phien_db' => 0,
            'theo_khoa' => [],
        ];

        sort($aggregate['ma_khoa_hoc_list']);

        DB::connection('sqlsrv_manhlinh')->transaction(function () use (
            $byKhoa,
            $parser,
            $cuocPhanLoaiIds,
            $daTruyenId,
            $cuocKhongId,
            &$aggregate
        ): void {
            foreach ($byKhoa as $maKhoaHoc => $rows) {
                $courseStats = $this->applyForCourse(
                    $maKhoaHoc,
                    $rows,
                    $parser,
                    $cuocPhanLoaiIds,
                    $daTruyenId,
                    $cuocKhongId
                );

                $aggregate['theo_khoa'][$maKhoaHoc] = $courseStats;
                $aggregate['da_truyen'] += $courseStats['da_truyen'];
                $aggregate['cuoc_khong'] += $courseStats['cuoc_khong'];
                $aggregate['khong_trong_file'] += $courseStats['khong_trong_file'];
                $aggregate['file_khong_co_db'] += $courseStats['file_khong_co_db'];
                $aggregate['tong_phien_db'] += $courseStats['tong_phien_db'];
            }
        });

        return $aggregate;
    }

    /**
     * @param  list<array{MaPhienHoc: string, MaKhoaHoc: string, TrangThai: string, KetQuaPhanLoai: string}>  $fileRows
     * @param  list<int>  $cuocPhanLoaiIds
     * @return array{
     *     ma_khoa_hoc: string,
     *     da_truyen: int,
     *     cuoc_khong: int,
     *     khong_trong_file: int,
     *     file_khong_co_db: int,
     *     tong_phien_db: int
     * }
     */
    private function applyForCourse(
        string $maKhoaHoc,
        array $fileRows,
        DatKetQuaCucExcelParser $parser,
        array $cuocPhanLoaiIds,
        int $daTruyenId,
        int $cuocKhongId
    ): array {
        /** @var array<string, string> $fileStatusByMaPhien */
        $fileStatusByMaPhien = [];
        foreach ($fileRows as $row) {
            $fileStatusByMaPhien[$row['MaPhienHoc']] = $row['TrangThai'];
        }

        $sessions = DatDSPhien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->get(['Id', 'MaPhienHoc']);

        $stats = [
            'ma_khoa_hoc' => $maKhoaHoc,
            'da_truyen' => 0,
            'cuoc_khong' => 0,
            'khong_trong_file' => 0,
            'file_khong_co_db' => 0,
            'tong_phien_db' => $sessions->count(),
        ];

        $dbMaPhienSet = [];
        foreach ($sessions as $session) {
            $dbMaPhienSet[$session->MaPhienHoc] = true;
        }

        foreach (array_keys($fileStatusByMaPhien) as $maPhien) {
            if (! isset($dbMaPhienSet[$maPhien])) {
                $stats['file_khong_co_db']++;
            }
        }

        foreach ($sessions as $session) {
            $maPhien = (string) $session->MaPhienHoc;

            if (! array_key_exists($maPhien, $fileStatusByMaPhien)) {
                $this->setCuocPhanLoai($session->Id, $cuocKhongId, $cuocPhanLoaiIds);
                $stats['cuoc_khong']++;
                $stats['khong_trong_file']++;

                continue;
            }

            $targetId = $parser->isKhaDung($fileStatusByMaPhien[$maPhien])
                ? $daTruyenId
                : $cuocKhongId;

            $this->setCuocPhanLoai($session->Id, $targetId, $cuocPhanLoaiIds);

            if ($targetId === $daTruyenId) {
                $stats['da_truyen']++;
            } else {
                $stats['cuoc_khong']++;
            }
        }

        return $stats;
    }

    /**
     * @param  list<int>  $cuocPhanLoaiIds
     */
    private function setCuocPhanLoai(int $datDsPhienId, int $targetPhanLoaiId, array $cuocPhanLoaiIds): void
    {
        $phien = DatDSPhien::query()->findOrFail($datDsPhienId);
        $phien->phanLoai()->detach($cuocPhanLoaiIds);
        $phien->phanLoai()->syncWithoutDetaching([$targetPhanLoaiId]);
    }

    private function ensurePhanLoai(string $tenPhanLoai): DatPhanLoaiPhien
    {
        $existing = DatPhanLoaiPhien::query()
            ->where('TenPhanLoai', $tenPhanLoai)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $maxThuTu = (int) DatPhanLoaiPhien::query()->max('ThuTu');

        return DatPhanLoaiPhien::query()->create([
            'TenPhanLoai' => $tenPhanLoai,
            'MoTa' => null,
            'ThuTu' => $maxThuTu + 1,
            'NgayTao' => now(),
        ]);
    }
}
