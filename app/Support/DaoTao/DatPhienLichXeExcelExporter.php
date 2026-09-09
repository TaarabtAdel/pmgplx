<?php

namespace App\Support\DaoTao;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatPhienLichXeExcelExporter
{
    /**
     * @param  Collection<int, object{
     *     session: \App\Models\DaoTao\DatDSPhien,
     *     valid: bool,
     *     message: string,
     *     matched: ?\App\Models\PMGPLX\KhoaHocXeTap,
     *     displaySchedule: ?\App\Models\PMGPLX\KhoaHocXeTap
     * }>  $rows
     */
    public static function download(Collection $rows, string $maKhoaHoc): StreamedResponse
    {
        $spreadsheet = self::buildSpreadsheet($rows, $maKhoaHoc);
        $safeMaKh = preg_replace('/[^A-Za-z0-9_-]+/', '-', $maKhoaHoc) ?: 'khoa';
        $filename = 'do-phien-lich-xe-'.$safeMaKh.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private static function buildSpreadsheet(Collection $rows, string $maKhoaHoc): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Do phien lich xe');

        $sheet->setCellValue('A1', 'Mã khóa học');
        $sheet->setCellValue('B1', $maKhoaHoc);
        $sheet->setCellValue('C1', 'Xuất lúc');
        $sheet->setCellValue('D1', now()->format('d/m/Y H:i:s'));

        $headers = [
            'STT',
            'Mã phiên',
            'Mã học viên',
            'Họ tên học viên',
            'Mã giáo viên (lịch)',
            'Giáo viên (lịch)',
            'Xe',
            'TG bắt đầu',
            'TG kết thúc',
            'TG bắt đầu (lịch)',
            'TG kết thúc (lịch)',
            'Kết quả',
            'Ghi chú',
        ];

        $headerRow = 3;
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, $headerRow], $header);
        }

        $rowIndex = $headerRow + 1;
        foreach ($rows as $index => $row) {
            $session = $row->session;
            $lich = $row->displaySchedule;
            $start = $session->ThoiGianBatDauPhienHoc;
            $end = $session->ThoiGianKetThucPhienHoc;
            $lichStart = $lich?->NgayBD;
            $lichEnd = $lich?->NgayKT;
            $ghiChu = $row->valid ? 'Khớp lịch xe tập' : ($row->message ?: '');

            $sheet->fromArray([
                $index + 1,
                $session->MaPhienHoc,
                $session->MaHocVien,
                $session->HoTenHocVien,
                $lich?->MaGV,
                $lich?->TenGV,
                $session->BienSoXe,
                $start?->format('d/m/Y H:i'),
                $end?->format('d/m/Y H:i'),
                $lichStart?->format('d/m/Y H:i'),
                $lichEnd?->format('d/m/Y H:i'),
                $row->valid ? 'Hợp lệ' : 'Cảnh báo',
                $ghiChu !== '' ? $ghiChu : null,
            ], null, 'A'.$rowIndex);

            $rowIndex++;
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
