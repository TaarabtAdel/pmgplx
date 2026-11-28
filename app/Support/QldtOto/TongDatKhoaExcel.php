<?php

namespace App\Support\QldtOto;

use App\Support\DaoTao\PhuLuc6DatBaoCao;
use App\Support\DaoTao\TongDatKhoaDuLieu;
use App\Support\QldtOto\KtmChiTiet\DocSheet;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TongDatKhoaExcel
{
    private const TEMPLATE = 'templates/oto/tong-dat-khoa.goc.xlsx';

    private const ROW_INFO = 3;

    private const ROW_TITLE = 2;

    private const ROW_SUMMARY_DATA = 5;

    private const ROW_STUDENT_DATA = 8;

    /**
     * @param  array<string, mixed>  $tomTat  output of TongDatKhoaDuLieu::tomTatKhoa
     */
    public static function taiVe(array $tomTat, string $ngayBaoCao, string $outputPath): void
    {
        $src = resource_path(self::TEMPLATE);
        if (! is_file($src)) {
            throw new \RuntimeException('Chưa có mẫu: '.self::TEMPLATE);
        }
        @mkdir(dirname($outputPath), 0755, true);
        if (! copy($src, $outputPath)) {
            throw new \RuntimeException('Không copy được mẫu Excel.');
        }

        $spreadsheet = IOFactory::load($outputPath);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $signRow = self::timHangXacNhan($sheet);
            if ($signRow === null) {
                $signRow = self::ROW_STUDENT_DATA + count($tomTat['hoc_vien'] ?? []) + 1;
            }

            $ngay = Carbon::parse($ngayBaoCao);
            self::capNhatNgayDauTrang($sheet, $ngay);
            $sheet->setCellValue(
                'A'.self::ROW_TITLE,
                "BÁO CÁO KẾT QUẢ THỰC HÀNH LÁI XE TRÊN ĐƯỜNG GIAO THÔNG CỦA KHÓA HỌC\n(Ngày báo cáo: ".$ngay->format('d/m/Y').')'
            );
            $sheet->setCellValue('A'.self::ROW_INFO, TongDatKhoaDuLieu::khoiThongTinKhoa($tomTat));

            $tomTatHang = array_values($tomTat['tom_tat_hang'] ?? []);
            $extraHang = max(0, count($tomTatHang) - 1);
            if ($extraHang > 0) {
                $sheet->insertNewRowBefore(self::ROW_SUMMARY_DATA + 1, $extraHang);
                $signRow += $extraHang;
            }

            $firstStuHeader = self::ROW_STUDENT_DATA + $extraHang;
            for ($r = self::ROW_SUMMARY_DATA; $r < $firstStuHeader; $r++) {
                $a = mb_strtolower(trim(DocSheet::cellStr($sheet, 'A'.$r)));
                if (str_contains($a, 'stt') || str_contains($a, 'thông tin quá trình')) {
                    continue;
                }
                foreach (['A', 'B', 'C', 'D', 'G', 'I'] as $col) {
                    $sheet->setCellValue($col.$r, null);
                }
            }

            foreach ($tomTatHang as $i => $hang) {
                $r = self::ROW_SUMMARY_DATA + $i;
                $sheet->setCellValue('A'.$r, $hang['stt']);
                $sheet->setCellValue('B'.$r, $hang['label']);
                $sheet->setCellValue('C'.$r, $hang['tong_hv']);
                $sheet->setCellValue('D'.$r, $hang['du_dk']);
                $sheet->setCellValue('G'.$r, $hang['khong_du_dk']);
            }

            $firstDataRow = self::ROW_STUDENT_DATA + $extraHang;
            $hocVien = array_values($tomTat['hoc_vien'] ?? []);
            $maxOldData = $signRow - $firstDataRow;
            for ($r = $firstDataRow; $r < $signRow; $r++) {
                foreach (['A', 'B', 'C', 'E', 'H', 'J', 'K'] as $col) {
                    $sheet->setCellValue($col.$r, null);
                }
            }

            $needRows = count($hocVien);
            $extraHv = max(0, $needRows - $maxOldData);
            if ($extraHv > 0) {
                $sheet->insertNewRowBefore($signRow, $extraHv);
            }

            foreach ($hocVien as $i => $hv) {
                $r = $firstDataRow + $i;
                $sheet->setCellValue('A'.$r, $hv['stt']);
                $sheet->setCellValue('B'.$r, $hv['ma_hoc_vien']);
                $sheet->setCellValue('C'.$r, $hv['ho_ten']);
                $ngaySinh = PhuLuc6DatBaoCao::excelNgaySinh($hv['ngay_sinh'] ?? null);
                if ($ngaySinh !== null) {
                    $sheet->setCellValue('E'.$r, $ngaySinh);
                }
                $sheet->setCellValue('H'.$r, TongDatKhoaDuLieu::chuoiGio((float) $hv['tong_gio']));
                $sheet->setCellValue('J'.$r, TongDatKhoaDuLieu::chuoiKm((float) $hv['tong_km']));
                $sheet->setCellValue('K'.$r, $hv['ghi_chu'] ?? '');
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outputPath);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function capNhatNgayDauTrang(Worksheet $sheet, Carbon $ngay): void
    {
        $text = 'Quảng Trị, Ngày '.$ngay->day.' tháng '.$ngay->month.' năm '.$ngay->year;
        foreach (['F1', 'G1', 'H1'] as $coord) {
            if (DocSheet::cellStr($sheet, $coord) !== '') {
                $sheet->setCellValue($coord, $text);
                return;
            }
        }
        $sheet->setCellValue('F1', $text);
    }

    private static function timHangXacNhan(Worksheet $sheet): ?int
    {
        for ($r = self::ROW_STUDENT_DATA; $r <= 500; $r++) {
            $a = mb_strtolower(DocSheet::cellStr($sheet, 'A'.$r));
            if (str_contains($a, 'xác nhận của cơ sở')) {
                return $r;
            }
        }

        return null;
    }

    public static function tenFile(string $maKhoa): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $maKhoa) ?: 'khoa';

        return 'tong-dat-'.$safe.'-'.now()->format('Ymd-His').'.xlsx';
    }
}