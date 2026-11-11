<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Xuất lịch LT+TH hạng B sàn — sheet «LỊCH SỐ SÀN» (mẫu BK56.xlsx). */
final class LichHocLyThuyetThucHanhHangBSanExporter
{
    private const TEMPLATE = 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-b-san.xlsx';

    private const SHEET_HINT = 'SỐ SÀN';

    /**
     * @param  array<string, \Carbon\Carbon|null>  $moc
     */
    public static function download(string $tenKhoaTrenFile, array $moc, string $filePrefix = 'lich-lt-th-khoa'): StreamedResponse
    {
        $path = base_path(self::TEMPLATE);
        if (! is_file($path)) {
            abort(500, 'Thiếu file mẫu lịch hạng B sàn.');
        }

        $ss = IOFactory::load($path);
        $sheet = null;
        foreach ($ss->getWorksheetIterator() as $ws) {
            if (mb_stripos($ws->getTitle(), self::SHEET_HINT) !== false) {
                $sheet = $ws;
                break;
            }
        }
        $sheet ??= $ss->getActiveSheet();
        $ss->setActiveSheetIndex($ss->getIndex($sheet));

        $sheet->setCellValue('A3', 'LỊCH HỌC LÝ THUYẾT, THỰC HÀNH KHÓA '.mb_strtoupper($tenKhoaTrenFile));
        LichHocLyThuyetThucHanhFillSheet::dienMoc($sheet, $moc, rowKiemTra: 29);

        $filename = $filePrefix.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($ss): void {
            (new Xlsx($ss))->save('php://output');
            $ss->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
