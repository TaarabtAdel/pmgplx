<?php

namespace App\Support\DaoTao;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Generator;

class DatDSPhienExcelParser
{
    private const HEADER_SCAN_MAX_ROW = 30;

    public const DEFAULT_PREVIEW_SAMPLE = 5;

    /** @var array<string, int> */
    private const FALLBACK_COLUMNS = [
        'STT' => 1,
        'MaPhienHoc' => 2,
        'TiLeNhanDien' => 3,
        'ThoiGianBatDauPhienHoc' => 4,
        'ThoiGianKetThucPhienHoc' => 5,
        'ThoiGianThucHanhGio' => 6,
        'QuangDuongThucHanhKm' => 7,
        'ThoiGianLaiBanDemGio' => 8,
        'ThoiGianLaiXeSoTuDong' => 9,
        'ThoiGianMayChuNhanPhienHoc' => 10,
        'MaHocVien' => 11,
        'HoTenHocVien' => 12,
        'MaKhoaHoc' => 13,
        'TenKhoaHoc' => 14,
        'LoaiKhoaHoc' => 15,
        'HoTenGiaoVien' => 16,
        'MaGiaoVien' => 17,
        'BienSoXe' => 18,
        'MaThietBi' => 19,
    ];

    /**
     * @return array{
     *     file_name: string,
     *     sheet_name: string,
     *     records: list<array<string, mixed>>,
     *     meta: array{record_count: int, skipped_count: int, header_row: int, data_start_row: int, date_range: ?string, preview_limit: int}
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName, int $previewSampleLimit = self::DEFAULT_PREVIEW_SAMPLE): array
    {
        $worksheet = $spreadsheet->getSheet(0);
        $context = $this->resolveSheetContext($worksheet);
        $previewRecords = [];
        $recordCount = 0;
        $skipped = 0;

        foreach ($this->iterateRecords($worksheet, $context) as $record) {
            $recordCount++;
            if (count($previewRecords) < $previewSampleLimit) {
                $previewRecords[] = $record;
            }
        }

        $skipped = $context['skipped_count'];

        if ($recordCount === 0) {
            throw new \InvalidArgumentException('Không tìm thấy dữ liệu phiên học hợp lệ trong file (cần cột Mã phiên học).');
        }

        return [
            'file_name' => $fileName,
            'sheet_name' => $context['sheet_name'],
            'records' => $previewRecords,
            'meta' => [
                'record_count' => $recordCount,
                'skipped_count' => $skipped,
                'header_row' => $context['header_row'],
                'data_start_row' => $context['data_start_row'],
                'date_range' => $context['date_range'],
                'preview_limit' => $previewSampleLimit,
            ],
        ];
    }

    public static function loadSpreadsheet(string $path): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        return $reader->load($path);
    }

    public static function releaseSpreadsheet(?Spreadsheet $spreadsheet): void
    {
        if ($spreadsheet === null) {
            return;
        }

        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @return Generator<int, array<string, mixed>, mixed, void>
     */
    public function iterateRecordsFromFile(string $path): Generator
    {
        $spreadsheet = self::loadSpreadsheet($path);

        try {
            $worksheet = $spreadsheet->getSheet(0);
            $context = $this->resolveSheetContext($worksheet);

            yield from $this->iterateRecords($worksheet, $context);
        } finally {
            self::releaseSpreadsheet($spreadsheet);
        }
    }

    /**
     * @return array{
     *     sheet_name: string,
     *     header_row: int,
     *     data_start_row: int,
     *     date_range: ?string,
     *     columns: array<string, int>,
     *     skipped_count: int
     * }
     */
    private function resolveSheetContext(Worksheet $worksheet): array
    {
        $headerRow = $this->findHeaderRow($worksheet);
        if ($headerRow === null) {
            throw new \InvalidArgumentException('Không tìm thấy dòng tiêu đề (STT, Mã phiên học) trong file Excel.');
        }

        $subHeaderRow = $this->isSubHeaderRow($worksheet, $headerRow + 1) ? $headerRow + 1 : null;
        $dataStartRow = ($subHeaderRow ?? $headerRow) + 1;
        $columns = $this->resolveColumns($worksheet, $headerRow, $subHeaderRow);

        return [
            'sheet_name' => trim((string) $worksheet->getTitle()),
            'header_row' => $headerRow,
            'data_start_row' => $dataStartRow,
            'date_range' => $this->parseDateRangeLine($worksheet),
            'columns' => $columns,
            'skipped_count' => 0,
        ];
    }

