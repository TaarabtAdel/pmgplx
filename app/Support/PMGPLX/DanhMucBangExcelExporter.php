<?php

namespace App\Support\PMGPLX;

use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Xuất Excel danh mục PMGPLX: mọi cột bảng, toàn bộ bản ghi (không phân trang). */
final class DanhMucBangExcelExporter
{
    private const HEADER_COLOR = 'FF1565C0';

    /**
     * @param  list<string>  $columns  Tên cột SQL theo thứ tự
     * @param  iterable<int, Model>  $rows
     */
    public static function download(string $title, string $filePrefix, array $columns, iterable $rows): StreamedResponse
    {
        $rowList = $rows instanceof \Traversable ? iterator_to_array($rows, false) : (array) $rows;
        $spreadsheet = self::build($title, $columns, $rowList, static function (mixed $row, string $name): mixed {
            if ($row instanceof Model) {
                return $row->getAttributes()[$name] ?? null;
            }

            return null;
        });
        $filename = $filePrefix.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<string>  $columns
     * @param  iterable<int, array<string, mixed>>  $rows
     */
    public static function downloadFromArrays(string $title, string $filePrefix, array $columns, iterable $rows): StreamedResponse
    {
        $rowList = $rows instanceof \Traversable ? iterator_to_array($rows, false) : (array) $rows;
        $spreadsheet = self::build($title, $columns, $rowList, static function (mixed $row, string $name): mixed {
            return is_array($row) ? ($row[$name] ?? null) : null;
        });
        $filename = $filePrefix.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<string>  $columns
     * @param  list<mixed>  $rows
     * @param  callable(mixed, string): mixed  $valueAt
     */
    private static function build(string $title, array $columns, array $rows, callable $valueAt): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(preg_replace('/[\[\]\:\*\?\/\\\\]/u', ' ', $title) ?: 'Sheet', 0, 31));

        $colCount = count($columns);
        $lastCol = self::columnLetter($colCount);

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Xuất lúc: '.now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s')
            .' · '.number_format(count($rows)).' bản ghi · '.number_format($colCount).' cột');
        $sheet->mergeCells('A2:'.$lastCol.'2');

        $headerRow = 4;
        foreach ($columns as $i => $name) {
            $col = self::columnLetter($i + 1);
            $sheet->setCellValue($col.$headerRow, $name);
            $sheet->getColumnDimension($col)->setWidth(min(42, max(10, mb_strlen($name) + 2)));
        }
        $sheet->getStyle('A'.$headerRow.':'.$lastCol.$headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_COLOR]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        $dataRow = $headerRow + 1;
        foreach ($rows as $row) {
            foreach ($columns as $i => $name) {
                $col = self::columnLetter($i + 1);
                $cell = $col.$dataRow;
                $value = self::cellValue($valueAt($row, $name));
                if (is_string($value) && $value !== '' && ! is_numeric($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
            }
            $dataRow++;
        }

        $lastDataRow = max($headerRow, $dataRow - 1);
        if ($lastDataRow > $headerRow) {
            $sheet->getStyle('A'.$headerRow.':'.$lastCol.$lastDataRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            ]);
        }

        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);

        return $spreadsheet;
    }

    private static function cellValue(mixed $value): string|int|float
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return (string) $value;
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
