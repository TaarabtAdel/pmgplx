<?php

namespace App\Support\DaoTao;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TienDoDaoTaoExcelParser
{
    private const HEADER_ROW = 5;

    private const TUAN_ROW = 6;

    private const TU_NGAY_ROW = 7;

    private const DEN_NGAY_ROW = 8;

    private const DATA_START_ROW = 9;

    /**
     * @return array{
     *     file_name: string,
     *     sheets: list<array<string, mixed>>,
     *     meta: array{sheet_count: int, class_count: int, record_count: int}
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName): array
    {
        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $parsed = $this->parseSheet($worksheet);
            if ($parsed !== null) {
                $sheets[] = $parsed;
            }
        }

        if ($sheets === []) {
            throw new \InvalidArgumentException('Không tìm thấy dữ liệu tiến độ đào tạo hợp lệ trong file.');
        }

        return [
            'file_name' => $fileName,
            'sheets' => $sheets,
            'meta' => [
                'sheet_count' => count($sheets),
                'class_count' => array_sum(array_map(fn (array $s) => (int) ($s['meta']['class_count'] ?? 0), $sheets)),
                'record_count' => array_sum(array_map(fn (array $s) => (int) ($s['meta']['record_count'] ?? 0), $sheets)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseSheet(Worksheet $sheet): ?array
    {
        $sheetName = trim((string) $sheet->getTitle());
        $namHoc = $this->parseNamHoc($sheetName);

        $weekStartCol = Coordinate::columnIndexFromString('F');
        $weekEndCol = Coordinate::columnIndexFromString('BM');
        $colTotNghiep = Coordinate::columnIndexFromString('BN');
        $colGhiChu = Coordinate::columnIndexFromString('BO');

        $weekMap = $this->buildWeekColumnMap($sheet, $weekStartCol, $weekEndCol);
        if ($weekMap === []) {
            return null;
        }

        $records = [];
        $classes = [];
        $highestRow = max(self::DATA_START_ROW, (int) $sheet->getHighestDataRow());

        for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
            $maKhoaLop = $this->cellText($sheet, $row, Coordinate::columnIndexFromString('C'));
            if ($maKhoaLop === '') {
                continue;
            }

            $soTT = $this->cellInt($sheet, $row, Coordinate::columnIndexFromString('B'));
            $giaoVienDay = $this->cellText($sheet, $row, Coordinate::columnIndexFromString('D'));
            $soLuongHocVien = $this->cellInt($sheet, $row, Coordinate::columnIndexFromString('E'));
            $soTotNghiep = $this->cellInt($sheet, $row, $colTotNghiep);
            $ghiChuLop = $this->cellText($sheet, $row, $colGhiChu);

            $kyHieuCount = 0;
            foreach ($weekMap as $col => $week) {
                $kyHieu = $this->normalizeKyHieu($this->cellText($sheet, $row, $col));
                if ($kyHieu === '') {
                    continue;
                }

                $kyHieuCount++;

                $records[] = [
                    'sheet_name' => $sheetName,
                    'nam_hoc' => $namHoc,
                    'MaKhoaLop' => $maKhoaLop,
                    'GiaoVienDay' => $giaoVienDay,
                    'SoLuongHocVien' => $soLuongHocVien,
                    'SoHocVienTotNghiep' => $soTotNghiep,
                    'ThangNam' => $week['ThangNam'],
                    'TuanThu' => $week['TuanThu'],
                    'TuNgay' => $week['TuNgay'],
                    'DenNgay' => $week['DenNgay'],
                    'KyHieu' => $kyHieu,
                    'GhiChu' => $ghiChuLop,
                ];
            }

            $classes[] = [
                'SoTT' => $soTT,
                'MaKhoaLop' => $maKhoaLop,
                'GiaoVienDay' => $giaoVienDay,
                'SoLuongHocVien' => $soLuongHocVien,
                'SoHocVienTotNghiep' => $soTotNghiep,
                'GhiChu' => $ghiChuLop,
                'ky_hieu_count' => $kyHieuCount,
                'week_count' => count($weekMap),
            ];
        }

        if ($classes === []) {
            return null;
        }

        return [
            'sheet_name' => $sheetName,
            'nam_hoc' => $namHoc,
            'classes' => $classes,
            'records' => $records,
            'meta' => [
                'class_count' => count($classes),
                'record_count' => count($records),
                'week_count' => count($weekMap),
            ],
        ];
    }

    /**
     * @return array<int, array{ThangNam: string, TuanThu: int|null, TuNgay: string|null, DenNgay: string|null}>
     */
    private function buildWeekColumnMap(Worksheet $sheet, int $weekStartCol, int $weekEndCol): array
    {
        $map = [];
        $lastThang = '';

        for ($col = $weekStartCol; $col <= $weekEndCol; $col++) {
            $thangRaw = $this->cellText($sheet, self::HEADER_ROW, $col);
            if ($thangRaw !== '') {
                $lastThang = $thangRaw;
            }

            $tuanThu = $this->cellInt($sheet, self::TUAN_ROW, $col);
            $tuNgay = $this->cellText($sheet, self::TU_NGAY_ROW, $col);
            $denNgay = $this->cellText($sheet, self::DEN_NGAY_ROW, $col);
            $dates = $this->parseWeekDates($lastThang, $tuNgay, $denNgay);

            $map[$col] = [
                'ThangNam' => $lastThang,
                'TuanThu' => $tuanThu,
                'TuNgay' => $dates['TuNgay'],
                'DenNgay' => $dates['DenNgay'],
            ];
        }

        return $map;
    }

