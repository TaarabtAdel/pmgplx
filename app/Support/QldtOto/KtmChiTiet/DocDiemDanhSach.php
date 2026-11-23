<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;

/** File danh sách Đạt / Trượt (H, ĐT) — mọi dòng có SBD → Đ hoặc KĐ tùy loại file. */
final class DocDiemDanhSach
{
    /** @return array<string, string> sbd => Đ|KĐ */
    public static function docDat(string $path): array
    {
        return self::doc($path, 'Đ');
    }

    /** @return array<string, string> sbd => Đ|KĐ */
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
            $headerRow = DocSheet::timHangTieuDe($sheet, ['sbd', 'stt'], 35);
            if ($headerRow === null) {
                throw new \InvalidArgumentException('Không tìm hàng SBD trong: '.basename($path));
            }

            $colSbd = self::cotSbd($sheet, $headerRow);
            if ($colSbd === null) {
                throw new \InvalidArgumentException('Không tìm cột SBD trong: '.basename($path));
            }

            $out = [];
            for ($r = $headerRow + 1; $r <= $headerRow + 500; $r++) {
                $sbd = DocSheet::normalizeSbd(self::at($sheet, $r, $colSbd));
                if ($sbd === '') {
                    if ($out !== []) {
                        break;
                    }
                    continue;
                }
                $out[$sbd] = $ketQua;
            }

            return $out;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function cotSbd(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $headerRow): ?int
    {
        $cols = DocSheet::mapCotTuHang($sheet, $headerRow, 30);
        foreach ($cols as $label => $idx) {
            if (str_contains($label, 'sbd') || str_contains($label, 'báo danh')) {
                return $idx;
            }
        }
        for ($c = 1; $c <= 15; $c++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $h = mb_strtolower(DocSheet::cellStr($sheet, $letter.$headerRow));
            if ($h === 'sbd' || str_contains($h, 'báo danh')) {
                return $c;
            }
        }
        $valRow = DocSheet::cellStr($sheet, 'C'.($headerRow + 1));
        if ($valRow !== '' && ctype_digit(trim($valRow))) {
            return 3;
        }

        return null;
    }

    private static function at(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $col): string
    {
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return DocSheet::cellStr($sheet, $letter.$row);
    }
}