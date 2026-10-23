<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\ThuTienHocVien;
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

/** Xuất danh sách thu tiền học viên (toàn bộ theo bộ lọc, không phân trang). */
final class ThuTienHocVienExcelExporter
{
    private const HEADER_COLOR = 'FF1565C0';

    /** Âm hiện đỏ — cùng quy ước với màn hình. */
    private const DINH_DANG_TIEN = '#,##0;[Red]-#,##0';

    /**
     * @param  Collection<int, ThuTienHocVien>  $rows
     * @param  Collection<string, object>  $hocVien  MaDK => thông tin bên PMGPLX
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
     * @param  Collection<int, ThuTienHocVien>  $rows
     * @param  Collection<string, object>  $hocVien
     * @param  list<string>  $moTaBoLoc
     */
    private static function build(Collection $rows, Collection $hocVien, array $moTaBoLoc): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Thu tien HV');

        $cot = [
            ['STT', 5], ['Mã ĐK', 22], ['Họ và tên', 26], ['Ngày sinh', 11], ['Số CMT', 15], ['Mã khoá học', 15],
            ['Hạng GPLX', 8], ['Tốt nghiệp', 11], ['Thu tiền', 13],
            ['Tiền LT', 12], ['KQ LT', 15], ['Tiền Sa hình', 12], ['KQ Sa hình', 15], ['Tiền Đường', 12], ['KQ Đường', 15],
            ['Bìa', 11], ['Tổng', 13], ['Phải thu', 13], ['Ghi chú', 28],
        ];
        $soCot = count($cot);
        $cotCuoi = self::chuCot($soCot);

        $sheet->setCellValue('A1', 'Danh sách thu tiền học viên');
        $sheet->setCellValue('A2', 'Bộ lọc: '.($moTaBoLoc === [] ? 'tất cả' : implode(' · ', $moTaBoLoc)));
        $sheet->setCellValue('A3', 'Xuất lúc: '.now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s').' · '.number_format($rows->count()).' học viên'
            .' · Phí: LT '.number_format(ThuTienHocVien::PHI['ly_thuyet'], 0, ',', '.')
            .', Sa hình '.number_format(ThuTienHocVien::PHI['sa_hinh'], 0, ',', '.')
            .', Đường '.number_format(ThuTienHocVien::PHI['duong'], 0, ',', '.')
            .', Bìa '.number_format(ThuTienHocVien::PHI['bia'], 0, ',', '.')
            .' — có kết quả thi thì trừ phí môn đó; rớt thì phải thu lại phí thi lại (cột Phải thu)');
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
                (string) ($hv->SoCMT ?? ''),
                (string) $row->MaKhoaHoc,
                (string) ($hv->HangGPLX ?? ''),
                $row->DaTotNghiep ? 'Đã tốt nghiệp' : 'Chưa',
                $row->DaThuTien ? 'Đã thu '.($row->NgayThuTien?->format('d/m/Y') ?? '') : 'Chưa thu',
                $row->tien('ly_thuyet'),
                self::nhanKq($row, 'ly_thuyet'),
                $row->tien('sa_hinh'),
                self::nhanKq($row, 'sa_hinh'),
                $row->tien('duong'),
                self::nhanKq($row, 'duong'),
                $row->tien('bia'),
                $row->tongTien(),
                $row->tongPhaiThu() > 0 ? -$row->tongPhaiThu() : 0,
                (string) $row->GhiChu,
            ], null, 'A'.$r, true);
            // Mã ĐK / CMT dạng chữ để Excel không làm mất số 0 đầu.
            $sheet->setCellValueExplicit('B'.$r, (string) $row->MaDK, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$r, (string) ($hv->SoCMT ?? ''), DataType::TYPE_STRING);
            $r++;
        }

        $dauDuLieu = $dongTieuDe + 1;
        $cuoiDuLieu = $r - 1;
        $sheet->setCellValue('A'.$r, 'Tổng');
        $sheet->mergeCells('A'.$r.':I'.$r);
        foreach (['J', 'L', 'N', 'P', 'Q', 'R'] as $c) {
            $sheet->setCellValue($c.$r, $cuoiDuLieu >= $dauDuLieu ? "=SUM({$c}{$dauDuLieu}:{$c}{$cuoiDuLieu})" : 0);
        }
        $sheet->getStyle('A'.$r.':'.$cotCuoi.$r)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F3F5']],
        ]);
        $sheet->getStyle('A'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach (['J', 'L', 'N', 'P', 'Q', 'R'] as $c) {
            $sheet->getStyle($c.$dauDuLieu.':'.$c.$r)->getNumberFormat()->setFormatCode(self::DINH_DANG_TIEN);
        }
        $sheet->getStyle('Q'.$dauDuLieu.':Q'.$r)->getFont()->setBold(true);
        foreach (['K', 'M', 'O'] as $c) {
            $sheet->getStyle($c.$dauDuLieu.':'.$c.$cuoiDuLieu)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle('A'.$dongTieuDe.':'.$cotCuoi.$r)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->freezePane('D'.$dauDuLieu);
        $sheet->setAutoFilter('A'.$dongTieuDe.':'.$cotCuoi.max($cuoiDuLieu, $dongTieuDe));
        $page = $sheet->getPageSetup();
        $page->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $page->setFitToPage(true);
        $page->setFitToWidth(1);
        $page->setFitToHeight(0);

        return $spreadsheet;
    }

    private static function nhanKq(ThuTienHocVien $row, string $khoan): string
    {
        return $row->nhanKetQua($khoan);
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
