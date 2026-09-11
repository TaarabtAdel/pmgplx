<?php

namespace App\Support\DaoTao;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DatPhanCongHocVienExcelParser
{
    public const DEFAULT_PREVIEW_SAMPLE = 10;

    private const HEADER_SCAN_MAX_ROW = 20;

    /** @var array<string, int> */
    private const FALLBACK_COLUMNS = [
        'STT' => 1,
        'HoTenHocVien' => 2,
        'MaHocVien' => 3,
        'BienSoXe' => 4,
        'BienSoXeTuDong' => 5,
        'MaGiaoVien' => 6,
    ];

    /**
     * @return array{
     *     file_name: string,
     *     sheet_name: string,
     *     records: list<array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName, int $previewSampleLimit = self::DEFAULT_PREVIEW_SAMPLE): array
    {
        $worksheet = $spreadsheet->getSheet(0);
        $titleA1 = $this->cellText($worksheet, 1, 1);
        $maKhoaHoc = self::extractMaKhoaHocFromTitle($titleA1);

        if ($maKhoaHoc === '') {
            throw new \InvalidArgumentException('Không đọc được mã khóa học từ ô A1 (cần dạng "... KHÓA BK55").');
        }

        $context = $this->resolveSheetContext($worksheet);
        $allRecords = [];

        foreach ($this->iterateRecords($worksheet, $context) as $record) {
            $allRecords[] = $record;
        }

        if ($allRecords === []) {
            throw new \InvalidArgumentException('Không tìm thấy dòng phân công hợp lệ (cần cột Mã học viên).');
        }

        $allRecords = DatPhanCongHocVienSaver::dedupeByMaHocVienKeepLast($allRecords);
        $previewRecords = array_slice($allRecords, 0, $previewSampleLimit);

        $gvSet = DatPhanCongHocVienLookup::existingMaGiaoVienSet(array_column($allRecords, 'MaGiaoVien'));

        $annotatedAll = $this->annotateRecords($allRecords, $gvSet);
        $annotatedPreview = $this->annotateRecords($previewRecords, $gvSet);

        $stats = self::buildStats($annotatedAll);

        return [
            'file_name' => $fileName,
            'sheet_name' => trim((string) $worksheet->getTitle()),
            'records' => $annotatedPreview,
            'meta' => array_merge([
                'ma_khoa_hoc' => $maKhoaHoc,
                'title_a1' => $titleA1,
                'record_count' => count($allRecords),
                'preview_limit' => $previewSampleLimit,
                'header_row' => $context['header_row'],
                'data_start_row' => $context['data_start_row'],
                'all_records' => $annotatedAll,
            ], $stats),
        ];
    }

    public static function extractMaKhoaHocFromTitle(?string $title): string
    {
        $text = trim((string) $title);
        if ($text === '') {
            return '';
        }

        if (preg_match('/KH[OÓAÀÂÃÁẠẢĂẰẮẲẴẶẤẦẨẪẬÍI]\s*([A-Z0-9\-]+)/ui', $text, $matches)) {
            return strtoupper(trim($matches[1]));
        }

        $parts = preg_split('/\s+/u', $text) ?: [];
        $last = trim((string) end($parts));

        return $last !== '' ? strtoupper($last) : '';
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    private function annotateRecords(array $records, Collection $gvSet): array
    {
        return array_map(function (array $record) use ($gvSet): array {
            $errors = [];
            $warnings = [];

            $maHocVien = trim((string) ($record['MaHocVien'] ?? ''));
            if ($maHocVien === '') {
                $errors[] = 'Thiếu mã học viên';
            }

            $maGv = trim((string) ($record['MaGiaoVien'] ?? ''));
            if ($maGv === '') {
                $errors[] = 'Thiếu mã giáo viên';
            } elseif (! $gvSet->has($maGv)) {
                $warnings[] = 'Mã giáo viên chưa có trong danh mục PMGPLX';
            }

            $record['errors'] = $errors;
            $record['warnings'] = $warnings;
            $record['can_save'] = $errors === [];

            return $record;
        }, $records);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{save_count: int, error_count: int, warning_count: int, gv_count: int, xe_count: int}
     */
    private static function buildStats(array $records): array
    {
        $saveCount = 0;
        $errorCount = 0;
        $warningCount = 0;
        $gvSet = [];
        $xeSet = [];

        foreach ($records as $record) {
            if (! empty($record['can_save'])) {
                $saveCount++;
            }
            if (! empty($record['errors'])) {
                $errorCount++;
            }
            if (! empty($record['warnings'])) {
                $warningCount++;
            }

            $maGv = trim((string) ($record['MaGiaoVien'] ?? ''));
            if ($maGv !== '') {
                $gvSet[$maGv] = true;
            }

            $xe = trim((string) ($record['BienSoXe'] ?? ''));
            if ($xe !== '') {
                $xeSet[$xe] = true;
            }

            $xeTd = trim((string) ($record['BienSoXeTuDong'] ?? ''));
            if ($xeTd !== '') {
                $xeSet[$xeTd] = true;
            }
        }

        return [
            'save_count' => $saveCount,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'gv_count' => count($gvSet),
            'xe_count' => count($xeSet),
        ];
    }

