<?php

namespace App\Support\DaoTao;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class DatKetQuaCucExcelParser
{
    public const DEFAULT_PREVIEW_SAMPLE = 5;

    public const COL_MA_PHIEN = 'B';

    public const COL_MA_KHOA = 'K';

    public const COL_TRANG_THAI = 'S';

    private const HEADER_SCAN_MAX_ROW = 30;

    private const TRANG_THAI_KHA_DUNG = 'Khả dụng';

    /**
     * @return array{
     *     file_name: string,
     *     sheet_name: string,
     *     records: list<array{MaPhienHoc: string, MaKhoaHoc: string, TrangThai: string, KetQuaPhanLoai: string}>,
     *     meta: array{
     *         record_count: int,
     *         skipped_count: int,
     *         header_row: int,
     *         data_start_row: int,
     *         ma_khoa_hoc: string,
     *         preview_limit: int,
     *         count_da_truyen: int,
     *         count_khong_chap_nhan: int
     *     }
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName, int $previewSampleLimit = self::DEFAULT_PREVIEW_SAMPLE): array
    {
        $worksheet = $spreadsheet->getSheet(0);
        $context = $this->resolveSheetContext($worksheet);
        $allRecords = $this->readRecords($worksheet, $context);

        $maKhoaHoc = $this->resolveMaKhoaHoc($allRecords);
        $recordsForCourse = array_values(array_filter(
            $allRecords,
            static fn (array $row): bool => $row['MaKhoaHoc'] === $maKhoaHoc
        ));

        $countDaTruyen = 0;
        $countKhongChapNhan = 0;
        foreach ($recordsForCourse as $row) {
            if ($this->isKhaDung($row['TrangThai'])) {
                $countDaTruyen++;
            } else {
                $countKhongChapNhan++;
            }
        }

        $previewRecords = array_slice($recordsForCourse, 0, $previewSampleLimit);

        return [
            'file_name' => $fileName,
            'sheet_name' => $worksheet->getTitle(),
            'records' => $previewRecords,
            'meta' => [
                'record_count' => count($recordsForCourse),
                'skipped_count' => count($allRecords) - count($recordsForCourse),
                'header_row' => $context['header_row'],
                'data_start_row' => $context['data_start_row'],
                'ma_khoa_hoc' => $maKhoaHoc,
                'preview_limit' => $previewSampleLimit,
                'count_da_truyen' => $countDaTruyen,
                'count_khong_chap_nhan' => $countKhongChapNhan,
            ],
        ];
    }

    /**
     * @return list<array{MaPhienHoc: string, MaKhoaHoc: string, TrangThai: string, KetQuaPhanLoai: string}>
     */
    public function readAllRecordsFromFile(string $path): array
    {
        $spreadsheet = DatDSPhienExcelParser::loadSpreadsheet($path);

        try {
            $worksheet = $spreadsheet->getSheet(0);
            $context = $this->resolveSheetContext($worksheet);
            $allRecords = $this->readRecords($worksheet, $context);
            $maKhoaHoc = $this->resolveMaKhoaHoc($allRecords);

            return array_values(array_filter(
                $allRecords,
                static fn (array $row): bool => $row['MaKhoaHoc'] === $maKhoaHoc
            ));
        } finally {
            DatDSPhienExcelParser::releaseSpreadsheet($spreadsheet);
        }
    }

    public function isKhaDung(string $trangThai): bool
    {
        return mb_stripos($trangThai, self::TRANG_THAI_KHA_DUNG) !== false;
    }

    public function ketQuaPhanLoaiLabel(string $trangThai): string
    {
        return $this->isKhaDung($trangThai)
            ? DatKetQuaCucUpdater::PHAN_LOAI_DA_TRUYEN
            : DatKetQuaCucUpdater::PHAN_LOAI_CUOC_KHONG;
    }

    /**
     * @return array{header_row: int, data_start_row: int}
     */
    private function resolveSheetContext(Worksheet $worksheet): array
    {
        $highestRow = min((int) $worksheet->getHighestRow(), self::HEADER_SCAN_MAX_ROW);

        for ($row = 1; $row <= $highestRow; $row++) {
            $headerB = $this->normalizeHeader(
                (string) $worksheet->getCell(self::COL_MA_PHIEN.$row)->getCalculatedValue()
            );

            if ($headerB === 'ma phien hoc') {
                return [
                    'header_row' => $row,
                    'data_start_row' => $row + 1,
                ];
            }
        }

        throw new RuntimeException('Không tìm thấy dòng tiêu đề có cột "Mã phiên học" (cột B).');
    }

    /**
     * @param  array{header_row: int, data_start_row: int}  $context
     * @return list<array{MaPhienHoc: string, MaKhoaHoc: string, TrangThai: string, KetQuaPhanLoai: string}>
     */
    private function readRecords(Worksheet $worksheet, array $context): array
    {
        $records = [];
        $highestRow = (int) $worksheet->getHighestRow();

        for ($row = $context['data_start_row']; $row <= $highestRow; $row++) {
            $maPhien = trim((string) $worksheet->getCell(self::COL_MA_PHIEN.$row)->getCalculatedValue());
            if ($maPhien === '') {
                continue;
            }

            $maKhoa = trim((string) $worksheet->getCell(self::COL_MA_KHOA.$row)->getCalculatedValue());
            $trangThai = trim((string) $worksheet->getCell(self::COL_TRANG_THAI.$row)->getCalculatedValue());

            $records[] = [
                'MaPhienHoc' => $maPhien,
                'MaKhoaHoc' => $maKhoa,
                'TrangThai' => $trangThai,
                'KetQuaPhanLoai' => $this->ketQuaPhanLoaiLabel($trangThai),
            ];
        }

        return $records;
    }

    /**
     * @param  list<array{MaPhienHoc: string, MaKhoaHoc: string, TrangThai: string, KetQuaPhanLoai: string}>  $records
     */
    private function resolveMaKhoaHoc(array $records): string
    {
        if ($records === []) {
            throw new RuntimeException('File không có dòng dữ liệu hợp lệ.');
        }

        $codes = [];
        foreach ($records as $row) {
            $code = trim($row['MaKhoaHoc']);
            if ($code === '') {
                continue;
            }
            $codes[$code] = ($codes[$code] ?? 0) + 1;
        }

        if ($codes === []) {
            throw new RuntimeException('Không tìm thấy mã khóa học ở cột K.');
        }

        if (count($codes) > 1) {
            throw new RuntimeException(
                'File có nhiều mã khóa học khác nhau ('.implode(', ', array_keys($codes)).'). Mỗi file chỉ xử lý một khóa.'
            );
        }

        return (string) array_key_first($codes);
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);

        return trim($value);
    }
}
