<?php

namespace App\Support\DaoTao;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Đọc file «DANH SÁCH MỜI HỌC VIÊN VÀO NHÓM LỚP» — mọi sheet, cột F/G/H: CCCD, SDT, Sale.
 */
class HocVienSdtSaleTuFileExcel
{
    public const COL_CCCD = 'F';

    public const COL_SDT = 'G';

    public const COL_SALE = 'H';

    public const PREVIEW_SAMPLE = 10;

    public static function loadSpreadsheet(string $path): Spreadsheet
    {
        return IOFactory::load($path);
    }

    public static function releaseSpreadsheet(?Spreadsheet $spreadsheet): void
    {
        if ($spreadsheet === null) {
            return;
        }
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    public static function chuanHoaCccd(mixed $raw): string
    {
        return preg_replace('/\D/', '', trim((string) $raw)) ?? '';
    }

    public static function chuanHoaText(mixed $raw): string
    {
        $s = trim((string) $raw);

        return preg_replace('/\s+/u', ' ', $s) ?? '';
    }

    /**
     * @return list<array{sheet_index: int, sheet_name: string, excel_row: int, cccd: string, sdt: string, sale: string}>
     */
    public static function docSheet(Worksheet $sheet, int $sheetIndex): array
    {
        $highest = (int) $sheet->getHighestRow();
        $out = [];

        for ($row = 1; $row <= $highest; $row++) {
            $cccdRaw = self::cellText($sheet, self::COL_CCCD, $row);
            if ($cccdRaw === '') {
                continue;
            }
            if (self::laDongTieuDe($cccdRaw)) {
                continue;
            }

            $cccd = self::chuanHoaCccd($cccdRaw);
            if ($cccd === '') {
                continue;
            }

            $sdt = self::chuanHoaSdt(self::cellText($sheet, self::COL_SDT, $row));
            $sale = self::chuanHoaText(self::cellText($sheet, self::COL_SALE, $row));

            if ($sdt === '' && $sale === '') {
                continue;
            }

            $out[] = [
                'sheet_index' => $sheetIndex,
                'sheet_name' => $sheet->getTitle(),
                'excel_row' => $row,
                'cccd' => $cccd,
                'sdt' => $sdt,
                'sale' => $sale,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{sheet_index: int, sheet_name: string, excel_row: int, cccd: string, sdt: string, sale: string}>
     */
    public static function docTatCaSheet(Spreadsheet $spreadsheet): array
    {
        $all = [];
        foreach ($spreadsheet->getWorksheetIterator() as $i => $sheet) {
            if ($sheet->getSheetState() === Worksheet::SHEETSTATE_HIDDEN) {
                continue;
            }
            foreach (self::docSheet($sheet, $i) as $row) {
                $all[] = $row;
            }
        }

        return $all;
    }

    private static function laDongTieuDe(string $cccdCell): bool
    {
        if (preg_match('/cccd|cmt|căn\s*cước|so\s*cccd|số\s*cccd|socmt/i', $cccdCell)) {
            return true;
        }

        return ! preg_match('/\d{8,}/', $cccdCell);
    }

    private static function chuanHoaSdt(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        if (preg_match('/^[\d\s\.\-\+\(\)]+$/', $s)) {
            return preg_replace('/\s+/', '', $s) ?? $s;
        }

        return $s;
    }

    private static function cellText(Worksheet $sheet, string $col, int $row): string
    {
        $coord = $col.$row;
        $cell = $sheet->getCell($coord);
        $val = $cell->getValue();

        if ($val === null || $val === '') {
            return '';
        }

        if (\is_string($val)) {
            return trim($val);
        }

        if (\is_int($val) || \is_float($val)) {
            if (Date::isDateTime($cell)) {
                try {
                    return Date::excelToDateTimeObject($val)->format('d/m/Y');
                } catch (\Throwable) {
                    // số điện thoại dạng số
                }
            }

            $s = (string) $val;
            if (str_contains($s, 'E') || str_contains($s, 'e')) {
                $s = sprintf('%.0f', $val);
            }

            return trim($s);
        }

        if ($val instanceof \DateTimeInterface) {
            return $val->format('d/m/Y');
        }

        return trim((string) $val);
    }

    /** @return list<string> */
    public static function sheetNames(Spreadsheet $spreadsheet): array
    {
        $names = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if ($sheet->getSheetState() === Worksheet::SHEETSTATE_HIDDEN) {
                continue;
            }
            $names[] = $sheet->getTitle();
        }

        return $names;
    }

    public static function sheetByIndex(Spreadsheet $spreadsheet, int $index): ?Worksheet
    {
        $i = 0;
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if ($sheet->getSheetState() === Worksheet::SHEETSTATE_HIDDEN) {
                continue;
            }
            if ($i === $index) {
                return $sheet;
            }
            $i++;
        }

        return null;
    }
}
