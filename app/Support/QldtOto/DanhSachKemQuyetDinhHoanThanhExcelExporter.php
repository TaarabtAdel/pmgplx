<?php

namespace App\Support\QldtOto;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Danh sách kèm theo quyết định hoàn thành khoá (mẫu Excel riêng, vd. «Kèm QĐ hoàn thành khóa học BK55.xlsx»). */
final class DanhSachKemQuyetDinhHoanThanhExcelExporter
{
    public const TEMPLATE = 'templates/oto/kem-quyet-dinh-hoan-thanh-khoa-hoc.xlsx';

    private const TIEU_DE_ROW = 1;

    private const DONG_QD_ROW = 2;

    private const DATA_START_ROW = 4;

    /** Số dòng dữ liệu có sẵn trong mẫu (hàng 4 — chỉ giữ để copy style). */
    private const TEMPLATE_DATA_ROWS = 1;

    /**
     * @param  array{tieu_de: string, dong_quyet_dinh: string}  $meta
     * @param  list<array{ho_dem: string, ten: string, ngay_sinh: string, so_cmnd: string, so_giay_ht: string, so_ccn: string, ten_khoa: string}>  $hocVien
     */
    public function generate(array $meta, array $hocVien, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có file mẫu: '.self::TEMPLATE);
        }

        @mkdir(dirname($outputPath), 0755, true);

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách');

        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if ($spreadsheet->getSheet($i)->getTitle() !== $sheet->getTitle()) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }

        $sheet->setCellValue('A'.self::TIEU_DE_ROW, $meta['tieu_de']);
        $sheet->setCellValue('A'.self::DONG_QD_ROW, $meta['dong_quyet_dinh']);

        $count = count($hocVien);
        $extra = max(0, $count - self::TEMPLATE_DATA_ROWS);
        if ($extra > 0) {
            $sheet->insertNewRowBefore(self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS, $extra);
        }

        for ($i = 0; $i < $count; $i++) {
            $row = self::DATA_START_ROW + $i;
            $hv = $hocVien[$i];
            if ($row > self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS - 1 + $extra) {
                self::copyRowStyle($sheet, self::DATA_START_ROW, $row);
            }
            $sheet->setCellValue('A'.$row, $i + 1);
            $sheet->setCellValue('B'.$row, $hv['ho_dem']);
            $sheet->setCellValue('C'.$row, $hv['ten']);
            $sheet->setCellValue('D'.$row, $hv['ngay_sinh']);
            $sheet->setCellValue('E'.$row, $hv['so_cmnd']);
            $sheet->setCellValue('F'.$row, $hv['so_giay_ht']);
            $sheet->setCellValue('G'.$row, $hv['so_ccn']);
            $sheet->setCellValue('H'.$row, $hv['ten_khoa']);
        }

        for ($row = self::DATA_START_ROW + $count; $row < self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS + $extra; $row++) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
                $sheet->setCellValue($col.$row, null);
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file Excel danh sách kèm theo.');
        }

        return $outputPath;
    }

    public static function tieuDe(string $hang, string $khoaSo): string
    {
        $hang = mb_strtoupper(trim($hang), 'UTF-8');
        $hang = str_replace(['(', ')'], [' ', ''], $hang);
        $hang = preg_replace('/\s+/u', ' ', trim($hang)) ?? $hang;
        $khoaSo = trim($khoaSo);

        return 'DANH SÁCH HỌC VIÊN HỌC LÁI XE Ô TÔ HẠNG '.$hang.($khoaSo !== '' ? ' KHÓA '.$khoaSo : '');
    }

    public static function dongKemTheo(string $soQd, CarbonInterface $ngay, string $dsNhom): string
    {
        $soQdFull = $soQd.QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD;

        return '(Kèm theo Quyết định số: '.$soQdFull.' ngày '.$ngay->format('d/m/Y')
            .' của Trung tâm GDNN Mạnh Linh về việc Hoàn thành lớp học Đào tạo lái xe & Cấp giấy xác nhận hoàn thành khóa đào tạo lái xe cho '
            .$dsNhom.')';
    }

    /** @return array{ho_dem: string, ten: string} */
    public static function tachHoTen(string $hoTen): array
    {
        $hoTen = trim($hoTen);
        if ($hoTen === '') {
            return ['ho_dem' => '', 'ten' => ''];
        }
        $parts = preg_split('/\s+/u', $hoTen) ?: [$hoTen];
        if (count($parts) === 1) {
            return ['ho_dem' => '', 'ten' => $parts[0]];
        }
        $ten = (string) array_pop($parts);
        $hoDem = implode(' ', $parts);
        if ($hoDem !== '') {
            $hoDem .= ' ';
        }

        return ['ho_dem' => $hoDem, 'ten' => $ten];
    }

    public static function soGiayXacNhanHt(string $maDk, string $hang): string
    {
        $maDk = trim($maDk);
        if ($maDk === '') {
            return '';
        }
        $suffix = self::hauToHangGiay($hang);

        return $suffix !== '' ? $maDk.'-'.$suffix : $maDk;
    }

    public static function hauToHangGiay(string $hang): string
    {
        $hang = trim($hang);
        if ($hang === '') {
            return '';
        }
        if (preg_match('/^([A-Za-z0-9]+)/u', $hang, $m)) {
            return strtoupper($m[1]);
        }

        return '';
    }

    private static function copyRowStyle(Worksheet $sheet, int $fromRow, int $toRow): void
    {
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->duplicateStyle($sheet->getStyle($col.$fromRow), $col.$toRow);
        }
        $sheet->getRowDimension($toRow)->setRowHeight($sheet->getRowDimension($fromRow)->getRowHeight());
    }
}