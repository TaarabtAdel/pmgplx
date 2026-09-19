<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatDSPhienExcelExporter
{
    /**
     * @param  Collection<int, DatDSPhien>  $items
     * @param  array<int, list<string>>  $violationsById
     * @param  array<string, array{label: string, badge: string}>  $loiDefinitions
     * @param  array<int, array{gio_tu_dong: float, km_tu_dong: float, gio_dem: float, km_dem: float, gio_cao_toc: float}>  $chiTieuById
     */
    public static function download(
        Collection $items,
        array $violationsById,
        array $loiDefinitions,
        array $expectedPhanCongById = [],
        array $chiTieuById = []
    ): StreamedResponse {
        $spreadsheet = self::buildSpreadsheet(
            $items,
            $violationsById,
            $loiDefinitions,
            $expectedPhanCongById,
            $chiTieuById
        );
        $filename = 'dat-phien-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, DatDSPhien>  $items
     * @param  array<int, list<string>>  $violationsById
     * @param  array<string, array{label: string, badge: string}>  $loiDefinitions
     * @param  array<int, array{gio_tu_dong: float, km_tu_dong: float, gio_dem: float, km_dem: float, gio_cao_toc: float}>  $chiTieuById
     */
    private static function buildSpreadsheet(
        Collection $items,
        array $violationsById,
        array $loiDefinitions,
        array $expectedPhanCongById = [],
        array $chiTieuById = []
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sach phien');

        $headers = [
            'STT',
            'Mã phiên học',
            'Mã học viên',
            'Họ tên học viên',
            'Mã khóa học',
            'Tên khóa học',
            'Mã giáo viên',
            'Họ tên giáo viên',
            'Biển số xe',
            'Bắt đầu',
            'Kết thúc',
            'TH (phút)',
            'Tỉ lệ ND (%)',
            'Số KM',
            'Số giờ tự động',
            'Số km tự động',
            'Số giờ đêm',
            'Số km đêm',
            'Cao tốc',
            'Đạt',
            'Phân loại',
            'Cảnh báo',
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 1], $header);
        }

        $rowIndex = 2;
        foreach ($items as $index => $item) {
            $start = $item->ThoiGianBatDauPhienHoc;
            $end = $item->ThoiGianKetThucPhienHoc;
            $phut = ($start && $end) ? $start->diffInRealMinutes($end) : null;
            $tiLe = $item->TiLeNhanDien !== null ? (float) $item->TiLeNhanDien : null;
            $loiCodes = $violationsById[(int) $item->Id] ?? [];
            $expectedPhanCong = $expectedPhanCongById[(int) $item->Id] ?? [];
            $datPhien = DatDSPhienKiemTra::datPhien($violationsById, (int) $item->Id);
            $canhBaoText = collect($loiCodes)
                ->map(fn (string $code): string => DatDSPhienKiemTra::violationLabel($code, $expectedPhanCong))
                ->implode('; ');

            $phanLoaiText = $item->phanLoai
                ->pluck('TenPhanLoai')
                ->filter()
                ->implode(', ');

            $chiTieu = $chiTieuById[(int) $item->Id] ?? [
                'gio_tu_dong' => 0.0,
                'km_tu_dong' => 0.0,
                'gio_dem' => 0.0,
                'km_dem' => 0.0,
                'gio_cao_toc' => 0.0,
            ];

            $sheet->fromArray([
                $index + 1,
                $item->MaPhienHoc,
                $item->MaHocVien,
                $item->HoTenHocVien,
                $item->MaKhoaHoc,
                $item->TenKhoaHoc,
                $item->MaGiaoVien,
                $item->HoTenGiaoVien,
                $item->BienSoXe,
                $start?->format('d/m/Y H:i'),
                $end?->format('d/m/Y H:i'),
                $phut !== null ? round($phut) : null,
                $tiLe,
                $item->QuangDuongThucHanhKm !== null ? round((float) $item->QuangDuongThucHanhKm, 2) : null,
                self::excelNumber($chiTieu['gio_tu_dong']),
                self::excelNumber($chiTieu['km_tu_dong']),
                self::excelNumber($chiTieu['gio_dem']),
                self::excelNumber($chiTieu['km_dem']),
                self::excelNumber($chiTieu['gio_cao_toc']),
                $datPhien ? 'Đạt' : 'Không đạt',
                $phanLoaiText !== '' ? $phanLoaiText : null,
                $canhBaoText !== '' ? $canhBaoText : null,
            ], null, 'A'.$rowIndex);

            $rowIndex++;
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private static function excelNumber(float $value): ?float
    {
        return $value > 0 ? round($value, 2) : null;
    }
}
