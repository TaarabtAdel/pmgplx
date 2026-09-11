<?php

namespace App\Support\DaoTao;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatTheoDoiDatExcelExporter
{
    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  array{
     *     ma_khoa_hoc: string,
     *     ngay: string,
     *     chi_cong_phien_dat: bool,
     *     ma_giao_vien: string,
     *     bien_so_xe: string
     * }  $filters
     */
    public static function download(
        array $groups,
        array $filters,
        string $tenKhoaHoc,
        bool $hasNgayFilter,
        string $ngayHeading,
        Collection $giaoVienNames
    ): StreamedResponse {
        $spreadsheet = self::buildSpreadsheet(
            $groups,
            $filters,
            $tenKhoaHoc,
            $hasNgayFilter,
            $ngayHeading,
            $giaoVienNames
        );

        $safeMaKh = preg_replace('/[^A-Za-z0-9_-]+/', '-', $filters['ma_khoa_hoc']) ?: 'khoa';
        $filename = 'theo-doi-dat-'.$safeMaKh.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  array{
     *     ma_khoa_hoc: string,
     *     ngay: string,
     *     chi_cong_phien_dat: bool,
     *     ma_giao_vien: string,
     *     bien_so_xe: string
     * }  $filters
     */
    private static function buildSpreadsheet(
        array $groups,
        array $filters,
        string $tenKhoaHoc,
        bool $hasNgayFilter,
        string $ngayHeading,
        Collection $giaoVienNames
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Theo doi DAT');

        $sheet->setCellValue('A1', 'Khóa học');
        $sheet->setCellValue('B1', $tenKhoaHoc !== '' ? $tenKhoaHoc.' ('.$filters['ma_khoa_hoc'].')' : $filters['ma_khoa_hoc']);
        $sheet->setCellValue('C1', 'Xuất lúc');
        $sheet->setCellValue('D1', now()->format('d/m/Y H:i:s'));

        $sheet->setCellValue('A2', 'Phạm vi');
        $sheet->setCellValue('B2', $hasNgayFilter
            ? 'Tổng toàn khóa + chi tiết ngày '.$ngayHeading
            : 'Tổng toàn khóa (tất cả ngày)');
        $sheet->setCellValue('C2', 'Phiên');
        $sheet->setCellValue('D2', ($filters['chi_cong_phien_dat'] ?? true) ? 'Chỉ phiên đạt' : 'Tất cả phiên');

        $filterParts = [];
        if (($filters['ma_giao_vien'] ?? '') !== '') {
            $filterParts[] = 'GV: '.self::formatGiaoVienLabel($filters['ma_giao_vien'], $giaoVienNames);
        }
        if (($filters['bien_so_xe'] ?? '') !== '') {
            $filterParts[] = 'Xe: '.$filters['bien_so_xe'];
        }
        $sheet->setCellValue('A3', 'Bộ lọc');
        $sheet->setCellValue('B3', $filterParts !== [] ? implode(' · ', $filterParts) : 'Tất cả GV / xe');

        $headers = [
            'STT',
            'Mã học viên',
            'Họ và tên học viên',
            'Chưa phân công',
            'Mã giáo viên',
            'GVTH',
            'BKS',
            'Số giờ tự động máy chủ ghi nhận',
            'Số km tự động ghi nhận',
            'Số giờ đêm ghi nhận',
            'Số km đêm ghi nhận',
            'Tổng Số giờ GVTH',
            'Tổng Số KM GVTH',
        ];

        if ($hasNgayFilter) {
            $headers[] = 'Số giờ trong ngày ('.$ngayHeading.')';
            $headers[] = 'Số km trong ngày ('.$ngayHeading.')';
            $headers[] = 'Tổng số km trong ngày ('.$ngayHeading.')';
        }

        $headers[] = 'Cung đường theo lịch giảng dạy';
        $headers[] = 'Cung đường giáo viên chạy';

        $headerRow = 5;
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, $headerRow], $header);
        }

        $rowIndex = $headerRow + 1;
        foreach ($groups as $group) {
            foreach ($group['students'] as $student) {
                $row = [
                    $student['stt'],
                    $student['ma_hoc_vien'],
                    $student['ho_ten'],
                    ! empty($student['ngoai_phan_cong']) ? 'Có' : '',
                    $group['ma_giao_vien'],
                    $group['ho_ten_giao_vien'],
                    $group['bien_so_xe'],
                    self::exportCell($student['gio_tu_dong']),
                    self::exportCell($student['km_may_chu']),
                    self::exportCell($student['chay_dem']),
                    self::exportCell($student['km_dem']),
                    self::exportCell($group['gio_gvth']),
                    self::exportCell($group['km_gvth']),
                ];

                if ($hasNgayFilter) {
                    $row[] = self::exportCell($student['gio_trong_ngay']);
                    $row[] = self::exportCell($student['km_trong_ngay']);
                    $row[] = self::exportCell($group['tong_km_ngay']);
                }

                $row[] = self::exportCell($group['cung_duong_lich']);
                $row[] = self::exportCell($group['cung_duong_gv']);

                $sheet->fromArray($row, null, 'A'.$rowIndex);

                if (! empty($student['ngoai_phan_cong'])) {
                    $sheet->getStyle('A'.$rowIndex.':'.self::columnLetter(count($headers)).$rowIndex)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('FFFFF3CD');
                }

                $rowIndex++;
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * @param  Collection<string, \App\Models\PMGPLX\GiaoVien>  $giaoVienNames
     */
    private static function formatGiaoVienLabel(string $maGiaoVien, Collection $giaoVienNames): string
    {
        $gv = $giaoVienNames->get($maGiaoVien);
        if ($gv === null) {
            return $maGiaoVien;
        }

        $ten = trim(trim((string) ($gv->HoTenDem ?? '')).' '.trim((string) ($gv->TenGV ?? '')));

        return $ten !== '' ? $ten.' ('.$maGiaoVien.')' : $maGiaoVien;
    }

    private static function exportCell(string $value): string
    {
        return $value === DatTheoDoiDat::placeholder() ? '' : $value;
    }

    private static function columnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)).$letter;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $letter;
    }
}
