<?php

namespace App\Support\DaoTao;

use App\Support\QldtOto\KtmChiTiet\DocSheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Đọc file danh sách đạt — lấy CCCD (và họ tên nếu có), giữ thứ tự dòng. */
final class PhuLuc6DanhSachDatCccd
{
    /**
     * @return list<array{stt: int, cccd: string, ho_ten: string}>
     */
    public static function docFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headerRow = self::timHangTieuDeDanhSach($sheet);
            if ($headerRow === null) {
                throw new \InvalidArgumentException(
                    'Không tìm thấy hàng tiêu đề (cột Số định danh / CCCD và Họ tên) trong file.',
                );
            }

            $cols = DocSheet::mapCotTuHang($sheet, $headerRow, 30);
            $colCccd = self::timCot($cols, ['số định danh', 'so dinh danh', 'cccd', 'số cccd', 'số cmt', 'cmt', 'so cccd']);
            $colHoTen = self::timCot($cols, ['họ và tên', 'họ tên', 'họ va tên', 'ho va ten']);
            if ($colCccd === null) {
                throw new \InvalidArgumentException('File thiếu cột CCCD / Số CMT.');
            }

            $out = [];
            $trongLienTiep = 0;
            $stt = 0;
            for ($r = $headerRow + 1; $r <= $headerRow + 2000; $r++) {
                $cccd = DocSheet::normalizeCccd(self::at($sheet, $r, $colCccd));
                $hoTen = $colHoTen !== null ? trim(self::at($sheet, $r, $colHoTen)) : '';

                if (self::laHangKyXacNhan($sheet, $r)) {
                    break;
                }

                if ($cccd === '' && $hoTen === '') {
                    $trongLienTiep++;
                    if ($trongLienTiep >= 8 && $out !== []) {
                        break;
                    }

                    continue;
                }
                $trongLienTiep = 0;

                if ($cccd === '' || strlen($cccd) < 9) {
                    continue;
                }

                $stt++;
                $out[] = [
                    'stt' => $stt,
                    'cccd' => $cccd,
                    'ho_ten' => $hoTen,
                ];
            }

            if ($out === []) {
                throw new \InvalidArgumentException('Không có dòng CCCD hợp lệ trong file.');
            }

            return $out;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function timHangTieuDeDanhSach(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): ?int
    {
        for ($r = 1; $r <= 60; $r++) {
            $cols = DocSheet::mapCotTuHang($sheet, $r, 20);
            if ($cols === []) {
                continue;
            }
            if (self::timCot($cols, ['số định danh', 'so dinh danh', 'cccd', 'số cccd', 'số cmt', 'cmt']) === null) {
                continue;
            }
            if (self::timCot($cols, ['họ và tên', 'họ tên', 'họ va tên']) === null) {
                continue;
            }

            return $r;
        }

        return DocSheet::timHangTieuDe(
            $sheet,
            ['cccd', 'số cccd', 'số cmt', 'cmt', 'số định danh', 'họ và tên'],
            60,
        );
    }

    private static function laHangKyXacNhan(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row): bool
    {
        $chunk = '';
        foreach (range(1, 8) as $c) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $chunk .= ' '.mb_strtolower(DocSheet::cellStr($sheet, $letter.$row));
        }
        $chunk = trim($chunk);
        if ($chunk === '') {
            return false;
        }

        foreach (['ủy viên', 'ký tên', 'chủ tịch hội đồng', 'xác nhận'] as $kw) {
            if (str_contains($chunk, $kw)) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, int>  $cols */
    private static function timCot(array $cols, array $keys): ?int
    {
        foreach ($cols as $label => $index) {
            foreach ($keys as $key) {
                if (str_contains($label, $key)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private static function at(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $col): string
    {
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return DocSheet::cellStr($sheet, $letter.$row);
    }
}