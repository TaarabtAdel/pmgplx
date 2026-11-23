<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;

/** File điểm LT / MP / CC có cột Kết quả — khóa SBD hoặc CCCD. */
final class DocDiemKetQua
{
    /**
     * @return array<string, string> sbd hoặc cccd => Đ|KĐ
     */
    public static function docTheoSbd(string $path): array
    {
        return self::doc($path, 'sbd');
    }

    /**
     * @return array<string, string> cccd => Đ|KĐ
     */
    public static function docTheoCccd(string $path): array
    {
        return self::doc($path, 'cccd');
    }

    /**
     * @return array<string, string>
     */
    private static function doc(string $path, string $khoa): array
    {
        $spreadsheet = IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headerRow = DocSheet::timHangTieuDe($sheet, ['kết quả', 'ket qua', 'sbd', 'số báo danh', 'cccd'], 30);
            if ($headerRow === null) {
                throw new \InvalidArgumentException('Không đọc được tiêu đề file điểm: '.basename($path));
            }
            $cols = DocSheet::mapCotTuHang($sheet, $headerRow);
            $colKq = self::timCot($cols, ['kết quả', 'ket qua']);
            if ($colKq === null) {
                throw new \InvalidArgumentException('File thiếu cột Kết quả: '.basename($path));
            }
            $colSbd = self::timCot($cols, ['sbd', 'số báo danh', 'so bao danh']);
            $colCccd = self::timCot($cols, ['cccd', 'số cccd', 'số cmt', 'cmt', 'số định danh', 'số cmt/cccd']);

            $out = [];
            for ($r = $headerRow + 1; $r <= $headerRow + 500; $r++) {
                $kq = DocSheet::chuanHoaKetQua(self::at($sheet, $r, $colKq));
                if ($kq === '') {
                    continue;
                }
                $keySbd = $colSbd !== null ? DocSheet::normalizeSbd(self::at($sheet, $r, $colSbd)) : '';
                $keyCccd = $colCccd !== null ? DocSheet::normalizeCccd(self::at($sheet, $r, $colCccd)) : '';

                if ($khoa === 'cccd') {
                    if ($keyCccd === '') {
                        continue;
                    }
                    $out[$keyCccd] = $kq;

                    continue;
                }

                if ($keySbd === '' && $keyCccd === '') {
                    continue;
                }
                if ($keySbd !== '') {
                    $out[$keySbd] = $kq;
                }
                // File LT/MP thường SBD = STT nội bộ (1,2,3…) — luôn thêm khóa CCCD/CMT khi có.
                if ($keyCccd !== '') {
                    $out[$keyCccd] = $kq;
                }
            }

            return $out;
        } finally {
            $spreadsheet->disconnectWorksheets();
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

    private static function at(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, ?int $col): string
    {
        if ($col === null) {
            return '';
        }
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return DocSheet::cellStr($sheet, $letter.$row);
    }
}