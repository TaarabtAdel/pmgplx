<?php

namespace App\Support\QldtOto;

use App\Support\DaoTao\PhuLuc6DatBaoCao;
use App\Support\QldtOto\KtmChiTiet\DocSheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PhuLuc6DatCabinExcel
{
    private const TEMPLATE = 'templates/oto/phu-luc-6-dat-cabin.goc.xlsx';

    private const DATA_ROW_START = 4;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function taiVe(array $rows, string $outputPath): void
    {
        $src = resource_path(self::TEMPLATE);
        if (! is_file($src)) {
            throw new \RuntimeException('Chưa có mẫu: '.self::TEMPLATE);
        }
        @mkdir(dirname($outputPath), 0755, true);
        if (! copy($src, $outputPath)) {
            throw new \RuntimeException('Không copy được mẫu Excel.');
        }

        $spreadsheet = IOFactory::load($outputPath);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $signRow = self::timHangXacNhan($sheet);
            if ($signRow === null) {
                $signRow = self::DATA_ROW_START + count($rows) + 2;
            }

            $maxDataRow = $signRow - 2;
            for ($r = self::DATA_ROW_START; $r < $signRow; $r++) {
                self::xoaDongDuLieu($sheet, $r);
            }

            $canChua = max(0, count($rows) - ($maxDataRow - self::DATA_ROW_START + 1));
            if ($canChua > 0) {
                $sheet->insertNewRowBefore($signRow, $canChua);
                $signRow += $canChua;
            }

            foreach ($rows as $i => $row) {
                $r = self::DATA_ROW_START + $i;
                $sheet->setCellValue('A'.$r, $row['stt']);
                $sheet->setCellValue('C'.$r, $row['ho_ten'] ?? '');
                $sheet->setCellValue('D'.$r, $row['ma_hoc_vien'] ?? '');
                $ngay = PhuLuc6DatBaoCao::excelNgaySinh($row['ngay_sinh'] ?? null);
                if ($ngay !== null) {
                    $sheet->setCellValue('E'.$r, $ngay);
                }
                $sheet->setCellValue('F'.$r, $row['hang'] ?? '');
                $sheet->setCellValue('G'.$r, $row['ma_khoa_hoc'] ?? '');
                $sheet->setCellValue('H'.$r, PhuLuc6DatBaoCao::chuoiGio($row['tong_gio'] ?? null));
                $sheet->setCellValue('I'.$r, PhuLuc6DatBaoCao::chuoiKm($row['tong_km'] ?? null));
                $sheet->setCellValue('J'.$r, $row['ghi_chu'] ?? 'HT');
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outputPath);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function timHangXacNhan(Worksheet $sheet): ?int
    {
        for ($r = self::DATA_ROW_START; $r <= 200; $r++) {
            for ($c = 1; $c <= 12; $c++) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $t = mb_strtolower(DocSheet::cellStr($sheet, $letter.$r));
                if (str_contains($t, 'xác nhận của cơ sở')) {
                    return $r;
                }
            }
        }

        return null;
    }

    private static function xoaDongDuLieu(Worksheet $sheet, int $row): void
    {
        foreach (['A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $sheet->setCellValue($col.$row, null);
        }
    }

    public static function tenFile(string $maKhoa): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $maKhoa) ?: 'khoa';

        return 'phu-luc-6-dat-'.$safe.'-'.now()->format('Ymd-His').'.xlsx';
    }
}