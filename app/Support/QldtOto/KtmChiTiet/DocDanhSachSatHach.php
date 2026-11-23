<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Danh sách thí sinh dự thi (STT, SBD, Họ tên, Số định danh, Ngày sinh, Hạng, Ghi chú).
 *
 * @return array{
 *     ten_khoa: string,
 *     ngay_thi_tu_file: string,
 *     dia_diem: string,
 *     thi_sinh: list<array{
 *         stt: int, sbd: string, ho_ten: string, cccd: string, ngay_sinh: string, hang: string, ghi_chu: string
 *     }>
 * }
 */
final class DocDanhSachSatHach
{
    public static function doc(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            return self::docTuSheet($sheet);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function docTuSheet(Worksheet $sheet): array
    {

        $tenKhoa = '';
        foreach (range(7, 12) as $r) {
            if (stripos(DocSheet::cellStr($sheet, 'A'.$r), 'Khóa thi') !== false
                || stripos(DocSheet::cellStr($sheet, 'A'.$r), 'Khoa thi') !== false) {
                $tenKhoa = DocSheet::cellStr($sheet, 'C'.$r);
                if ($tenKhoa === '') {
                    $tenKhoa = DocSheet::cellStr($sheet, 'D'.$r);
                }
            }
        }

        $ngayThi = '';
        foreach (range(7, 12) as $r) {
            if (stripos(DocSheet::cellStr($sheet, 'A'.$r), 'Ngày thi') !== false) {
                $ngayThi = DocSheet::cellStr($sheet, 'C'.$r) ?: DocSheet::cellStr($sheet, 'D'.$r);
            }
        }

        $diaDiem = '';
        foreach (range(5, 10) as $r) {
            if (stripos(DocSheet::cellStr($sheet, 'A'.$r), 'Tại') !== false) {
                $diaDiem = DocSheet::cellStr($sheet, 'B'.$r) ?: DocSheet::cellStr($sheet, 'C'.$r);
            }
        }

        $headerRow = DocSheet::timHangTieuDe($sheet, ['sbd', 'số báo danh'], 25);
        if ($headerRow === null) {
            throw new \InvalidArgumentException('Không tìm thấy hàng tiêu đề (SBD) trong file danh sách.');
        }

        $cols = DocSheet::mapCotTuHang($sheet, $headerRow);
        $colSbd = self::cot($cols, ['sbd', 'số báo danh']);
        $colHoTen = self::cot($cols, ['họ và tên', 'họ tên', 'họ va tên']);
        $colCccd = self::cot($cols, ['số định danh', 'số cccd', 'cccd', 'số cmt', 'cmt']);
        $colNs = self::cot($cols, ['ngày sinh', 'ngay sinh']);
        $colHang = self::cot($cols, ['hạng gplx', 'hạng', 'hang']);
        $colGhiChu = self::cot($cols, ['ghi chú', 'ghi chu']);

        $out = [];
        for ($r = $headerRow + 1; $r <= $headerRow + 500; $r++) {
            $sbd = DocSheet::normalizeSbd(self::o($sheet, $r, $colSbd));
            $hoTen = self::o($sheet, $r, $colHoTen);
            if (! self::dongThiSinhHopLe($sbd, $hoTen)) {
                if ($out !== []) {
                    break;
                }

                continue;
            }
            $out[] = [
                'stt' => count($out) + 1,
                'sbd' => $sbd,
                'ho_ten' => mb_strtoupper($hoTen),
                'cccd' => DocSheet::normalizeCccd(self::o($sheet, $r, $colCccd)),
                'ngay_sinh' => self::o($sheet, $r, $colNs),
                'hang' => self::o($sheet, $r, $colHang),
                'ghi_chu' => self::o($sheet, $r, $colGhiChu),
            ];
        }

        if ($out === []) {
            throw new \InvalidArgumentException('File danh sách không có thí sinh.');
        }

        return [
            'ten_khoa' => $tenKhoa,
            'ngay_thi_tu_file' => $ngayThi,
            'dia_diem' => $diaDiem,
            'thi_sinh' => $out,
        ];
    }

    /** Một dòng DS hợp lệ: có SBD thật và họ tên có chữ (bỏ hàng 0, footer Excel). */
    public static function dongThiSinhHopLe(string $sbd, string $hoTen): bool
    {
        $sbd = trim($sbd);
        $hoTen = trim($hoTen);
        if ($sbd === '' || $sbd === '0') {
            return false;
        }
        if ($hoTen === '' || $hoTen === '0') {
            return false;
        }
        if (preg_match('/\p{L}/u', $hoTen) !== 1) {
            return false;
        }

        return true;
    }

    /** @param  array<string, int>  $cols */
    private static function cot(array $cols, array $aliases): ?int
    {
        foreach ($aliases as $a) {
            foreach ($cols as $label => $idx) {
                if (str_contains($label, mb_strtolower($a))) {
                    return $idx;
                }
            }
        }

        return null;
    }

    private static function o(Worksheet $sheet, int $row, ?int $col): string
    {
        if ($col === null) {
            return '';
        }
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return DocSheet::cellStr($sheet, $letter.$row);
    }
}