<?php

namespace App\Support\DaoTao;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** File mẫu cấu trúc «BÁO CÁO CHI TIẾT - KẾT QUẢ SÁT HẠCH» (đúng cột {@see KetQuaSatHachExcelParser}). */
final class KetQuaSatHachMauExcel
{
    public const DOWNLOAD_NAME = 'MAU_BAO_CAO_CHI_TIET_KET_QUA_SAT_HACH.xlsx';

    public static function build(): Spreadsheet
    {
        $ss = new Spreadsheet;
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Sheet1');

        $ws->setCellValue('A1', 'STT');
        $ws->setCellValue('B1', 'Họ và tên');
        $ws->setCellValue('C1', 'Ngày sinh');
        $ws->setCellValue('F1', 'Số định danh');
        $ws->setCellValue('I1', 'Hạng');
        $ws->setCellValue('R1', 'Tổng hợp');

        $ws->setCellValue('L2', 'LT');
        $ws->setCellValue('M2', 'Lần');
        $ws->setCellValue('N2', 'Sa hình');
        $ws->setCellValue('O2', 'Lần');
        $ws->setCellValue('P2', 'Đường');
        $ws->setCellValue('Q2', 'Lần');

        $mau = [
            ['1', 'Nguyễn Văn An', '01/01/1990', '001234567890', 'B01', 'Đ', '1', 'Đ', '1', 'KĐ', '2', ''],
            ['2', 'Trần Thị Bình', '15/06/1995', '098765432101', 'C1', 'Đ', '1', 'KĐ', '1', '-', '1', ''],
            ['3', 'Lê Văn Cường', '20/12/1988', '012345678901', 'B', 'Đ', '2', 'Đ', '2', 'Đ', '1', 'Đ'],
        ];

        $row = 3;
        foreach ($mau as $d) {
            $ws->setCellValue('A'.$row, $d[0]);
            $ws->setCellValue('B'.$row, $d[1]);
            $ws->setCellValue('C'.$row, $d[2]);
            $ws->setCellValueExplicit('F'.$row, $d[3], DataType::TYPE_STRING);
            $ws->setCellValue('I'.$row, $d[4]);
            $ws->setCellValue('L'.$row, $d[5]);
            $ws->setCellValue('M'.$row, $d[6]);
            $ws->setCellValue('N'.$row, $d[7]);
            $ws->setCellValue('O'.$row, $d[8]);
            $ws->setCellValue('P'.$row, $d[9]);
            $ws->setCellValue('Q'.$row, $d[10]);
            $ws->setCellValue('R'.$row, $d[11]);
            $row++;
        }

        $ws->getColumnDimension('B')->setWidth(22);
        $ws->getColumnDimension('F')->setWidth(16);
        $ws->getColumnDimension('I')->setWidth(8);

        return $ss;
    }

    public static function writeToTempFile(): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('ket-qua-sh-mau_', true).'.xlsx';
        (new Xlsx(self::build()))->save($path);

        return $path;
    }
}