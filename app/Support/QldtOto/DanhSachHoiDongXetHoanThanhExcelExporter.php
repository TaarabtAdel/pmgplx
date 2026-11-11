<?php

namespace App\Support\QldtOto;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class DanhSachHoiDongXetHoanThanhExcelExporter
{
    public const TEMPLATE = 'templates/oto/danh-sach-hoi-dong-xet-hoan-thanh-khoa.xlsx';

    private const TIEU_DE_ROW = 4;

    private const DONG_QD_ROW = 5;

    private const DATA_START_ROW = 7;

    private const TEMPLATE_DATA_ROWS = 5;

    /**
     * @param  array{tieu_de: string, dong_quyet_dinh: string}  $meta
     * @param  list<array{ho_ten: string, chuc_vu: string, chuc_danh: string}>  $thanhVien
     */
    public function generate(array $meta, array $thanhVien, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có file mẫu: '.self::TEMPLATE);
        }

        @mkdir(dirname($outputPath), 0755, true);

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Sheet3');
        if ($sheet === null) {
            throw new \RuntimeException('Mẫu Excel thiếu sheet «Sheet3».');
        }
        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if ($spreadsheet->getSheet($i)->getTitle() !== 'Sheet3') {
                $spreadsheet->removeSheetByIndex($i);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A'.self::TIEU_DE_ROW, $meta['tieu_de']);
        $sheet->setCellValue('A'.self::DONG_QD_ROW, $meta['dong_quyet_dinh']);

        $count = count($thanhVien);
        $extra = max(0, $count - self::TEMPLATE_DATA_ROWS);
        if ($extra > 0) {
            $sheet->insertNewRowBefore(self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS, $extra);
        }

        for ($i = 0; $i < $count; $i++) {
            $row = self::DATA_START_ROW + $i;
            $tv = $thanhVien[$i];
            if ($row > self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS - 1 + $extra) {
                self::copyRowStyle($sheet, self::DATA_START_ROW, $row);
            }
            $sheet->setCellValue('C'.$row, $i + 1);
            $sheet->setCellValue('D'.$row, $tv['ho_ten']);
            $sheet->setCellValue('F'.$row, $tv['chuc_vu']);
            $sheet->setCellValue('H'.$row, $tv['chuc_danh']);
        }

        for ($row = self::DATA_START_ROW + $count; $row < self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS + $extra; $row++) {
            foreach (['C', 'D', 'F', 'H'] as $col) {
                $sheet->setCellValue($col.$row, null);
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file Excel danh sách hội đồng.');
        }

        return $outputPath;
    }

    public static function tieuDeTuMoTa(string $moTaQuyetDinh): string
    {
        $text = trim($moTaQuyetDinh);
        $text = preg_replace('/^Lớp đào tạo/u', 'DANH SÁCH HỘI ĐỒNG XÉT HOÀN THÀNH KHÓA ĐÀO TẠO', $text) ?? $text;

        return mb_strtoupper($text, 'UTF-8');
    }

    public static function dongKemTheo(string $soQd, CarbonInterface $ngay): string
    {
        return QuyetDinhThanhLapHoiDongXetHoanThanhDocx::dongKemTheo($soQd, $ngay);
    }

    private static function copyRowStyle(Worksheet $sheet, int $fromRow, int $toRow): void
    {
        foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->duplicateStyle($sheet->getStyle($col.$fromRow), $col.$toRow);
        }
        $sheet->getRowDimension($toRow)->setRowHeight($sheet->getRowDimension($fromRow)->getRowHeight());
    }
}