    /**
     * @return array{header_row: int, data_start_row: int, columns: array<string, int>, sheet_name: string, skipped_count: int}
     */
    private function resolveSheetContext(Worksheet $worksheet): array
    {
        $headerRow = null;
        $columns = self::FALLBACK_COLUMNS;

        for ($row = 1; $row <= self::HEADER_SCAN_MAX_ROW; $row++) {
            $candidate = $this->detectColumns($worksheet, $row);
            if ($candidate !== null) {
                $headerRow = $row;
                $columns = $candidate;
                break;
            }
        }

        if ($headerRow === null) {
            throw new \InvalidArgumentException('Không tìm thấy dòng tiêu đề (STT, Họ và tên, Mã học viên).');
        }

        return [
            'header_row' => $headerRow,
            'data_start_row' => $headerRow + 1,
            'columns' => $columns,
            'sheet_name' => trim((string) $worksheet->getTitle()),
            'skipped_count' => 0,
        ];
    }

    /**
     * @return array<string, int>|null
     */
    private function detectColumns(Worksheet $worksheet, int $row): ?array
    {
        $columns = self::FALLBACK_COLUMNS;
        $foundMaHocVien = false;
        $foundStt = false;

        $highestCol = min(10, Coordinate::columnIndexFromString($worksheet->getHighestDataColumn($row)));
        for ($col = 1; $col <= $highestCol; $col++) {
            $header = $this->normalizeHeader($this->cellText($worksheet, $row, $col));
            if ($header === '') {
                continue;
            }

            if (
                str_contains($header, 'ma hoc vien')
                || str_contains($header, 'ma hv')
                || str_contains($header, 'ma dk')
                || str_contains($header, 'ma dang ky')
            ) {
                $columns['MaHocVien'] = $col;
                $foundMaHocVien = true;
            }
            if ($header === 'stt' || str_starts_with($header, 'stt ')) {
                $columns['STT'] = $col;
                $foundStt = true;
            }
            if (str_contains($header, 'ho va ten') || str_contains($header, 'ho ten')) {
                $columns['HoTenHocVien'] = $col;
            }
            if (str_contains($header, 'tu dong') || str_contains($header, 'so tu dong')) {
                $columns['BienSoXeTuDong'] = $col;
            } elseif (str_contains($header, 'tap lai') || str_contains($header, 'xe tap')) {
                $columns['BienSoXe'] = $col;
            } elseif (
                (str_contains($header, 'xe') || str_contains($header, 'bks') || str_contains($header, 'bien so'))
                && ! str_contains($header, 'tu dong')
            ) {
                $columns['BienSoXe'] = $col;
            }
            if (str_contains($header, 'ma giao vien') || str_contains($header, 'ma gv')) {
                $columns['MaGiaoVien'] = $col;
            }
        }

        return ($foundMaHocVien && $foundStt) ? $columns : null;
    }

    /**
     * @param  array{header_row: int, data_start_row: int, columns: array<string, int>}  $context
     * @return \Generator<int, array<string, mixed>>
     */
    private function iterateRecords(Worksheet $worksheet, array $context): \Generator
    {
        $columns = $context['columns'];
        $dataStart = $context['data_start_row'];
        $highestRow = $worksheet->getHighestDataRow();
        $maKhoaHoc = self::extractMaKhoaHocFromTitle($this->cellText($worksheet, 1, 1));

        for ($row = $dataStart; $row <= $highestRow; $row++) {
            $maHocVien = DatPhanCongHocVienSaver::normalizeMaHocVien(
                $this->cellText($worksheet, $row, $columns['MaHocVien'])
            );
            if ($maHocVien === '') {
                continue;
            }

            $maGiaoVien = $this->normalizeMaGiaoVien($this->cellText($worksheet, $row, $columns['MaGiaoVien']));
            if ($maGiaoVien === '') {
                continue;
            }

            $bienSoRaw = $this->cellText($worksheet, $row, $columns['BienSoXe']);
            $bienSo = DatPhanCongHocVienSaver::normalizeBienSo($bienSoRaw);

            $bienSoTuDongRaw = '';
            $bienSoTuDong = '';
            if (isset($columns['BienSoXeTuDong'])) {
                $bienSoTuDongRaw = $this->cellText($worksheet, $row, $columns['BienSoXeTuDong']);
                $bienSoTuDong = DatPhanCongHocVienSaver::normalizeBienSo($bienSoTuDongRaw);
            }

            yield [
                'row_number' => $row,
                'STT' => $this->cellText($worksheet, $row, $columns['STT']),
                'HoTenHocVien' => $this->cellText($worksheet, $row, $columns['HoTenHocVien']),
                'MaHocVien' => $maHocVien,
                'BienSoXe' => $bienSo,
                'BienSoXeHienThi' => trim($bienSoRaw),
                'BienSoXeTuDong' => $bienSoTuDong,
                'BienSoXeTuDongHienThi' => trim($bienSoTuDongRaw),
                'MaGiaoVien' => $maGiaoVien,
                'MaKhoaHoc' => $maKhoaHoc,
            ];
        }
    }

    private function normalizeMaGiaoVien(?string $value): string
    {
        return DatPhanCongHocVienSaver::normalizeMaGiaoVien($value);
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
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
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function cellText(Worksheet $worksheet, int $row, int $col): string
    {
        $value = $worksheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getCalculatedValue();

        if ($value === null) {
            return '';
        }

        if (is_float($value) && fmod($value, 1.0) === 0.0) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }
}
