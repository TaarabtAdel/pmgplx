<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Xuất lịch LT+TH hạng B tự động (B01) — sheet «LỊCH TỰ ĐỘNG». */
final class LichHocLyThuyetThucHanhHangB01Exporter
{
    private const TEMPLATE = 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-b01.xlsx';

    private const SHEET_HINT = 'TỰU ĐỘNG';

    /**
     * @param  array<string, \Carbon\Carbon|null>  $moc
     */
    public static function download(string $tenKhoaTrenFile, array $moc, string $filePrefix = 'lich-lt-th-khoa'): StreamedResponse
    {
        $path = base_path(self::TEMPLATE);
        if (! is_file($path)) {
            abort(500, 'Thiếu file mẫu lịch hạng B tự động.');
        }

        $ss = IOFactory::load($path);
        $sheet = LichHocLyThuyetThucHanhSheet::chonSheet($ss, self::SHEET_HINT);
        $ss->setActiveSheetIndex($ss->getIndex($sheet));

        $sheet->setCellValue('A3', 'LỊCH HỌC LÝ THUYẾT, THỰC HÀNH KHÓA '.mb_strtoupper($tenKhoaTrenFile));
        LichHocLyThuyetThucHanhFillSheet::dienMoc(
            $sheet,
            $moc,
            rowKiemTra: 28,
            hang: LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG
        );

        $filename = $filePrefix.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($ss): void {
            (new Xlsx($ss))->save('php://output');
            $ss->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
