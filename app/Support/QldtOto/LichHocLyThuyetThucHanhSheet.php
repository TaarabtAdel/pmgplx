<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Chọn đúng sheet lịch LT+TH trong workbook mẫu (nhiều sheet, active thường là bản «lịch» thử). */
final class LichHocLyThuyetThucHanhSheet
{
    public static function chonSheet(Spreadsheet $ss, string $titleHint): Worksheet
    {
        foreach ($ss->getWorksheetIterator() as $ws) {
            if ($titleHint !== '' && mb_stripos($ws->getTitle(), $titleHint) !== false) {
                return $ws;
            }
        }

        foreach ($ss->getWorksheetIterator() as $ws) {
            $b4 = mb_strtoupper(trim((string) $ws->getCell('B4')->getValue()));
            if (str_contains($b4, 'NỘI DUNG BÀI HỌC')) {
                return $ws;
            }
        }

        return $ss->getActiveSheet();
    }
}
