<?php

namespace App\Support\DaoTao;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SoPhanCongDaoTaoExcelParser
{
    private const HEADER_ROW = 1;

    private const DATA_START_ROW = 2;

    /** @var array<string, int> */
    private const DEFAULT_COLUMNS = [
        'HoTen' => 2,
        'ThoiGian' => 3,
        'TenKhoa' => 4,
        'BienSo' => 5,
        'NoiDungGiangDay' => 6,
    ];

    /**
     * @return array{
     *     file_name: string,
     *     sheet_name: string,
     *     records: list<array<string, mixed>>,
     *     meta: array{record_count: int, khoa_count: int, gv_count: int, xe_count: int}
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName): array
    {
        $worksheet = $spreadsheet->getSheet(0);
        $columns = $this->resolveColumns($worksheet);
        $records = $this->parseSheet($worksheet, $columns);

        if ($records === []) {
            throw new \InvalidArgumentException('Không tìm thấy dữ liệu phân công hợp lệ trong file.');
        }

        $khoaSet = [];
        $gvSet = [];
        $xeSet = [];
        foreach ($records as $record) {
            $khoa = trim((string) ($record['TenKhoa'] ?? ''));
            if ($khoa !== '') {
                $khoaSet[$khoa] = true;
            }
            $hoTen = trim((string) ($record['HoTen'] ?? ''));
            if ($hoTen !== '') {
                $gvSet[$hoTen] = true;
            }
            $bienSo = trim((string) ($record['BienSo'] ?? ''));
            if ($bienSo !== '') {
                $xeSet[$bienSo] = true;
            }
        }

        return [
            'file_name' => $fileName,
            'sheet_name' => trim((string) $worksheet->getTitle()),
            'records' => $records,
            'meta' => [
                'record_count' => count($records),
                'khoa_count' => count($khoaSet),
                'gv_count' => count($gvSet),
                'xe_count' => count($xeSet),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function resolveColumns(Worksheet $sheet): array
    {
        $columns = self::DEFAULT_COLUMNS;
        $map = [
            'giáo viên' => 'HoTen',
            'giao vien' => 'HoTen',
            'thời gian' => 'ThoiGian',
            'thoi gian' => 'ThoiGian',
            'khoá đào tạo' => 'TenKhoa',
            'khoa dao tao' => 'TenKhoa',
            'biển số' => 'BienSo',
            'bien so' => 'BienSo',
            'nội dung' => 'NoiDungGiangDay',
            'noi dung' => 'NoiDungGiangDay',
        ];

        $highestCol = min(10, Coordinate::columnIndexFromString($sheet->getHighestDataColumn(self::HEADER_ROW)));
        for ($col = 1; $col <= $highestCol; $col++) {
            $header = $this->normalizeHeader($this->cellText($sheet, self::HEADER_ROW, $col));
            foreach ($map as $needle => $field) {
                if ($header !== '' && str_contains($header, $needle)) {
                    $columns[$field] = $col;
                }
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, int>  $columns
     * @return list<array<string, mixed>>
     */
    private function parseSheet(Worksheet $sheet, array $columns): array
    {
        $records = [];
        $highestRow = max(self::DATA_START_ROW, (int) $sheet->getHighestDataRow());

        for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
            $hoTen = $this->cellText($sheet, $row, $columns['HoTen']);
            $tenKhoa = strtoupper(trim($this->cellText($sheet, $row, $columns['TenKhoa'])));
            $bienSo = $this->cellText($sheet, $row, $columns['BienSo']);
            $thoiGian = $this->cellText($sheet, $row, $columns['ThoiGian']);
            $noiDung = $this->cellText($sheet, $row, $columns['NoiDungGiangDay']);

            if ($tenKhoa === '' && $hoTen === '' && $bienSo === '' && $thoiGian === '') {
                continue;
            }

            // Cột giáo viên rỗng: không gán GV, vẫn giữ dòng nếu có xe/khoá/thời gian
            $hoTen = trim($hoTen);

            [$tuNgay, $denNgay, $thoiGianDisplay] = $this->parseThoiGian($thoiGian);

            $records[] = [
                'excel_row' => $row,
                'HoTen' => $hoTen,
                'ThoiGian' => $thoiGianDisplay,
                'TuNgay' => $tuNgay,
                'DenNgay' => $denNgay,
                'TenKhoa' => $tenKhoa,
                'BienSo' => $bienSo,
                'NoiDungGiangDay' => $noiDung,
            ];
        }

        return $records;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string}
     */
    private function parseThoiGian(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [null, null, ''];
        }

        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})/u', $value, $m)) {
            try {
                $tu = Carbon::createFromFormat('d/m/Y', trim($m[1]))->toDateString();
                $den = Carbon::createFromFormat('d/m/Y', trim($m[2]))->toDateString();

                if ($tu > $den) {
                    [$tu, $den] = [$den, $tu];
                }

                $tuDisplay = Carbon::parse($tu)->format('d/m/Y');
                $denDisplay = Carbon::parse($den)->format('d/m/Y');

                return [$tu, $den, $tuDisplay.' – '.$denDisplay];
            } catch (\Throwable) {
                return [null, null, $value];
            }
        }

        return [null, null, $value];
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    private function cellText(Worksheet $sheet, int $row, int $col): string
    {
        $value = $sheet->getCell([$col, $row])->getCalculatedValue();

        return trim((string) ($value ?? ''));
    }
}