    /**
     * @param  array{
     *     sheet_name: string,
     *     header_row: int,
     *     data_start_row: int,
     *     date_range: ?string,
     *     columns: array<string, int>,
     *     skipped_count: int
     * }  $context
     * @return Generator<int, array<string, mixed>, mixed, void>
     */
    private function iterateRecords(Worksheet $worksheet, array &$context): Generator
    {
        $columns = $context['columns'];
        $dataStartRow = $context['data_start_row'];
        $highestRow = $this->highestDataRow($worksheet, $dataStartRow, $columns['MaPhienHoc']);

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $record = $this->parseRow($worksheet, $row, $columns);
            if ($record === null) {
                continue;
            }

            if ($record['MaPhienHoc'] === '') {
                $context['skipped_count']++;

                continue;
            }

            $record['excel_row'] = $row;
            yield $record;
        }
    }

    private function highestDataRow(Worksheet $sheet, int $dataStartRow, int $maPhienCol): int
    {
        $colLetter = Coordinate::stringFromColumnIndex($maPhienCol);
        $highestRow = (int) $sheet->getHighestDataRow($colLetter);

        return max($dataStartRow, $highestRow);
    }

    private function findHeaderRow(Worksheet $sheet): ?int
    {
        $maxRow = min(self::HEADER_SCAN_MAX_ROW, (int) $sheet->getHighestDataRow());

        for ($row = 1; $row <= $maxRow; $row++) {
            $col1 = $this->normalizeHeader($this->cellText($sheet, $row, 1));
            $col2 = $this->normalizeHeader($this->cellText($sheet, $row, 2));

            if ($col1 === 'stt' && str_contains($col2, 'ma phien hoc')) {
                return $row;
            }
        }

        return null;
    }

    private function isSubHeaderRow(Worksheet $sheet, int $row): bool
    {
        if ($row > (int) $sheet->getHighestDataRow()) {
            return false;
        }

        $parts = [];
        $maxCol = min(12, Coordinate::columnIndexFromString($sheet->getHighestDataColumn($row)));
        for ($col = 1; $col <= $maxCol; $col++) {
            $parts[] = $this->normalizeHeader($this->cellText($sheet, $row, $col));
        }

        $text = implode(' ', array_filter($parts));
        if ($text === '') {
            return false;
        }

        return str_contains($text, 'phien hoc')
            || str_contains($text, 'csdt truyen len')
            || str_contains($text, 'hoc vien')
            || str_contains($text, 'giao vien');
    }

    /**
     * @return array<string, int>
     */
    private function resolveColumns(Worksheet $sheet, int $headerRow, ?int $subHeaderRow): array
    {
        $columns = self::FALLBACK_COLUMNS;
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($headerRow));

        for ($col = 1; $col <= $highestCol; $col++) {
            $line1 = $this->normalizeHeader($this->cellText($sheet, $headerRow, $col));
            $line2 = $subHeaderRow !== null
                ? $this->normalizeHeader($this->cellText($sheet, $subHeaderRow, $col))
                : '';
            $combined = trim($line1.' '.$line2);

            if ($combined === '') {
                continue;
            }

            $field = $this->mapHeaderToField($line1, $line2, $combined);
            if ($field !== null) {
                $columns[$field] = $col;
            }
        }

        return $columns;
    }

    private function mapHeaderToField(string $line1, string $line2, string $combined): ?string
    {
        if ($line1 === 'stt') {
            return 'STT';
        }

        if (str_contains($combined, 'ma phien hoc')) {
            return 'MaPhienHoc';
        }

        if (str_contains($combined, 'ti le nhan dien')) {
            return 'TiLeNhanDien';
        }

        if (str_contains($line1, 'thoi gian bat dau') || (str_contains($combined, 'bat dau') && str_contains($combined, 'phien hoc'))) {
            return 'ThoiGianBatDauPhienHoc';
        }

        if (str_contains($line1, 'thoi gian ket thuc') || (str_contains($combined, 'ket thuc') && str_contains($combined, 'phien hoc'))) {
            return 'ThoiGianKetThucPhienHoc';
        }

        if (str_contains($combined, 'thoi gian thuc hanh')) {
            return 'ThoiGianThucHanhGio';
        }

        if (str_contains($combined, 'quang duong thuc hanh')) {
            return 'QuangDuongThucHanhKm';
        }

        if (str_contains($combined, 'ban dem')) {
            return 'ThoiGianLaiBanDemGio';
        }

        if (str_contains($combined, 'tu dong')) {
            return 'ThoiGianLaiXeSoTuDong';
        }

        if (str_contains($combined, 'may chu')) {
            return 'ThoiGianMayChuNhanPhienHoc';
        }

        if (str_contains($combined, 'ma hoc vien')) {
            return 'MaHocVien';
        }

        if (str_contains($line2, 'hoc vien') || str_contains($combined, 'ho va ten hoc vien')) {
            return 'HoTenHocVien';
        }

        if (str_contains($combined, 'ma khoa hoc')) {
            return 'MaKhoaHoc';
        }

        if (str_contains($combined, 'ten khoa hoc')) {
            return 'TenKhoaHoc';
        }

        if (str_contains($combined, 'loai khoa hoc')) {
            return 'LoaiKhoaHoc';
        }

        if (str_contains($line2, 'giao vien') || str_contains($combined, 'ho va ten giao vien')) {
            return 'HoTenGiaoVien';
        }

        if (str_contains($combined, 'ma giao vien')) {
            return 'MaGiaoVien';
        }

        if (str_contains($combined, 'bien so xe')) {
            return 'BienSoXe';
        }

        if (str_contains($combined, 'ma thiet bi')) {
            return 'MaThietBi';
        }

        return null;
    }

    /**
     * @param  array<string, int>  $columns
     * @return array<string, mixed>|null
     */
    private function parseRow(Worksheet $sheet, int $row, array $columns): ?array
    {
        $values = [];
        foreach ($columns as $field => $col) {
            $values[$field] = $this->cellRaw($sheet, $row, $col);
        }

        $hasData = false;
        foreach ($values as $value) {
            if ($this->cellTextValue($value) !== '') {
                $hasData = true;
                break;
            }
        }

        if (! $hasData) {
            return null;
        }

        return [
            'MaPhienHoc' => $this->cellTextValue($values['MaPhienHoc'] ?? null),
            'TiLeNhanDien' => $this->cellFloat($values['TiLeNhanDien'] ?? null),
            'ThoiGianBatDauPhienHoc' => $this->cellDateTime($values['ThoiGianBatDauPhienHoc'] ?? null),
            'ThoiGianKetThucPhienHoc' => $this->cellDateTime($values['ThoiGianKetThucPhienHoc'] ?? null),
            'ThoiGianThucHanhGio' => $this->cellFloat($values['ThoiGianThucHanhGio'] ?? null),
            'QuangDuongThucHanhKm' => $this->cellFloat($values['QuangDuongThucHanhKm'] ?? null),
            'ThoiGianLaiBanDemGio' => $this->cellFloat($values['ThoiGianLaiBanDemGio'] ?? null),
            'ThoiGianLaiXeSoTuDong' => $this->cellFloat($values['ThoiGianLaiXeSoTuDong'] ?? null),
            'ThoiGianMayChuNhanPhienHoc' => $this->cellDateTime($values['ThoiGianMayChuNhanPhienHoc'] ?? null),
            'MaHocVien' => $this->cellTextValue($values['MaHocVien'] ?? null),
            'HoTenHocVien' => $this->cellTextValue($values['HoTenHocVien'] ?? null),
            'MaKhoaHoc' => $this->cellTextValue($values['MaKhoaHoc'] ?? null),
            'TenKhoaHoc' => $this->cellTextValue($values['TenKhoaHoc'] ?? null),
            'LoaiKhoaHoc' => $this->cellTextValue($values['LoaiKhoaHoc'] ?? null),
            'HoTenGiaoVien' => $this->cellTextValue($values['HoTenGiaoVien'] ?? null),
            'MaGiaoVien' => $this->cellTextValue($values['MaGiaoVien'] ?? null),
            'BienSoXe' => $this->cellTextValue($values['BienSoXe'] ?? null),
            'MaThietBi' => $this->cellTextValue($values['MaThietBi'] ?? null),
        ];
    }

    private function parseDateRangeLine(Worksheet $sheet): ?string
    {
        for ($row = 1; $row <= min(5, (int) $sheet->getHighestDataRow()); $row++) {
            $text = $this->cellText($sheet, $row, 1).' '.$this->cellText($sheet, $row, 4);
            $text = trim($text);
            if (str_contains(mb_strtolower($text), 'từ ngày') || str_contains(mb_strtolower($text), 'tu ngay')) {
                return $text !== '' ? $text : null;
            }
        }

        return null;
    }

    private function cellRaw(Worksheet $sheet, int $row, int $col): mixed
    {
        return $sheet->getCell([$col, $row])->getCalculatedValue();
    }

    private function cellText(Worksheet $sheet, int $row, int $col): string
    {
        return $this->cellTextValue($this->cellRaw($sheet, $row, $col));
    }

    private function cellTextValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = trim((string) $value);
        $text = ltrim($text, "'");

        return trim($text);
    }

    private function cellInt(mixed $value): ?int
    {
        $text = $this->cellTextValue($value);
        if ($text === '') {
            return null;
        }

        return is_numeric($text) ? (int) $text : null;
    }

    private function cellFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = str_replace([' ', ','], ['', '.'], $this->cellTextValue($value));

        return is_numeric($text) ? (float) $text : null;
    }

    private function cellDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d H:i:s');
        }

        $text = $this->cellTextValue($value);
        if ($text === '') {
            return null;
        }

        $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $text)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                // thử format tiếp theo
            }
        }

        try {
            return Carbon::parse($text)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeHeader(?string $value): string
    {
        $value = mb_strtolower(trim((string) ($value ?? '')));
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
        $value = str_replace(['(', ')', '•'], [' ', ' ', ' '], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
