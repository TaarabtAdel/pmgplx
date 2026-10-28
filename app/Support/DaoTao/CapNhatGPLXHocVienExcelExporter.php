<?php

namespace App\Support\DaoTao;

use App\Support\PMGPLX\NgayVn;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Xuất danh sách cập nhật GPLX học viên (toàn bộ theo bộ lọc, không phân trang). */
final class CapNhatGPLXHocVienExcelExporter
{
    private const HEADER_COLOR = 'FF1565C0';

    /**
     * @param  Collection<int, CapNhatGPLXHocVien>  $rows
     * @param  Collection<string, object>  $hocVien
     * @param  list<string>  $moTaBoLoc
     */
    public static function download(Collection $rows, Collection $hocVien, array $moTaBoLoc, string $tenFile): StreamedResponse
    {
        $spreadsheet = self::build($rows, $hocVien, $moTaBoLoc);

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
    private static function build(Collection $rows, Collection $hocVien, array $moTaBoLoc): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cap nhat GPLX');

        $cot = [
            ['STT', 5], ['Mã ĐK', 22], ['Họ và tên', 26], ['Ngày sinh', 11], ['Số CMT', 15],
            ['Mã khoá học', 15], ['Hạng ĐT', 8],
            ['GPLX đã có (PM)', 16], ['Hạng đã có', 10], ['Ngày cấp đã có', 13], ['Ngày HH đã có', 13],
            ['Số GPLX mới', 16], ['Hạng GPLX mới', 12], ['Ngày cấp mới', 13], ['Ngày HH mới', 13], ['Nơi cấp mới', 22],
            ['Cập nhật GPLX', 16], ['Ghi chú', 28],
        ];
        $soCot = count($cot);
        $cotCuoi = self::chuCot($soCot);

        $sheet->setCellValue('A1', 'Danh sách cập nhật GPLX học viên');
        $sheet->setCellValue('A2', 'Bộ lọc: '.($moTaBoLoc === [] ? 'tất cả' : implode(' · ', $moTaBoLoc)));
        $sheet->setCellValue('A3', 'Xuất lúc: '.now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s')
            .' · '.number_format($rows->count()).' học viên · lưu trên MANHLINH, không ghi GPLX_BAN_MOI');
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
            $hoTen = $hv ? ($hv->HoVaTen ?: trim(($hv->HoDemNLX ?? '').' '.($hv->TenNLX ?? ''))) : '(không tìm thấy bên PMGPLX)';
            $sheet->fromArray([
                $i + 1,
                (string) $row->MaDK,
                $hoTen,
                $hv ? NgayVn::format($hv->NgaySinh, 'yyyymmdd') : '',
                (string) ($hv?->SoCMT ?? ''),
                (string) $row->MaKhoaHoc,
                (string) (($hv?->HangGPLX) ?: ($row->HangGPLX ?? '')),
                (string) ($hv?->SoGPLXDaCo ?? ''),
                (string) ($hv?->HangGPLXDaCo ?? ''),
                self::ngayPm($hv?->NgayCapGPLXDaCo),
                self::ngayPm($hv?->NgayHHGPLXDaCo),
                (string) ($row->SoGPLX ?? ''),
                (string) ($row->HangGPLX ?? ''),
                $row->NgayCapGPLX?->format('d/m/Y') ?? '',
                $row->NgayHHGPLX?->format('d/m/Y') ?? '',
                (string) ($row->NoiCapGPLX ?? ''),
                $row->DaCapNhatGPLX ? 'Đã cập nhật '.($row->NgayCapNhatGPLX?->format('d/m/Y') ?? '') : 'Chưa',
                (string) $row->GhiChu,
            ], null, 'A'.$r, true);
            $sheet->setCellValueExplicit('B'.$r, (string) $row->MaDK, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$r, (string) ($hv?->SoCMT ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('H'.$r, (string) ($hv?->SoGPLXDaCo ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('L'.$r, (string) ($row->SoGPLX ?? ''), DataType::TYPE_STRING);
            $r++;
        }

        $dauDuLieu = $dongTieuDe + 1;
        $cuoiDuLieu = max($r - 1, $dongTieuDe);
        $sheet->getStyle('A'.$dongTieuDe.':'.$cotCuoi.$cuoiDuLieu)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('D'.$dauDuLieu);
        $sheet->setAutoFilter('A'.$dongTieuDe.':'.$cotCuoi.$cuoiDuLieu);
        $page = $sheet->getPageSetup();
        $page->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $page->setFitToPage(true);
        $page->setFitToWidth(1);
        $page->setFitToHeight(0);

        return $spreadsheet;
    }

    private static function ngayPm(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (strlen($s) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            return substr($s, 8, 2).'/'.substr($s, 5, 2).'/'.substr($s, 0, 4);
        }

        return NgayVn::format($s, 'yyyymmdd');
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
