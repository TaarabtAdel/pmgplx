<?php

namespace App\Support\DaoTao;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class KetQuaDaoTaoExcelParser
{
    public const DEFAULT_PREVIEW_SAMPLE = 5;

    public const COL_STT = 'A';

    public const COL_MA_HOC_VIEN = 'B';

    public const COL_HO_TEN = 'C';

    public const COL_TG_HINH = 'G';

    public const COL_KM_HINH = 'H';

    public const COL_DIEM_LT = 'I';

    public const COL_DIEM_MO_PHONG = 'J';

    public const COL_DIEM_HINH = 'K';

    public const COL_DIEM_DUONG = 'M';

    public const COL_NGAY_HTKH = 'O';

    private const HEADER_SCAN_MAX_ROW = 15;

    /**
     * @return array{
     *     file_name: string,
     *     sheet_name: string,
     *     records: list<array<string, mixed>>,
     *     meta: array{
     *         record_count: int,
     *         header_row: int,
     *         data_start_row: int,
     *         preview_limit: int
     *     }
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName, int $previewSampleLimit = self::DEFAULT_PREVIEW_SAMPLE): array
    {
        $worksheet = $spreadsheet->getSheet(0);
        $context = $this->resolveSheetContext($worksheet);
        $all = $this->readRecords($worksheet, $context);

        if ($all === []) {
            throw new RuntimeException('Không tìm thấy mã học viên ở cột B.');
        }

        return [
            'file_name' => $fileName,
            'sheet_name' => $worksheet->getTitle(),
            'records' => array_slice($all, 0, $previewSampleLimit),
            'meta' => [
                'record_count' => count($all),
                'header_row' => $context['header_row'],
                'data_start_row' => $context['data_start_row'],
                'preview_limit' => $previewSampleLimit,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readAllRecordsFromFile(string $path): array
    {
        $spreadsheet = DatDSPhienExcelParser::loadSpreadsheet($path);

        try {
            $worksheet = $spreadsheet->getSheet(0);
            $context = $this->resolveSheetContext($worksheet);

            return $this->readRecords($worksheet, $context);
        } finally {
            DatDSPhienExcelParser::releaseSpreadsheet($spreadsheet);
        }
    }

    /**
     * @return array{header_row: int, data_start_row: int}
     */
    private function resolveSheetContext(Worksheet $worksheet): array
    {
        $highestRow = min((int) $worksheet->getHighestRow(), self::HEADER_SCAN_MAX_ROW);

        for ($row = 1; $row <= $highestRow; $row++) {
            $headerB = $this->normalizeHeader(
                (string) $worksheet->getCell(self::COL_MA_HOC_VIEN.$row)->getCalculatedValue()
            );

            if ($headerB === 'ma hoc vien') {
                return [
                    'header_row' => $row,
                    'data_start_row' => $row + 1,
                ];
            }
        }

        throw new RuntimeException('Không tìm thấy dòng tiêu đề có cột "Mã học viên" (cột B).');
    }

    /**
     * @param  array{header_row: int, data_start_row: int}  $context
     * @return list<array<string, mixed>>
     */
    private function readRecords(Worksheet $worksheet, array $context): array
    {
        $records = [];
        $highestRow = (int) $worksheet->getHighestRow();

        for ($row = $context['data_start_row']; $row <= $highestRow; $row++) {
            $maHocVien = $this->cellText($worksheet, self::COL_MA_HOC_VIEN.$row);
            if ($maHocVien === '' || $this->normalizeHeader($maHocVien) === 'ma hoc vien') {
                continue;
            }

            $records[] = [
                'excel_row' => $row,
                'stt' => $this->cellText($worksheet, self::COL_STT.$row),
                'ma_hoc_vien' => $maHocVien,
                'ho_ten' => $this->cellText($worksheet, self::COL_HO_TEN.$row),
                'tg_thuc_hanh_hinh' => $this->cellFloat($worksheet, self::COL_TG_HINH.$row),
                'qd_thuc_hanh_hinh' => $this->cellFloat($worksheet, self::COL_KM_HINH.$row),
                'diem_kq_ly_thuyet' => $this->cellFloat($worksheet, self::COL_DIEM_LT.$row),
                'diem_kq_mo_phong' => $this->cellFloat($worksheet, self::COL_DIEM_MO_PHONG.$row),
                'diem_kq_hinh' => $this->cellFloat($worksheet, self::COL_DIEM_HINH.$row),
                'diem_kq_thuc_hanh' => $this->cellFloat($worksheet, self::COL_DIEM_DUONG.$row),
                'ngay_ra_kqtn' => $this->cellDate($worksheet, self::COL_NGAY_HTKH.$row),
            ];
        }

        return $records;
    }

    private function cellText(Worksheet $worksheet, string $coord): string
    {
        $value = $worksheet->getCell($coord)->getCalculatedValue();
        if ($value === null) {
            return '';
        }

        return trim(ltrim(trim((string) $value), "'"));
    }

    private function cellFloat(Worksheet $worksheet, string $coord): ?float
    {
        $value = $worksheet->getCell($coord)->getCalculatedValue();
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = str_replace([' ', ','], ['', '.'], $this->cellText($worksheet, $coord));

        return is_numeric($text) ? (float) $text : null;
    }

    private function cellDate(Worksheet $worksheet, string $coord): ?string
    {
        $value = $worksheet->getCell($coord)->getCalculatedValue();
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $text = $this->cellText($worksheet, $coord);
        if ($text === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $text);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
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
