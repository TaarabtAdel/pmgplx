<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;

/** File danh sách Đạt / Trượt (H, ĐT) — khớp CCCD, hoặc Họ tên + Ngày sinh, hoặc SBD. */
final class DocDiemDanhSach
{
    /** @return array<string, string> */
    public static function docDat(string $path): array
    {
        return self::doc($path, 'Đ');
    }

    /** @return array<string, string> */
    public static function docTruot(string $path): array
    {
        return self::doc($path, 'KĐ');
    }

    /**
     * @return array<string, string>
     */
    private static function doc(string $path, string $ketQua): array
    {
        $spreadsheet = IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headerRow = DocSheet::timHangTieuDe(
                $sheet,
                ['cccd', 'họ và tên', 'họ tên', 'sbd', 'stt'],
                55,
            );
            if ($headerRow === null) {
                throw new \InvalidArgumentException('Không tìm hàng tiêu đề trong: '.basename($path));
            }

            $cols = DocSheet::mapCotTuHang($sheet, $headerRow, 35);
            $colCccd = self::timCot($cols, ['cccd', 'số cccd', 'số cmt', 'cmt', 'số định danh']);
            $colHoTen = self::timCot($cols, ['họ và tên', 'họ tên', 'họ va tên']);
            $colNs = self::timCot($cols, ['ngày sinh', 'ngay sinh']);
            $colSbd = self::timCot($cols, ['sbd', 'số báo danh', 'báo danh']);

            if ($colHoTen === null && $colCccd === null && $colSbd === null) {
                throw new \InvalidArgumentException('File thiếu cột CCCD / Họ tên / SBD: '.basename($path));
            }

            $out = [];
            $trongLienTiep = 0;
            for ($r = $headerRow + 1; $r <= $headerRow + 500; $r++) {
                $cccd = $colCccd !== null ? DocSheet::normalizeCccd(self::at($sheet, $r, $colCccd)) : '';
                $hoTen = self::docHoTenDong($sheet, $r, $colHoTen);
                $ngaySinh = self::docNgaySinhDong($sheet, $r, $colHoTen, $colNs);
                $sbd = $colSbd !== null ? DocSheet::normalizeSbd(self::at($sheet, $r, $colSbd)) : '';

                if ($cccd === '' && $hoTen === '' && $sbd === '') {
                    $trongLienTiep++;
                    if ($trongLienTiep >= 8 && $out !== []) {
                        break;
                    }

                    continue;
                }
                $trongLienTiep = 0;

                if ($cccd === '' && $hoTen === '' && ($sbd === '' || $sbd === '0')) {
                    continue;
                }

                self::ganKhoa($out, $cccd, $hoTen, $ngaySinh, $sbd, $ketQua);
            }

            return $out;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /** @param  array<string, string>  $out */
    private static function ganKhoa(
        array &$out,
        string $cccd,
        string $hoTen,
        string $ngaySinh,
        string $sbd,
        string $ketQua,
    ): void {
        if ($cccd !== '') {
            $out[$cccd] = $ketQua;
        }
        $kTen = DocSheet::khoaHoTenNgaySinh($hoTen, $ngaySinh);
        if ($kTen !== '') {
            $out[$kTen] = $ketQua;
        }
        if ($sbd !== '' && $sbd !== '0') {
            $out[$sbd] = $ketQua;
        }
    }

    /** @param  array<string, int>  $cols */
    private static function timCot(array $cols, array $need): ?int
    {
        foreach ($need as $n) {
            foreach ($cols as $label => $idx) {
                if (str_contains($label, $n)) {
                    return $idx;
                }
            }
        }

        return null;
    }

    private static function docHoTenDong(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        ?int $colHoTen,
    ): string {
        if ($colHoTen !== null) {
            foreach ([$colHoTen, $colHoTen + 1, $colHoTen - 1, $colHoTen + 2] as $c) {
                if ($c < 1) {
                    continue;
                }
                $v = mb_strtoupper(trim(self::at($sheet, $row, $c)));
                if ($v !== '' && preg_match('/\p{L}/u', $v) === 1 && ! DocSheet::giongNgaySinh($v)) {
                    return $v;
                }
            }
        }
        for ($c = 1; $c <= 28; $c++) {
            $v = mb_strtoupper(trim(self::at($sheet, $row, $c)));
            if ($v === '' || mb_strlen($v) < 4) {
                continue;
            }
            if (DocSheet::giongNgaySinh($v)) {
                continue;
            }
            $digits = DocSheet::normalizeCccd($v);
            if ($digits !== '' && strlen($digits) >= 9) {
                continue;
            }
            if (preg_match('/\p{L}/u', $v) === 1) {
                return $v;
            }
        }

        return '';
    }

    private static function docNgaySinhDong(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        ?int $colHoTen,
        ?int $colNs,
    ): string {
        if ($colNs !== null) {
            foreach ([$colNs, $colNs + 1, $colNs - 1] as $c) {
                if ($c < 1) {
                    continue;
                }
                $v = self::at($sheet, $row, $c);
                if (DocSheet::giongNgaySinh($v)) {
                    return $v;
                }
            }
        }
        if ($colHoTen !== null) {
            for ($c = $colHoTen + 1; $c <= $colHoTen + 22; $c++) {
                $v = self::at($sheet, $row, $c);
                if (DocSheet::giongNgaySinh($v)) {
                    return $v;
                }
            }
        }

        return '';
    }

    private static function at(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, ?int $col): string
    {
        if ($col === null) {
            return '';
        }
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return DocSheet::cellStr($sheet, $letter.$row);
    }
}