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
     * @param  array<int, array{ma_giao_vien: string, bien_so_xe: string}>  $expectedPhanCongById
     */
    public static function download(
        Collection $items,
        array $violationsById,
        array $loiDefinitions,
        array $expectedPhanCongById = []
    ): StreamedResponse {
        $spreadsheet = self::buildSpreadsheet($items, $violationsById, $loiDefinitions, $expectedPhanCongById);
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
     * @param  array<int, array{ma_giao_vien: string, bien_so_xe: string}>  $expectedPhanCongById
     */
    private static function buildSpreadsheet(
        Collection $items,
        array $violationsById,
        array $loiDefinitions,
        array $expectedPhanCongById = []
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
}
