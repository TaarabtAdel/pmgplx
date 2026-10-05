<?php

namespace App\Support\DaoTao;

use App\Models\PMGPLX\KhoaHoc;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PhanCongTuLichTongHopExcelExporter
{
    private const HEADER_COLOR = 'FF2E7D32';

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{ma_kh: string, ma_gv: string, bien_so_xe: string}  $filters
     */
    public static function download(array $rows, array $filters): StreamedResponse
    {
        $spreadsheet = self::buildSpreadsheet($rows, $filters);
        $filename = 'phan-cong-tu-lich-tong-hop-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{ma_kh: string, ma_gv: string, bien_so_xe: string}  $filters
     */
    private static function buildSpreadsheet(array $rows, array $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tong hop khoa xe');

        $lastCol = 8;
        $lastLetter = self::columnLetter($lastCol);

        $sheet->setCellValue('A1', 'Tổng hợp phân công theo khoá · xe (lịch xe PMGPLX)');
        $sheet->mergeCells('A1:'.$lastLetter.'1');
        $sheet->setCellValue('A2', self::filterScopeLine($filters));
        $sheet->mergeCells('A2:'.$lastLetter.'2');
        $sheet->setCellValue('A3', 'Xuất lúc: '.now()->format('d/m/Y H:i:s').' · '.number_format(count($rows)).' dòng');
        $sheet->mergeCells('A3:'.$lastLetter.'3');

        $headerRow = 5;
        $headers = [
            'STT',
            'Khóa',
            'Mã KH',
            'BKS',
            "TG\nbắt đầu",
            "TG\nkết thúc",
            'GV A',
            'GV B',
        ];
        foreach ($headers as $i => $label) {
            $sheet->setCellValue(self::columnLetter($i + 1).$headerRow, $label);
        }
        self::styleHeader($sheet, 'A'.$headerRow.':'.$lastLetter.$headerRow);

        $rowIndex = $headerRow + 1;
        foreach ($rows as $stt => $row) {
            $gvs = array_values($row['giao_viens'] ?? []);
            $gvExtra = max(0, count($gvs) - 2);
            $gvA = isset($gvs[0]) ? self::formatGiaoVienCell($gvs[0]) : '';
            $gvB = isset($gvs[1]) ? self::formatGiaoVienCell($gvs[1]) : '';
            if ($gvExtra > 0) {
                $gvB .= ($gvB !== '' ? "\n" : '')."(+{$gvExtra} GV khác)";
            }

            $sheet->fromArray([
                $stt + 1,
                (string) ($row['ten_khoa'] ?? ''),
                (string) ($row['ma_kh'] ?? ''),
                (string) ($row['bien_so'] ?? ''),
                self::formatDateTime($row['tu_ngay'] ?? null),
                self::formatDateTime($row['den_ngay'] ?? null),
                $gvA,
                $gvB,
            ], null, 'A'.$rowIndex);

            $rowIndex++;
        }

        $lastDataRow = max($headerRow, $rowIndex - 1);
        $sheet->getStyle('A'.$headerRow.':'.$lastLetter.$lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle('A'.($headerRow + 1).':A'.$lastDataRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.($headerRow + 1).':F'.$lastDataRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(4.5);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(11);
        $sheet->getColumnDimension('D')->setWidth(9);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);

        $sheet->getRowDimension($headerRow)->setRowHeight(32);
        if ($lastDataRow >= $headerRow + 1) {
            $sheet->getStyle('A'.($headerRow + 1).':'.$lastLetter.$lastDataRow)->getFont()->setSize(9);
        }
        $sheet->getStyle('A'.$headerRow.':'.$lastLetter.$headerRow)->getFont()->setSize(9);
        $sheet->getStyle('A1:A3')->getFont()->setSize(10);

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setPrintArea('A1:'.$lastLetter.$lastDataRow);

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.4);
        $margins->setRight(0.25);
        $margins->setLeft(0.25);
        $margins->setBottom(0.4);

        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);

        return $spreadsheet;
    }

    /**
     * @param  array{ma_kh: string, ma_gv: string, bien_so_xe: string}  $filters
     */
    private static function filterScopeLine(array $filters): string
    {
        $parts = ['Điều kiện lọc:'];
        if (($filters['ma_kh'] ?? '') !== '') {
            $ten = KhoaHoc::query()->where('MaKH', $filters['ma_kh'])->value('TenKH');
            $parts[] = 'Khóa '.trim((string) $ten).' ('.$filters['ma_kh'].')';
        } else {
            $parts[] = 'Khóa: tất cả';
        }
        if (($filters['ma_gv'] ?? '') !== '') {
            $parts[] = 'GV: '.$filters['ma_gv'];
        }
        if (($filters['bien_so_xe'] ?? '') !== '') {
            $parts[] = 'Xe: '.$filters['bien_so_xe'];
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  array{ma_gv?: string, ho_ten?: string}  $gv
     */
    private static function formatGiaoVienCell(array $gv): string
    {
        $hoTen = trim((string) ($gv['ho_ten'] ?? ''));
        $ma = trim((string) ($gv['ma_gv'] ?? ''));
        if ($hoTen === '' && $ma === '') {
            return '';
        }
        if ($ma === '') {
            return $hoTen;
        }
        if ($hoTen === '') {
            return $ma;
        }

        return $hoTen."\n".$ma;
    }

    private static function formatDateTime(mixed $value): string
    {
        if (! $value instanceof Carbon) {
            return '';
        }

        return $value->format('d/m/y')."\n".$value->format('H:i');
    }

    private static function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_COLOR],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    private static function columnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)).$letter;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $letter;
    }
}
