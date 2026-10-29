<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\CapNhatGPLXHocVien;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Xuất danh sách cập nhật GPLX (cột giống mẫu báo cáo). */
final class CapNhatGPLXHocVienExcelExporter
{
    private const HEADER_COLOR = 'FF1565C0';

    /**
     * @param  Collection<int, CapNhatGPLXHocVien>  $rows
     * @param  Collection<string, object>  $hocVien
     * @param  list<string>  $moTaBoLoc
     */
    /**
     * @param  array<string, string>  $dvhcMap
     */
    public static function download(Collection $rows, Collection $hocVien, array $moTaBoLoc, string $tenFile, array $dvhcMap = []): StreamedResponse
    {
        $spreadsheet = self::build($rows, $hocVien, $moTaBoLoc, $dvhcMap);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $tenFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, CapNhatGPLXHocVien>  $rows
     * @param  Collection<string, object>  $hocVien
     * @param  list<string>  $moTaBoLoc
     */
    /**
     * @param  array<string, string>  $dvhcMap
     */
    private static function build(Collection $rows, Collection $hocVien, array $moTaBoLoc, array $dvhcMap = []): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cap nhat GPLX');

        $cot = [
            ['STT', 5],
            ['Họ và tên', 28],
            ['Ngày tháng năm sinh', 12],
            ['Số CMND', 16],
            ['Nơi đăng ký hộ khẩu', 36],
            ['Đã có GPLX', 11],
            ['Số GPLX', 16],
            ['Ngày cấp GPLX', 12],
            ['Ngày trúng tuyển', 12],
        ];
        $soCot = count($cot);
        $cotCuoi = self::chuCot($soCot);

        $sheet->setCellValue('A1', 'Danh sách cập nhật GPLX học viên');
        $sheet->setCellValue('A2', 'Bộ lọc: '.($moTaBoLoc === [] ? 'tất cả' : implode(' · ', $moTaBoLoc)));
        $sheet->setCellValue('A3', 'Xuất lúc: '.now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s')
            .' · '.number_format($rows->count()).' học viên · PMGPLX + MANHLINH (bổ sung khi PM thiếu)');
        foreach ([1, 2, 3] as $r) {
            $sheet->mergeCells('A'.$r.':'.$cotCuoi.$r);
        }
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $dongTieuDe = 5;
        foreach ($cot as $i => [$nhan, $rong]) {
            $chu = self::chuCot($i + 1);
            $sheet->setCellValue($chu.$dongTieuDe, $nhan);
            $sheet->getColumnDimension($chu)->setWidth($rong);
        }
        $sheet->getStyle('A'.$dongTieuDe.':'.$cotCuoi.$dongTieuDe)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_COLOR]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $r = $dongTieuDe + 1;
        foreach ($rows->values() as $i => $row) {
            $hv = $hocVien[(string) $row->MaDK] ?? null;
            $ht = CapNhatGPLXHienThi::dong($row, $hv, $row->boSung, $dvhcMap);
            $tenCell = $ht['ho_ten'] !== ''
                ? $ht['ho_ten_xuat']
                : '(không tìm thấy bên PMGPLX)';
            $sheet->fromArray([
                $i + 1,
                $tenCell,
                $ht['ngay_sinh'],
                $ht['so_cmnd'],
                $ht['noi_dk_ho_khau'],
                $ht['da_co_gplx'],
                $ht['so_gplx'],
                $ht['ngay_cap_gplx'],
                $ht['ngay_trung_tuyen'],
            ], null, 'A'.$r, true);
            $sheet->setCellValueExplicit('D'.$r, $ht['so_cmnd'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G'.$r, $ht['so_gplx'], DataType::TYPE_STRING);
            $r++;
        }

        $dauDuLieu = $dongTieuDe + 1;
        $cuoiDuLieu = max($r - 1, $dongTieuDe);
        $sheet->getStyle('A'.$dongTieuDe.':'.$cotCuoi.$cuoiDuLieu)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->freezePane('B'.$dauDuLieu);
        $sheet->setAutoFilter('A'.$dongTieuDe.':'.$cotCuoi.$cuoiDuLieu);
        $page = $sheet->getPageSetup();
        $page->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $page->setFitToPage(true);
        $page->setFitToWidth(1);
        $page->setFitToHeight(0);

        return $spreadsheet;
    }

    private static function chuCot(int $i): string
    {
        $s = '';
        while ($i > 0) {
            $i--;
            $s = chr(65 + ($i % 26)).$s;
            $i = intdiv($i, 26);
        }

        return $s;
    }
}
