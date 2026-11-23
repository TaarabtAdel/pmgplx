<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Quy ước: **1 mẫu = 1 file .xls = 1 sheet** (định dạng giống sheet trong File chuẩn).
 * Nguồn tách: `file-chuan-ktm-mau.xls` (copy từ File chuẩn). Cập nhật mẫu: `php artisan qldt-oto:tach-mau-chuan`.
 */
final class KtmTachMauChuan
{
    private const NGUON = 'templates/oto/ktm-bao-cao/file-chuan-ktm-mau.xls';

    /** @var array<string, string> tên sheet gốc → tên file mẫu (một sheet) */
    public const SHEET_RA_FILE = [
        'BC CHUNG' => 'bao-cao-chung-ktm.xls',
        'BBTH' => 'bao-cao-tong-hop-bbth-ktm.xls',
        'bb họp' => 'bien-ban-hop-ktm.xls',
    ];

    public static function tachCaBa(?string $nguonPath = null): void
    {
        $nguon = $nguonPath ?? resource_path(self::NGUON);
        if (! is_file($nguon)) {
            throw new \RuntimeException('Không tìm thấy file chuẩn: '.$nguon);
        }

        $dir = dirname($nguon);
        foreach (self::SHEET_RA_FILE as $tenSheet => $tenFile) {
            self::tachMotSheet($nguon, $tenSheet, $dir.'/'.$tenFile);
        }
    }

    public static function tachMotSheet(string $nguon, string $tenSheet, string $dich): void
    {
        $reader = IOFactory::createReader('Xls');
        $reader->setIncludeCharts(false);
        $reader->setLoadSheetsOnly([$tenSheet]);
        $spreadsheet = $reader->load($nguon);

        $sheet = $spreadsheet->getSheetByName($tenSheet);
        if ($sheet === null) {
            throw new \RuntimeException('Sheet «'.$tenSheet.'» không có trong '.$nguon);
        }

        while ($spreadsheet->getSheetCount() > 1) {
            $idx = $spreadsheet->getIndex($sheet) === 0 ? 1 : 0;
            $spreadsheet->removeSheetByIndex($idx);
        }

        @mkdir(dirname($dich), 0755, true);
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($dich);
        if ($spreadsheet->getSheetCount() !== 1) {
            throw new \RuntimeException('Sau tách, file phải còn đúng 1 sheet: '.$dich);
        }
        $spreadsheet->disconnectWorksheets();
    }
}