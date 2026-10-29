<?php

namespace App\Support\QldtOto;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DanhSachHocSinhLaiXeExcelExporter
{
    public const TEMPLATE = 'templates/oto/danh-sach-hoc-vien.xlsx';

    private const DATA_START_ROW = 5;

    private const TEMPLATE_DATA_ROWS = 100;

    private const FOOTER_ROW_TEMPLATE = 105;

    /**
     * @param  list<array<string, mixed>>  $hocVienRows
     */
    public function download(array $meta, array $hocVienRows, string $downloadName): BinaryFileResponse
    {
        $tmp = storage_path('app/tmp/danh-sach-hoc-sinh-'.uniqid('', true).'.xlsx');
        $this->generate($meta, $hocVienRows, $tmp);

        return response()->download($tmp, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * @param  array{
     *     tieu_de: string,
     *     dong_quyet_dinh: string,
     * }  $meta
     * @param  list<array<string, mixed>>  $hocVienRows
     */
    public function generate(array $meta, array $hocVienRows, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có file mẫu: '.self::TEMPLATE);
        }

        @mkdir(dirname($outputPath), 0755, true);

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $meta['tieu_de']);
        $sheet->setCellValue('A2', $meta['dong_quyet_dinh']);

        $count = count($hocVienRows);
        $extra = max(0, $count - self::TEMPLATE_DATA_ROWS);
        if ($extra > 0) {
            $sheet->insertNewRowBefore(self::FOOTER_ROW_TEMPLATE, $extra);
        }

        $footerRow = self::FOOTER_ROW_TEMPLATE + $extra;

        for ($i = 0; $i < $count; $i++) {
            $row = self::DATA_START_ROW + $i;
            $hv = $hocVienRows[$i];
            if ($row > self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS - 1 + $extra) {
                self::copyRowStyle($sheet, self::DATA_START_ROW, $row);
            }

            $sheet->setCellValue('A'.$row, (int) ($hv['stt'] ?? $i + 1));
            $sheet->setCellValue('B'.$row, (string) ($hv['ho_ten'] ?? ''));
            $sheet->setCellValue('C'.$row, (string) ($hv['ngay_sinh'] ?? ''));
            $sheet->setCellValue('D'.$row, (string) ($hv['gioi_tinh'] ?? ''));
            $sheet->setCellValue('E'.$row, (string) ($hv['quoc_tich'] ?? ''));
            $sheet->setCellValueExplicit('F'.$row, (string) ($hv['so_cmnd'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue('G'.$row, (string) ($hv['dia_chi_thuong_tru'] ?? ''));
            $sheet->setCellValue('H'.$row, (string) ($hv['noi_cu_tru'] ?? ''));
            $sheet->setCellValue('I'.$row, (string) ($hv['ghi_chu'] ?? ''));
        }

        if ($count < self::TEMPLATE_DATA_ROWS) {
            for ($row = self::DATA_START_ROW + $count; $row < self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS; $row++) {
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
                    $sheet->setCellValue($col.$row, null);
                }
            }
        }

        $sheet->setCellValue('B'.$footerRow, '(Danh sách có '.$count.' học viên)');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file Excel danh sách học viên.');
        }

        return $outputPath;
    }

    public static function dongQuyetDinh(
        string $soQdFull,
        CarbonInterface $ngay,
        string $hangGplxLabel,
        string $khoaSo,
    ): string {
        $ngayStr = $ngay->format('d/m/Y');

        return '(Kèm theo Quyết định số: '.$soQdFull.' ngày '.$ngayStr
            ." của Trung tâm GDNN Mạnh Linh \n"
            .'về việc Thành lập lớp học Đào tạo lái xe Hạng '.$hangGplxLabel.' khóa '.$khoaSo.')';
    }

    public static function tieuDe(string $hangTieuDe, string $khoaSo): string
    {
        return 'DANH SÁCH HỌC VIÊN LỚP ĐÀO TẠO LÁI XE HẠNG '.$hangTieuDe.' KHÓA '.$khoaSo;
    }

    private static function copyRowStyle(Worksheet $sheet, int $fromRow, int $toRow): void
    {
        for ($col = 1; $col <= 9; $col++) {
            $from = $sheet->getCellByColumnAndRow($col, $fromRow)->getCoordinate();
            $to = $sheet->getCellByColumnAndRow($col, $toRow)->getCoordinate();
            $sheet->duplicateStyle($sheet->getStyle($from), $to);
        }
        $sheet->getRowDimension($toRow)->setRowHeight(
            $sheet->getRowDimension($fromRow)->getRowHeight()
        );
    }
}
