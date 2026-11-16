<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/** Xuất workbook lịch LT+TH + lịch TH từng GV (hạng C và B số tự động). */
final class LichHocLyThuyetThucHanhTungGiaoVienExporter
{
    private const TEMPLATE_GV_C = 'resources/templates/oto/lich-hoc-thuc-hanh-gv-hang-c.xlsx';

    private const TEMPLATE_GV_B01 = 'resources/templates/oto/lich-hoc-thuc-hanh-gv-hang-b01.xlsx';

    private const TEMPLATE_LT_C = 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-c.xlsx';

    private const TEMPLATE_LT_B01 = 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-b01.xlsx';

    /**
     * @param  array<string, Carbon|null>  $moc
     */
    public static function downloadWorkbook(
        string $maKh,
        string $tenKhoaTrenFile,
        array $moc,
        string $hang
    ): StreamedResponse {
        if (! self::hangDuocHoTro($hang)) {
            abort(422, 'Hạng này chưa hỗ trợ xuất lịch từng giáo viên.');
        }

        $gvGroups = LichHocThucHanhGiaoVienDanhSach::nhomTheoGiaoVien($maKh);
        if ($gvGroups === []) {
            abort(422, 'Khoá chưa có lịch giáo viên thực hành trên PMGPLX.');
        }

        $spreadsheet = self::buildSpreadsheet($tenKhoaTrenFile, $moc, $gvGroups, $hang);
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $maKh) ?: 'khoa';
        $filename = 'lich-lt-th-tung-gv-'.$safe.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, Carbon|null>  $moc
     */
    public static function downloadZip(
        string $maKh,
        string $tenKhoaTrenFile,
        array $moc,
        string $hang
    ): StreamedResponse {
        if (! self::hangDuocHoTro($hang)) {
            abort(422, 'Hạng này chưa hỗ trợ xuất lịch từng giáo viên.');
        }

        $gvGroups = LichHocThucHanhGiaoVienDanhSach::nhomTheoGiaoVien($maKh);
        if ($gvGroups === []) {
            abort(422, 'Khoá chưa có lịch giáo viên thực hành trên PMGPLX.');
        }

        $tmpdir = storage_path('app/tmp/lich-tung-gv-'.uniqid('', true));
        mkdir($tmpdir, 0775, true);

        $zipPath = $tmpdir.'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Không tạo được file ZIP.');
        }

        foreach ($gvGroups as $i => $gv) {
            $ss = new Spreadsheet;
            $ss->removeSheetByIndex(0);
            $thWb = self::taoWorkbookMotSheetTh($tenKhoaTrenFile, $gv, $i + 1, $hang);
            $ss->addExternalSheet($thWb->getActiveSheet());
            $thWb->disconnectWorksheets();
            $ltWb = self::taoWorkbookSheetLt($tenKhoaTrenFile, $moc, $hang);
            $ss->addExternalSheet($ltWb->getActiveSheet());
            $ltWb->disconnectWorksheets();
            $safeName = self::safeFileStem($gv['ten_gv'] ?? $gv['ma_gv']);
            $file = $tmpdir.'/'.$safeName.'.xlsx';
            (new Xlsx($ss))->save($file);
            $zip->addFile($file, $safeName.'.xlsx');
            $ss->disconnectWorksheets();
        }

        $zip->close();

        $safeMa = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $maKh) ?: 'khoa';
        $downloadName = 'lich-tung-gv-'.$safeMa.'-'.now()->format('Ymd-His').'.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public static function hangDuocHoTro(string $hang): bool
    {
        return in_array($hang, [
            LichHocLyThuyetThucHanhLichNgay::HANG_C,
            LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG,
        ], true);
    }

    /**
     * @param  list<array<string, mixed>>  $gvGroups
     */
    private static function buildSpreadsheet(
        string $tenKhoaTrenFile,
        array $moc,
        array $gvGroups,
        string $hang
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($gvGroups as $i => $gv) {
            $wb = self::taoWorkbookMotSheetTh($tenKhoaTrenFile, $gv, $i + 1, $hang);
            $spreadsheet->addExternalSheet($wb->getActiveSheet());
            $wb->disconnectWorksheets();
        }

        $ltWb = self::taoWorkbookSheetLt($tenKhoaTrenFile, $moc, $hang);
        $spreadsheet->addExternalSheet($ltWb->getActiveSheet());
        $ltWb->disconnectWorksheets();

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $gv
     */
    private static function taoWorkbookMotSheetTh(string $tenKhoaTrenFile, array $gv, int $stt, string $hang): Spreadsheet
    {
        $path = base_path(
            $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG
                ? self::TEMPLATE_GV_B01
                : self::TEMPLATE_GV_C
        );
        if (! is_file($path)) {
            throw new \RuntimeException('Thiếu file mẫu lịch thực hành GV.');
        }

        $protoSs = IOFactory::load($path);
        $out = new Spreadsheet;
        $out->removeSheetByIndex(0);
        $out->addExternalSheet($protoSs->getActiveSheet());
        $sheet = $out->getActiveSheet();

        $tenGv = trim((string) ($gv['ten_gv'] ?? ''));
        $bien = trim((string) ($gv['bien_so'] ?? ''));
        $sheet->setCellValue('A3', self::tieuDeThucHanh($tenKhoaTrenFile, $bien, $hang));

        if ($hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG) {
            $sheet->setCellValue('D4', mb_strtoupper($tenGv));
        } else {
            $soHv = max(8, (int) ($gv['so_hv'] ?? 0));
            $sheet->setCellValue('E4', mb_strtoupper($tenGv));
            $sheet->setCellValue('F2', $soHv.'HV');
        }

        LichHocThucHanhGiaoVienDanhSach::ghiBuoiLenSheet($sheet, $gv['buoi'] ?? [], $hang);
        $sheet->setTitle(self::sheetTitleTh($stt, $hang));

        $protoSs->disconnectWorksheets();

        return $out;
    }

    /**
     * @param  array<string, Carbon|null>  $moc
     */
    private static function taoWorkbookSheetLt(string $tenKhoaTrenFile, array $moc, string $hang): Spreadsheet
    {
        $b01 = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG;
        $path = base_path($b01 ? self::TEMPLATE_LT_B01 : self::TEMPLATE_LT_C);
        if (! is_file($path)) {
            throw new \RuntimeException('Thiếu file mẫu lịch LT+TH.');
        }

        $protoSs = IOFactory::load($path);
        if ($b01) {
            $sheetLt = LichHocLyThuyetThucHanhSheet::chonSheet($protoSs, 'TỰU ĐỘNG');
        } else {
            $sheetLt = $protoSs->getActiveSheet();
        }

        $out = new Spreadsheet;
        $out->removeSheetByIndex(0);
        $out->addExternalSheet($sheetLt);
        $sheet = $out->getActiveSheet();
        $sheet->setCellValue('A3', 'LỊCH HỌC LÝ THUYẾT, THỰC HÀNH KHÓA '.mb_strtoupper($tenKhoaTrenFile));
        $rowKt = $b01 ? 28 : 29;
        LichHocLyThuyetThucHanhFillSheet::dienMoc(
            $sheet,
            $moc,
            rowKiemTra: $rowKt,
            hang: $b01 ? LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG : null
        );
        $sheet->setTitle($b01 ? 'LỊCH TỰ ĐỘNG' : 'LỊCH');
        $protoSs->disconnectWorksheets();

        return $out;
    }

    private static function tieuDeThucHanh(string $tenKhoaTrenFile, string $bienSo, string $hang): string
    {
        $ten = mb_strtoupper(trim($tenKhoaTrenFile));
        $bien = trim($bienSo);
        $xe = $bien !== '' ? ' XE TẬP LÁI '.$bien : '';

        if ($hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG) {
            return 'LỊCH HỌC THỰC HÀNH B SỐ TỰ ĐỘNG '.$ten.$xe;
        }

        return 'LỊCH HỌC THỰC HÀNH LÁI XE '.$ten.$xe;
    }

    private static function sheetTitleTh(int $stt, string $hang): string
    {
        $prefix = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG
            ? 'THUC HANH B11'
            : 'LICHTHUCHANH';
        $name = $prefix.' '.$stt;

        return mb_strlen($name) > 31 ? mb_substr($name, 0, 31) : $name;
    }

    private static function safeFileStem(string $ten): string
    {
        $ten = trim($ten);
        $s = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $ten) ?: 'giao-vien';

        return mb_substr($s, 0, 60);
    }
}