<?php

namespace App\Support\QldtOto\KtmBaoCao;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Xóa số liệu / tên mẫu cũ trong 3 file template KTM, giữ bố cục & nhãn tĩnh. */
final class KtmLamSachFileMau
{
    private const TPL_DIR = 'templates/oto/ktm-bao-cao';

    public static function lamSachCaBa(): void
    {
        self::lamSachBbth(resource_path(self::TPL_DIR.'/MAU_BBTH_bien_ban_tong_hop.xlsx'));
        self::lamSachBcChung(resource_path(self::TPL_DIR.'/MAU_BC_CHUNG_bao_cao_chung.xlsx'));
        self::lamSachBcChiTiet(resource_path(self::TPL_DIR.'/MAU_BC_CHI_TIET_bao_cao_chi_tiet.xlsx'));
    }

    public static function lamSachBbth(string $path): void
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('BBTH') ?? $spreadsheet->getActiveSheet();

        foreach (['I4', 'A8', 'B39', 'X38', 'X39', 'B51', 'F51', 'M51', 'G58'] as $cell) {
            $sheet->setCellValue($cell, null);
        }
        foreach (range(9, 13) as $row) {
            $sheet->setCellValue('B'.$row, null);
        }
        $sheet->setCellValue('H16', null);
        $sheet->setCellValue('H17', null);
        $sheet->setCellValue('O16', null);

        foreach ([24, 25, 26, 30, 31, 32, 33, 34, 36] as $row) {
            self::xoaHangSoLieuBbth($sheet, $row);
        }

        self::luu($spreadsheet, $path);
    }

    public static function lamSachBcChung(string $path): void
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('BC CHUNG') ?? $spreadsheet->getActiveSheet();

        foreach (['O4', 'A8', 'A9', 'P27'] as $cell) {
            $sheet->setCellValue($cell, null);
        }
        foreach (range(13, 18) as $row) {
            self::xoaHangSoLieuBcChung($sheet, $row);
        }

        self::luu($spreadsheet, $path);
    }

    public static function lamSachBcChiTiet(string $path): void
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('BC CHI TIẾT') ?? $spreadsheet->getActiveSheet();

        foreach (['O4', 'A8', 'A9'] as $cell) {
            $sheet->setCellValue($cell, null);
        }

        $dataStart = 13;
        $dataEnd = 46;
        for ($row = $dataStart; $row <= $dataEnd; $row++) {
            foreach (['B', 'C', 'D', 'G', 'J', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'] as $col) {
                $sheet->setCellValue($col.$row, null);
            }
            foreach (range(24, 34) as $idx) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx).$row, null);
            }
        }

        $sheet->setCellValue('P52', null);

        self::luu($spreadsheet, $path);
    }

    private static function xoaHangSoLieuBbth(Worksheet $sheet, int $row): void
    {
        foreach (range(5, 22) as $idx) {
            $col = Coordinate::stringFromColumnIndex($idx);
            if (in_array($col, ['B'], true)) {
                continue;
            }
            $sheet->setCellValue($col.$row, null);
        }
        $sheet->setCellValue('V'.$row, null);
    }

    private static function xoaHangSoLieuBcChung(Worksheet $sheet, int $row): void
    {
        foreach (range(6, 23) as $idx) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx).$row, null);
        }
    }

    private static function luu(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $path): void
    {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();
    }
}