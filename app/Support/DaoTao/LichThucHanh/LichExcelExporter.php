<?php

namespace App\Support\DaoTao\LichThucHanh;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LichExcelExporter
{
    /**
     * @param  array<string, mixed>  $lich
     */
    public static function download(array $lich, string $maKhoa): StreamedResponse
    {
        $spreadsheet = self::build($lich);
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $maKhoa) ?: 'khoa';
        $filename = 'lich-phan-cong-th-'.$safe.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @param array<string, mixed> $lich */
    public static function build(array $lich): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $gvs = $lich['giao_viens'] ?? [];
        $dates = $lich['dates'] ?? [];
        $cells = $lich['cells'] ?? [];
        $nghiDong = array_flip($lich['ngay_nghi_ca_dong'] ?? []);

        $col = 1;
        foreach ($gvs as $gv) {
            $header = ($gv['ho_ten'] ?? '')."\n".($gv['bien_so'] ?? '');
            $sheet->setCellValueByColumnAndRow($col, 1, 'THỨ');
            $sheet->setCellValueByColumnAndRow($col + 1, 1, 'THỜI GIAN');
            $sheet->setCellValueByColumnAndRow($col + 2, 1, 'SỐ GIỜ');
            $sheet->setCellValueByColumnAndRow($col + 3, 1, $header);
            $sheet->setCellValueByColumnAndRow($col + 4, 1, ($gv['ma_gv'] ?? '').'-'.($gv['ma_khoa'] ?? ''));
            $sheet->setCellValueByColumnAndRow($col + 5, 1, 'BẮT ĐẦU');
            $sheet->setCellValueByColumnAndRow($col + 6, 1, 'KẾT THÚC');
            $col += 7;
        }

        $row = 2;
        foreach ($dates as $iso) {
            $col = 1;
            foreach ($gvs as $gv) {
                $key = $iso.'|'.$gv['ma_gv'];
                $cell = $cells[$key] ?? [];
                $sheet->setCellValueByColumnAndRow($col, $row, $cell['thu'] ?? '');
                $sheet->setCellValueByColumnAndRow($col + 1, $row, LichNgay::hienThiNgay($iso));
                $sheet->setCellValueByColumnAndRow($col + 2, $row, $cell['so_gio'] ?? '');
                $sheet->setCellValueByColumnAndRow($col + 3, $row, $cell['bai'] ?? '');
                $sheet->setCellValueByColumnAndRow($col + 4, $row, LichCellHienThi::noiDungPhu($cell));
                $sheet->setCellValueByColumnAndRow($col + 5, $row, $cell['bat_dau'] ?? '');
                $sheet->setCellValueByColumnAndRow($col + 6, $row, $cell['ket_thuc'] ?? '');

                $mau = (string) ($cell['mau'] ?? '');
                if (isset($nghiDong[$iso]) || $mau === 'NGHI') {
                    self::fillRowBlock($sheet, $row, $col, 'FFFFFF00');
                } else {
                    $hex = BaiGiang::mauHienThi()[$mau]['excel'] ?? null;
                    if ($hex) {
                        self::fillRowBlock($sheet, $row, $col, $hex);
                    }
                }
                $col += 7;
            }
            $row++;
        }

        $sheet->freezePane('C2');

        return $spreadsheet;
    }

    private static function fillRowBlock(Worksheet $sheet, int $row, int $startCol, string $argb): void
    {
        for ($i = 0; $i < 7; $i++) {
            $sheet->getStyleByColumnAndRow($startCol + $i, $row)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB($argb);
        }
    }

    /**
     * Màu nền CSS cho xem trước HTML (cùng quy tắc với ô Excel).
     *
     * @param  array<string, mixed>  $cell
     * @param  array<string, mixed>  $lich
     */
    public static function cssBackgroundForCell(array $cell, string $iso, array $lich): string
    {
        $nghiDong = array_flip($lich['ngay_nghi_ca_dong'] ?? []);
        $mau = (string) ($cell['mau'] ?? '');
        if (isset($nghiDong[$iso]) || $mau === 'NGHI') {
            return '#fff9c4';
        }
        $bg = BaiGiang::mauHienThi()[$mau]['bg'] ?? null;
        if (is_string($bg) && $bg !== '') {
            return $bg;
        }

        return '#ffffff';
    }
}