    /**
     * @return array{TuNgay: string|null, DenNgay: string|null}
     */
    private function parseWeekDates(string $thangNam, string $tuNgay, string $denNgay): array
    {
        $month = null;
        $year = null;
        if (preg_match('/Tháng\s*(\d+)\s*\/\s*(\d{4})/ui', $thangNam, $m)) {
            $month = (int) $m[1];
            $year = (int) $m[2];
        } elseif (preg_match('/(\d+)\s*\/\s*(\d{4})/u', $thangNam, $m)) {
            $month = (int) $m[1];
            $year = (int) $m[2];
        }

        $tuDay = $this->normalizeDayNumber($tuNgay);
        $denDay = $this->normalizeDayNumber($denNgay);

        if ($month === null || $year === null || $tuDay === null || $denDay === null) {
            return ['TuNgay' => null, 'DenNgay' => null];
        }

        try {
            $tu = Carbon::createFromDate($year, $month, $tuDay)->startOfDay();
            $den = Carbon::createFromDate($year, $month, $denDay)->startOfDay();
            if ($den->lt($tu)) {
                $den = $den->addMonth();
            }

            return [
                'TuNgay' => $tu->format('Y-m-d'),
                'DenNgay' => $den->format('Y-m-d'),
            ];
        } catch (\Throwable) {
            return ['TuNgay' => null, 'DenNgay' => null];
        }
    }

    private function normalizeDayNumber(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $day = (int) $value;

        return $day >= 1 && $day <= 31 ? $day : null;
    }

    private function parseNamHoc(string $sheetName): ?int
    {
        if (preg_match('/(\d{4})/', $sheetName, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function normalizeKyHieu(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s*•\s*/u', ' •', $value) ?? $value;

        return trim($value);
    }

    private function cellText(Worksheet $sheet, int $row, int $col): string
    {
        $value = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getCalculatedValue();

        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value)) {
            if (is_float($value) && floor($value) == $value) {
                return (string) (int) $value;
            }

            return trim((string) $value);
        }

        return trim((string) $value);
    }

    private function cellInt(Worksheet $sheet, int $row, int $col): ?int
    {
        $text = $this->cellText($sheet, $row, $col);
        if ($text === '' || ! is_numeric($text)) {
            return null;
        }

        return (int) $text;
    }
}
