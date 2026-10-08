<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DatTongHopHocVienExcelExporter
{
    private const HEADER_COLOR = 'FF1565C0';

    /**
     * @param  Collection<int, object>  $rows
     * @param  array<string, mixed>  $filters
     * @param  Collection<string, DatDieuKienDat>  $dieuKienByHang
     */
    public static function download(Collection $rows, array $filters, Collection $dieuKienByHang): StreamedResponse
    {
        $spreadsheet = self::buildSpreadsheet($rows, $filters, $dieuKienByHang);
        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($filters['ma_khoa_hoc'] ?? 'khoa')) ?: 'khoa';
        $filename = 'tong-hop-hoc-vien-dat-'.$safeMa.'-'.now()->format('Ymd-His').'.xlsx';

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
     * @param  array<string, mixed>  $filters
     * @param  Collection<string, DatDieuKienDat>  $dieuKienByHang
     */
    private static function buildSpreadsheet(Collection $rows, array $filters, Collection $dieuKienByHang): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tong hop HV');

        $lastCol = 12;
        $lastLetter = self::columnLetter($lastCol);

        $sheet->setCellValue('A1', 'Tổng hợp học viên DAT');
        $sheet->mergeCells('A1:'.$lastLetter.'1');
        $sheet->setCellValue('A2', self::filterScopeLine($filters));
        $sheet->mergeCells('A2:'.$lastLetter.'2');
        $sheet->setCellValue('A3', 'Xuất lúc: '.now()->format('d/m/Y H:i:s').' · '.number_format($rows->count()).' dòng');
        $sheet->mergeCells('A3:'.$lastLetter.'3');

        $headerRow = 5;
        $headers = [
            'STT',
            'Mã HV',
            'Họ tên',
            'Mã khóa',
            'Tên khóa',
            'Loại KH',
            'Số phiên',
            'Số giờ học',
            'Tổng km',
            'Ban đêm (giờ)',
            'Xe số tđ (giờ)',
            'Đạt CT',
        ];
        foreach ($headers as $i => $label) {
            $sheet->setCellValue(self::columnLetter($i + 1).$headerRow, $label);
        }
        self::styleHeader($sheet, 'A'.$headerRow.':'.$lastLetter.$headerRow);

        $rowIndex = $headerRow + 1;
        $stt = 0;
        foreach ($rows as $item) {
            $stt++;
            $dieuKien = $dieuKienByHang->get($item->LoaiKhoaHoc ?? '');
            $datCt = DatHocVienTongHop::datChuongTrinh($item, $dieuKien);
            $datCtLabel = match ($datCt) {
                true => 'Đạt',
                false => 'Chưa đạt',
                default => '',
            };

            $sheet->fromArray([
                $stt,
                (string) ($item->MaHocVien ?? ''),
                (string) ($item->HoTenHocVien ?? ''),
                (string) ($item->MaKhoaHoc ?? ''),
                (string) ($item->TenKhoaHoc ?? ''),
                (string) ($item->LoaiKhoaHoc ?? ''),
                (int) ($item->SoPhien ?? 0),
                self::exportNumber($item->TongGioHoc ?? null),
                self::exportNumber($item->TongQuangDuongKm ?? null),
                self::exportNumber($item->TongBanDemGio ?? null),
                self::exportNumber($item->TongXeSoTuDongGio ?? null),
                $datCtLabel,
            ], null, 'A'.$rowIndex);

            if ($datCt === false) {
                $sheet->getStyle('A'.$rowIndex.':'.$lastLetter.$rowIndex)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF8E1');
            }

            $rowIndex++;
        }

        $lastDataRow = max($headerRow, $rowIndex - 1);
        $sheet->getStyle('A'.$headerRow.':'.$lastLetter.$lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(22);
        foreach (range(6, $lastCol) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setWidth(12);
        }

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function filterScopeLine(array $filters): string
    {
        $parts = ['Bộ lọc:'];
        if (($filters['ma_khoa_hoc'] ?? '') !== '') {
            $parts[] = 'Khóa '.$filters['ma_khoa_hoc'];
        }
        if (($filters['ma_hoc_vien'] ?? '') !== '') {
            $parts[] = 'HV '.$filters['ma_hoc_vien'];
        }
        if (($filters['loai_khoa_hoc'] ?? '') !== '') {
            $parts[] = 'Loại '.$filters['loai_khoa_hoc'];
        }
        if (($filters['tu_ngay'] ?? '') !== '' || ($filters['den_ngay'] ?? '') !== '') {
            $parts[] = 'Từ '.($filters['tu_ngay'] ?: '…').' đến '.($filters['den_ngay'] ?: '…');
        }
        if (($filters['dat_ct'] ?? '') === 'dat') {
            $parts[] = 'Đạt CT';
        } elseif (($filters['dat_ct'] ?? '') === 'chua_dat') {
            $parts[] = 'Chưa đạt CT';
        }

        return implode(' · ', $parts);
    }

    private static function exportNumber(mixed $value): float|int|string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (float) $value;
    }

    private static function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_COLOR],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);
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
