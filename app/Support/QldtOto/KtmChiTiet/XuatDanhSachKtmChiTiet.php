<?php

namespace App\Support\QldtOto\KtmChiTiet;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class XuatDanhSachKtmChiTiet
{
    public const TEMPLATE_VANG = 'templates/oto/ktm-bao-cao/ds-vang-ktm.xls';

    public const TEMPLATE_DAT = 'templates/oto/ktm-bao-cao/ds-dat-ktm.xls';

    public const TEMPLATE_KO_DAT = 'templates/oto/ktm-bao-cao/ds-ko-dat-ktm.xls';

    public const LOAI_VANG = 'vang';

    public const LOAI_DAT = 'dat';

    public const LOAI_KO_DAT = 'ko-dat';

    private const DATA_START = 11;

    /**
     * @param  list<array<string, mixed>>  $thiSinh  đã lọc
     */
    public static function xuat(
        string $loai,
        array $thiSinh,
        string $tenKhoa,
        string $diaDiem,
        Carbon $ngayKyThi,
        string $outputPath,
    ): void {
        $template = match ($loai) {
            self::LOAI_VANG => self::TEMPLATE_VANG,
            self::LOAI_DAT => self::TEMPLATE_DAT,
            self::LOAI_KO_DAT => self::TEMPLATE_KO_DAT,
            default => throw new \InvalidArgumentException('Loại danh sách không hợp lệ: '.$loai),
        };

        $path = resource_path($template);
        if (! is_file($path)) {
            throw new \RuntimeException('Chưa có mẫu: '.$template);
        }

        $spreadsheet = IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $count = count($thiSinh);

            $sheet->setCellValue('B6', trim($diaDiem));
            $sheet->setCellValue('C7', $tenKhoa);
            $sheet->setCellValue('C8', $ngayKyThi->format('d/m/Y'));

            if ($loai === self::LOAI_KO_DAT) {
                $sheet->setCellValue('H7', $count);
            } else {
                $sheet->setCellValue('G7', $count);
            }

            $footerRow = self::timHangChanTrang($sheet);
            $templateDataRows = max(1, $footerRow - self::DATA_START);
            $delta = $count - $templateDataRows;
            if ($delta > 0) {
                $sheet->insertNewRowBefore(self::DATA_START + $templateDataRows, $delta);
                for ($i = 0; $i < $delta; $i++) {
                    self::saoChepHang($sheet, self::DATA_START, self::DATA_START + $templateDataRows + $i);
                }
            }

            foreach ($thiSinh as $i => $ts) {
                $row = self::DATA_START + $i;
                self::dienHang($sheet, $row, $loai, $i + 1, $ts);
            }

            for ($row = self::DATA_START + $count; $row < self::DATA_START + $templateDataRows + max(0, $delta); $row++) {
                self::xoaHangDuLieu($sheet, $row, $loai);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save($outputPath);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function timHangChanTrang(Worksheet $sheet): int
    {
        $max = (int) $sheet->getHighestRow();
        for ($r = $max; $r >= self::DATA_START; $r--) {
            $a = trim((string) $sheet->getCell('A'.$r)->getCalculatedValue());
            if ($a === 'Ủy Viên') {
                return $r;
            }
        }

        return self::DATA_START + 1;
    }

    /** @param  array<string, mixed>  $ts */
    private static function dienHang(Worksheet $sheet, int $row, string $loai, int $stt, array $ts): void
    {
        $sbd = trim((string) ($ts['sbd'] ?? ''));
        $hoTen = (string) ($ts['hoTen'] ?? '');
        $cccd = (string) ($ts['soDinhDanh'] ?? '');
        $ngaySinh = (string) ($ts['ngaySinh'] ?? '');
        $hang = (string) ($ts['hang'] ?? '');
        $ghiChu = (string) ($ts['ghiChu'] ?? '');

        $sheet->setCellValue('A'.$row, $stt);
        $sheet->setCellValue('B'.$row, $sbd);
        $sheet->setCellValue('C'.$row, $hoTen);
        $sheet->setCellValue('D'.$row, $cccd);

        if ($loai === self::LOAI_KO_DAT) {
            $sheet->setCellValue('F'.$row, $ngaySinh);
            $sheet->setCellValue('H'.$row, $hang);
            $sheet->setCellValue('I'.$row, $ghiChu);
        } else {
            $sheet->setCellValue('E'.$row, $ngaySinh);
            $sheet->setCellValue('G'.$row, $hang);
            $sheet->setCellValue('H'.$row, $ghiChu);
        }
    }

    private static function xoaHangDuLieu(Worksheet $sheet, int $row, string $loai): void
    {
        $cols = $loai === self::LOAI_KO_DAT
            ? ['A', 'B', 'C', 'D', 'F', 'G', 'H', 'I']
            : ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($cols as $col) {
            $sheet->setCellValue($col.$row, null);
        }
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