<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Danh sách phiên đề xuất hủy (cùng cột với bảng trong báo cáo Word). */
final class DanhSachHuyPhienExcelExporter
{
    /**
     * @param  list<array{ten_khoa: string, ho_ten_gv: string, ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>  $phien
     */
    public function generate(array $phien, string $outputPath): string
    {
        if ($phien === []) {
            throw new \InvalidArgumentException('Chưa có dòng phiên nào trong danh sách.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sach huy phien');

        $headers = ['STT', 'Khóa học', 'Giáo viên', 'Họ và tên', 'Mã học viên', 'Mã phiên học', 'Phương án', 'Lý do'];
        foreach ($headers as $col => $label) {
            $cell = chr(ord('A') + $col).'1';
            $sheet->setCellValue($cell, $label);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($phien as $i => $row) {
            $r = $i + 2;
            $sheet->setCellValue('A'.$r, $i + 1);
            $sheet->setCellValue('B'.$r, $row['ten_khoa'] ?? '');
            $sheet->setCellValue('C'.$r, $row['ho_ten_gv'] ?? '');
            $sheet->setCellValue('D'.$r, $row['ho_ten']);
            $sheet->setCellValue('E'.$r, $row['ma_hv']);
            $sheet->setCellValue('F'.$r, $row['ma_phien']);
            $sheet->setCellValue('G'.$r, $row['phuong_an']);
            $sheet->setCellValue('H'.$r, $row['ly_do']);
        }

        $last = count($phien) + 1;
        $sheet->getStyle('A1:H'.$last)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(26);
        $sheet->getColumnDimension('F')->setWidth(38);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(48);
        $sheet->getStyle('H2:H'.$last)->getAlignment()->setWrapText(true);

        (new Xlsx($spreadsheet))->save($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được Excel danh sách hủy phiên.');
        }

        return $outputPath;
    }
}
