<?php

namespace App\Support\DaoTao;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatTheoDoiDatExcelExporter
{
    private const COLOR_HEADER = 'FFD9E8F7';

    private const COLOR_HEADER_DATE = 'FFEEF4FB';

    private const COLOR_SERVER = 'FFE8F5E9';

    private const COLOR_WARNING = 'FFFFF3CD';

    private const COLOR_BORDER = 'FFB8CFE6';

    private const COLOR_HEADER_FONT = 'FF1A3A5C';

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
        Collection $giaoVienNames,
        bool $anCotTuDong = false
    ): StreamedResponse {
        $spreadsheet = self::buildSpreadsheet(
            $groups,
            $filters,
            $tenKhoaHoc,
            $hasNgayFilter,
            $ngayHeading,
            $giaoVienNames,
            $anCotTuDong
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
        Collection $giaoVienNames,
        bool $anCotTuDong
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Theo doi DAT');

        $col = 1;
        $colStt = $col++;
        $colHv = $col++;
        $colGv = $col++;
        $colBks = $col++;
        $colGioTuDong = $anCotTuDong ? 0 : $col++;
        $colKmTuDong = $anCotTuDong ? 0 : $col++;
        $colGioDem = $col++;
        $colKmDem = $col++;
        $colCaoToc = $col++;
        $colGioMayChu = $col++;
        $colKmMayChu = $col++;
        $colGioNgay = $hasNgayFilter ? $col++ : 0;
        $colKmNgay = $hasNgayFilter ? $col++ : 0;
        $colTongKmNgay = $hasNgayFilter ? $col++ : 0;
        $cungLichCol = $col++;
        $cungGvCol = $col++;
        $lastCol = $cungGvCol;
        $lastLetter = self::columnLetter($lastCol);
        $serverStartCol = $colGioTuDong > 0 ? $colGioTuDong : $colGioDem;
        $serverEndCol = $colKmMayChu;

        $khoaLabel = $tenKhoaHoc !== '' ? $tenKhoaHoc.' ('.$filters['ma_khoa_hoc'].')' : $filters['ma_khoa_hoc'];
        $sheet->setCellValue('A1', 'Khóa: '.$khoaLabel);
        $sheet->mergeCells('A1:'.$lastLetter.'1');

        $scope = $hasNgayFilter
            ? 'Phạm vi: Tổng toàn khóa + chi tiết ngày '.$ngayHeading
            : 'Phạm vi: Tổng toàn khóa (tất cả ngày)';
        $scope .= ($filters['chi_cong_phien_dat'] ?? true) ? ' · Chỉ phiên đạt' : ' · Tất cả phiên';
        $scope .= ' · Giờ/km đêm: lịch Ban đêm + LaBanDem';
        if (($filters['ma_giao_vien'] ?? '') !== '') {
            $scope .= ' · GV: '.self::formatGiaoVienLabel($filters['ma_giao_vien'], $giaoVienNames);
        }
        if (($filters['bien_so_xe'] ?? '') !== '') {
            $scope .= ' · Xe: '.$filters['bien_so_xe'];
        }
        $sheet->setCellValue('A2', $scope);
        $sheet->mergeCells('A2:'.$lastLetter.'2');

        $headerRow1 = 4;
        $headerRow2 = 5;

        $sheet->setCellValue(self::columnLetter($colStt).$headerRow1, 'STT');
        $sheet->mergeCells(self::columnLetter($colStt).$headerRow1.':'.self::columnLetter($colStt).$headerRow2);
        $sheet->setCellValue(self::columnLetter($colHv).$headerRow1, 'Họ và tên học viên');
        $sheet->setCellValue(self::columnLetter($colGv).$headerRow1, 'GVTH');
        $sheet->setCellValue(self::columnLetter($colBks).$headerRow1, 'BKS');
        $sheet->mergeCells(self::columnLetter($colBks).$headerRow1.':'.self::columnLetter($colBks).$headerRow2);

        if ($colGioTuDong > 0) {
            $sheet->setCellValue(self::columnLetter($colGioTuDong).$headerRow1, 'Số giờ tự động');
            $sheet->mergeCells(self::columnLetter($colGioTuDong).$headerRow1.':'.self::columnLetter($colGioTuDong).$headerRow2);
            $sheet->setCellValue(self::columnLetter($colKmTuDong).$headerRow1, 'Số km tự động');
            $sheet->mergeCells(self::columnLetter($colKmTuDong).$headerRow1.':'.self::columnLetter($colKmTuDong).$headerRow2);
        }

        $sheet->setCellValue(self::columnLetter($colGioDem).$headerRow1, 'Số giờ đêm');
        $sheet->mergeCells(self::columnLetter($colGioDem).$headerRow1.':'.self::columnLetter($colGioDem).$headerRow2);
        $sheet->setCellValue(self::columnLetter($colKmDem).$headerRow1, 'Số km đêm');
        $sheet->mergeCells(self::columnLetter($colKmDem).$headerRow1.':'.self::columnLetter($colKmDem).$headerRow2);
        $sheet->setCellValue(self::columnLetter($colCaoToc).$headerRow1, 'Cao tốc');
        $sheet->mergeCells(self::columnLetter($colCaoToc).$headerRow1.':'.self::columnLetter($colCaoToc).$headerRow2);
        $sheet->setCellValue(self::columnLetter($colGioMayChu).$headerRow1, "Tổng giờ\nmáy chủ");
        $sheet->mergeCells(self::columnLetter($colGioMayChu).$headerRow1.':'.self::columnLetter($colGioMayChu).$headerRow2);
        $sheet->setCellValue(self::columnLetter($colKmMayChu).$headerRow1, "Tổng KM\nmáy chủ");
        $sheet->mergeCells(self::columnLetter($colKmMayChu).$headerRow1.':'.self::columnLetter($colKmMayChu).$headerRow2);

        if ($hasNgayFilter) {
            $sheet->setCellValue(self::columnLetter($colGioNgay).$headerRow1, $ngayHeading);
            $sheet->mergeCells(self::columnLetter($colGioNgay).$headerRow1.':'.self::columnLetter($colTongKmNgay).$headerRow1);
            $sheet->setCellValue(self::columnLetter($colGioNgay).$headerRow2, "Số giờ\ntrong ngày");
            $sheet->setCellValue(self::columnLetter($colKmNgay).$headerRow2, "Số km\ntrong ngày");
            $sheet->setCellValue(self::columnLetter($colTongKmNgay).$headerRow2, "Tổng số km\ntrong ngày");
        }

        $sheet->setCellValue(self::columnLetter($cungLichCol).$headerRow1, "Cung đường theo\nlịch giảng dạy");
        $sheet->mergeCells(self::columnLetter($cungLichCol).$headerRow1.':'.self::columnLetter($cungLichCol).$headerRow2);
        $sheet->setCellValue(self::columnLetter($cungGvCol).$headerRow1, "Cung đường\ngiáo viên chạy");
        $sheet->mergeCells(self::columnLetter($cungGvCol).$headerRow1.':'.self::columnLetter($cungGvCol).$headerRow2);

        $sheet->setCellValue(self::columnLetter($colHv).$headerRow2, 'Mã học viên');
        $sheet->setCellValue(self::columnLetter($colGv).$headerRow2, 'Mã giáo viên');

        self::styleHeader($sheet, 'A'.$headerRow1.':'.$lastLetter.$headerRow2);
        if ($hasNgayFilter) {
            $sheet->getStyle(self::columnLetter($colGioNgay).$headerRow1.':'.self::columnLetter($colTongKmNgay).$headerRow1)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HEADER_DATE);
        }

        $rowIndex = 6;
        foreach ($groups as $group) {
            $students = $group['students'] ?? [];
            $rowspan = count($students);
            if ($rowspan === 0) {
                continue;
            }

            $groupStart = $rowIndex;
            $groupEnd = $rowIndex + $rowspan - 1;

            foreach ($students as $index => $student) {
                $isFirst = $index === 0;
                $hoTen = (string) ($student['ho_ten'] ?? '');
                $maHv = (string) ($student['ma_hoc_vien'] ?? '');
                $nameCell = $hoTen;
                if ($maHv !== '') {
                    $nameCell .= "\n".$maHv;
                }
                if (! empty($student['ngoai_phan_cong'])) {
                    $nameCell .= "\nChưa phân công";
                }

                $sheet->setCellValue(self::columnLetter($colStt).$rowIndex, $student['stt'] ?? '');
                $sheet->setCellValue(self::columnLetter($colHv).$rowIndex, $nameCell);

                if ($isFirst) {
                    $gvTen = (string) ($group['ho_ten_giao_vien'] ?? '');
                    $gvMa = (string) ($group['ma_giao_vien'] ?? '');
                    $gvCell = $gvTen;
                    if ($gvMa !== '' && $gvMa !== DatTheoDoiDat::placeholder()) {
                        $gvCell .= "\n".$gvMa;
                    }
                    $sheet->setCellValue(self::columnLetter($colGv).$rowIndex, $gvCell);
                    $sheet->setCellValue(self::columnLetter($colBks).$rowIndex, self::exportCell((string) ($group['bien_so_xe'] ?? '')));
                }

                if ($colGioTuDong > 0) {
                    $sheet->setCellValue(self::columnLetter($colGioTuDong).$rowIndex, self::exportCell((string) ($student['gio_tu_dong'] ?? '')));
                    $sheet->setCellValue(self::columnLetter($colKmTuDong).$rowIndex, self::exportCell((string) ($student['km_may_chu'] ?? '')));
                }
                $sheet->setCellValue(self::columnLetter($colGioDem).$rowIndex, self::exportCell((string) ($student['chay_dem'] ?? '')));
                $sheet->setCellValue(self::columnLetter($colKmDem).$rowIndex, self::exportCell((string) ($student['km_dem'] ?? '')));
                $sheet->setCellValue(self::columnLetter($colCaoToc).$rowIndex, self::exportCell((string) ($student['cao_toc'] ?? '')));
                $sheet->setCellValue(self::columnLetter($colGioMayChu).$rowIndex, self::exportCell((string) ($student['gio_may_chu'] ?? '')));
                $sheet->setCellValue(self::columnLetter($colKmMayChu).$rowIndex, self::exportCell((string) ($student['tong_km_may_chu'] ?? '')));

                if ($hasNgayFilter) {
                    $sheet->setCellValue(self::columnLetter($colGioNgay).$rowIndex, self::exportCell((string) ($student['gio_trong_ngay'] ?? '')));
                    $sheet->setCellValue(self::columnLetter($colKmNgay).$rowIndex, self::exportCell((string) ($student['km_trong_ngay'] ?? '')));
                    if ($isFirst) {
                        $sheet->setCellValue(self::columnLetter($colTongKmNgay).$rowIndex, self::exportCell((string) ($group['tong_km_ngay'] ?? '')));
                    }
                }

                if ($isFirst) {
                    $sheet->setCellValue(
                        self::columnLetter($cungLichCol).$rowIndex,
                        self::exportCell((string) ($group['cung_duong_lich'] ?? ''))
                    );
                    $sheet->setCellValue(
                        self::columnLetter($cungGvCol).$rowIndex,
                        self::exportCell((string) ($group['cung_duong_gv'] ?? ''))
                    );
                }

                $sheet->getStyle(self::columnLetter($serverStartCol).$rowIndex.':'.self::columnLetter($serverEndCol).$rowIndex)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_SERVER);

                if (! empty($student['ngoai_phan_cong'])) {
                    $sheet->getStyle('A'.$rowIndex.':'.$lastLetter.$rowIndex)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_WARNING);
                    $sheet->getStyle(self::columnLetter($serverStartCol).$rowIndex.':'.self::columnLetter($serverEndCol).$rowIndex)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFE69C');
                }

                $rowIndex++;
            }

            if ($rowspan > 1) {
                $sheet->mergeCells(self::columnLetter($colGv).$groupStart.':'.self::columnLetter($colGv).$groupEnd);
                $sheet->mergeCells(self::columnLetter($colBks).$groupStart.':'.self::columnLetter($colBks).$groupEnd);
                if ($hasNgayFilter) {
                    $sheet->mergeCells(self::columnLetter($colTongKmNgay).$groupStart.':'.self::columnLetter($colTongKmNgay).$groupEnd);
                }
                $sheet->mergeCells(self::columnLetter($cungLichCol).$groupStart.':'.self::columnLetter($cungLichCol).$groupEnd);
                $sheet->mergeCells(self::columnLetter($cungGvCol).$groupStart.':'.self::columnLetter($cungGvCol).$groupEnd);
            }
        }

        $lastDataRow = $rowIndex > 6 ? $rowIndex - 1 : $headerRow2;
        $sheet->getStyle('A'.$headerRow1.':'.$lastLetter.$lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        if ($lastDataRow >= 6) {
            $sheet->getStyle('B6:C'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle(self::columnLetter($cungLichCol).'6:'.self::columnLetter($cungGvCol).$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(12);
        foreach (range(5, $lastCol) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setWidth(14);
        }
        $sheet->getColumnDimensionByColumn($cungLichCol)->setWidth(22);
        $sheet->getColumnDimensionByColumn($cungGvCol)->setWidth(22);
        $sheet->getRowDimension($headerRow1)->setRowHeight(28);
        $sheet->getRowDimension($headerRow2)->setRowHeight(28);
        $sheet->freezePane('A6');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return $spreadsheet;
    }

    private static function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => self::COLOR_HEADER_FONT],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::COLOR_HEADER],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);
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
