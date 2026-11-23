<?php

namespace App\Support\QldtOto\KtmChiTiet;

use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class XuatBaoCaoChiTietExcel
{
    public const TEMPLATE = 'templates/oto/ktm-bao-cao/bao-cao-chi-tiet-ktm.xls';

    /**
     * @param  list<array<string, mixed>>  $thiSinh
     */
    public static function xuat(
        array $thiSinh,
        Carbon $ngayKyThi,
        string $diaDiem,
        string $ngayThiHienThi,
        string $outputPath,
    ): void {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu: '.self::TEMPLATE);
        }

        $spreadsheet = IOFactory::load($template);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('O4', 'Quảng Trị, '.BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngayKyThi));
            $sheet->setCellValue('A8', '          1.	Trung tâm sát hạch: '.trim($diaDiem));
            $sheet->setCellValue('A9', '          2.	Ngày sát hạch: '.$ngayThiHienThi);

            $start = 13;
            $templateRows = max(1, 13);
            $count = count($thiSinh);
            $lastTemplateRow = (int) $sheet->getHighestRow();
            $templateRows = max(34, $lastTemplateRow - $start + 1);
            $delta = $count - $templateRows;
            if ($delta > 0) {
                $sheet->insertNewRowBefore($start + $templateRows, $delta);
                for ($i = 0; $i < $delta; $i++) {
                    self::saoChepHang($sheet, $start, $start + $templateRows + $i);
                }
            }

            foreach ($thiSinh as $i => $ts) {
                $row = $start + $i;
                $sheet->setCellValue('B'.$row, $ts['stt'] ?? ($i + 1));
                $sheet->setCellValue('C'.$row, $ts['hoTen'] ?? '');
                $sheet->setCellValue('D'.$row, $ts['ngaySinh'] ?? '');
                $sheet->setCellValue('G'.$row, $ts['soDinhDanh'] ?? '');
                $sheet->setCellValue('J'.$row, $ts['hang'] ?? '');
                self::dienMon($sheet, $row, 'M', 'N', (string) ($ts['lt'] ?? '-'), (int) ($ts['lanShLt'] ?? 0));
                self::dienMon($sheet, $row, 'O', 'P', (string) ($ts['mp'] ?? '-'), (int) ($ts['lanShMp'] ?? 0));
                self::dienMon($sheet, $row, 'Q', 'R', (string) ($ts['h'] ?? '-'), (int) ($ts['lanShH'] ?? 0));
                self::dienMon($sheet, $row, 'S', 'T', (string) ($ts['cc'] ?? '-'), (int) ($ts['lanShCc'] ?? 0));
                self::dienMon($sheet, $row, 'U', 'V', (string) ($ts['duong'] ?? '-'), (int) ($ts['lanShDuong'] ?? 0));
                $sheet->setCellValue('W'.$row, $ts['tongHop'] ?? '');
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save($outputPath);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function dienMon(Worksheet $sheet, int $row, string $colKq, string $colLan, string $kq, int $lan): void
    {
        $sheet->setCellValue($colKq.$row, $kq);
        $sheet->setCellValue($colLan.$row, $lan > 0 ? $lan : 0);
    }

    private static function saoChepHang(Worksheet $sheet, int $from, int $to): void
    {
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn($from));
        for ($c = 1; $c <= $maxCol; $c++) {
            $col = Coordinate::stringFromColumnIndex($c);
            $sheet->duplicateStyle($sheet->getStyle($col.$from), $col.$to);
        }
        $sheet->getRowDimension($to)->setRowHeight($sheet->getRowDimension($from)->getRowHeight());
    }
}