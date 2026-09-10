<?php

namespace App\Support\DaoTao;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatPhienLichXeExcelExporter
{
    /**
     * @param  Collection<int, object>  $rows
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
            'Mã giáo viên',
            'Giáo viên',
            'Mã giáo viên (lịch)',
            'Giáo viên (lịch)',
            'Xe',
            'TG phiên',
            'Thời gian (phút)',
            'TG lịch',
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
            $phut = ($start && $end) ? $start->diffInRealMinutes($end) : null;
            $tgPhien = self::formatTimeRange($start, $end);
            $tgLich = self::formatTimeRange($lichStart, $lichEnd);

            $sheet->fromArray([
                $index + 1,
                $session->MaPhienHoc,
                $session->MaHocVien,
                $session->HoTenHocVien,
                $session->MaGiaoVien,
                $session->HoTenGiaoVien,
                $lich?->MaGV,
                $lich?->TenGV,
                $session->BienSoXe,
                $tgPhien,
                $phut !== null ? round($phut) : '',
                $tgLich,
                $row->valid ? 'Hợp lệ' : 'Cảnh báo',
                $row->valid ? 'Khớp lịch xe tập' : ($row->message ?: ''),
            ], null, 'A'.$rowIndex);

            $rowIndex++;
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private static function formatTimeRange(mixed $start, mixed $end): string
    {
        $startText = $start?->format('d/m/Y H:i') ?? '';
        $endText = $end?->format('d/m/Y H:i') ?? '';

        if ($startText === '' && $endText === '') {
            return '';
        }

        if ($startText === '') {
            return $endText;
        }

        if ($endText === '') {
            return $startText;
        }

        return $startText."\n".$endText;
    }
}
