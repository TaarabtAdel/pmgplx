<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Ghi mốc ngày vào sheet lịch LT+TH (dùng chung C / B01). */
final class LichHocLyThuyetThucHanhFillSheet
{
    /**
     * @param  array<string, Carbon|null>  $moc
     */
    public static function dienMoc(Worksheet $sheet, array $moc, int $rowKiemTra = 29): void
    {
        $kg = $moc['ngay_khai_giang'];
        $bdTh = $moc['ngay_bd_thuc_hanh'];

        self::ghiNgay($sheet, 'D6', $kg);
        self::ghiNgay($sheet, 'E6', $kg?->copy()->addDays(17));

        if ($bdTh) {
            $tru1 = $bdTh->copy()->subDay();
            self::ghiNgay($sheet, 'D10', $tru1);
            self::ghiNgay($sheet, 'E10', $tru1);
        }

        self::ghiNgay($sheet, 'D12', $moc['ngay_bd_hinh_tren']);
        self::ghiNgay($sheet, 'E12', $moc['ngay_kt_hinh_tren']);
        self::ghiNgay($sheet, 'D13', $moc['ngay_bd_hinh_tren']);
        self::ghiNgay($sheet, 'E13', $moc['ngay_kt_hinh_tren']);

        self::ghiNgay($sheet, 'D18', $moc['ngay_bd_cabin']);
        self::ghiNgay($sheet, 'E18', $moc['ngay_kt_cabin']);
        self::ghiNgay($sheet, 'D19', $moc['ngay_bd_dat']);
        self::ghiNgay($sheet, 'E19', $moc['ngay_kt_dat']);
        self::ghiNgay($sheet, 'D25', $moc['ngay_bo_sung_dat']);
        self::ghiNgay($sheet, 'E25', $moc['ngay_kt_bo_sung_dat'] ?? $moc['ngay_bo_sung_dat']);

        $bdHd = $moc['ngay_bd_hinh_duoi'];
        self::ghiNgay($sheet, 'D26', $bdHd);
        self::ghiNgay($sheet, 'E26', $bdHd);
        self::ghiNgay($sheet, 'D27', $bdHd?->copy()->addDay());
        self::ghiNgay($sheet, 'E27', $moc['ngay_kt_hinh_duoi']);

        self::ghiNgay($sheet, 'D28', $moc['ngay_bd_on_luyen']);
        self::ghiNgay($sheet, 'E28', $moc['ngay_kt_on_luyen']);
        self::ghiNgay($sheet, 'D'.$rowKiemTra, $moc['ngay_kiem_tra']);
        self::ghiNgay($sheet, 'E'.$rowKiemTra, $moc['ngay_kiem_tra']);
    }

    private static function ghiNgay(Worksheet $sheet, string $cell, ?Carbon $ngay): void
    {
        if ($ngay === null) {
            return;
        }
        $sheet->getCell($cell)->setValue(Date::PHPToExcel($ngay->copy()->startOfDay()));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    }
}
