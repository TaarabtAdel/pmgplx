<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Danh sách học viên kèm QĐ tổ chức kiểm tra kết thúc môn — một sheet, gộp nhiều khoá. */
final class KemQdToChucKtmDanhSachHocVienExcelExporter
{
    public const TEMPLATE = 'templates/oto/kem-qd-to-chuc-kiem-tra-ket-thuc-mon.goc.xlsx';

    private const TIEU_DE_CELL = 'B1';

    private const DONG_QD_CELL = 'A2';

    private const DATA_START_ROW = 4;

    /** Dòng dữ liệu mẫu trong file gốc (hàng 4–8, để copy style). */
    private const TEMPLATE_DATA_ROWS = 5;

    private const FOOTER_ROW_TEMPLATE = 9;

    /**
     * @param  array{tieu_de: string, dong_quyet_dinh: string}  $meta
     * @param  list<array<string, mixed>>  $hocVienRows
     */
    public function download(array $meta, array $hocVienRows, string $downloadName): BinaryFileResponse
    {
        $tmp = storage_path('app/tmp/kem-qd-ktm-ds-hv-'.uniqid('', true).'.xlsx');
        $this->generate($meta, $hocVienRows, $tmp);

        return response()->download($tmp, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * @param  array{tieu_de: string, dong_quyet_dinh: string}  $meta
     * @param  list<array<string, mixed>>  $hocVienRows
     */
    public function generate(array $meta, array $hocVienRows, string $outputPath): string
    {
        if ($hocVienRows === []) {
            throw new \RuntimeException('Không còn học viên sau khi loại trừ.');
        }

        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có file mẫu: '.self::TEMPLATE);
        }

        @mkdir(dirname($outputPath), 0755, true);

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách');

        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if ($spreadsheet->getSheet($i)->getTitle() !== $sheet->getTitle()) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }

        self::fillSheet($sheet, $meta, $hocVienRows);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file Excel danh sách học viên.');
        }

        return $outputPath;
    }

    /**
     * Gộp học viên các khoá; chỉ giữ mã đăng ký được chọn (checkbox).
     *
     * @param  list<KhoaHoc>  $khoas
     * @param  array<string, list<string>>  $chonMaDkTheoKhoa  ma_kh => list ma_dk
     * @return list<array<string, mixed>>
     */
    public static function gopHocVienDaChon(array $khoas, array $chonMaDkTheoKhoa): array
    {
        $all = [];
        foreach ($khoas as $khoa) {
            if (! $khoa instanceof KhoaHoc) {
                continue;
            }
            $maKh = (string) $khoa->MaKH;
            $chon = array_flip($chonMaDkTheoKhoa[$maKh] ?? []);
            $rows = DanhSachHocSinhLaiXeHocVien::dongXuat($maKh);
            foreach ($rows as $row) {
                $maDk = (string) ($row['ma_dk'] ?? '');
                if ($maDk === '' || ! isset($chon[$maDk])) {
                    continue;
                }
                $all[] = $row;
            }
        }

        foreach ($all as $i => &$row) {
            $row['stt'] = $i + 1;
        }
        unset($row);

        return $all;
    }

    /**
     * @param  array{tieu_de: string, dong_quyet_dinh: string}  $meta
     * @param  list<array<string, mixed>>  $hocVienRows
     */
    private static function fillSheet(Worksheet $sheet, array $meta, array $hocVienRows): void
    {
        $sheet->setCellValue(self::TIEU_DE_CELL, $meta['tieu_de']);
        $sheet->setCellValue(self::DONG_QD_CELL, $meta['dong_quyet_dinh']);

        $count = count($hocVienRows);
        $extra = max(0, $count - self::TEMPLATE_DATA_ROWS);
        if ($extra > 0) {
            $sheet->insertNewRowBefore(self::FOOTER_ROW_TEMPLATE, $extra);
        }

        $footerRow = self::FOOTER_ROW_TEMPLATE + $extra;

        for ($i = 0; $i < $count; $i++) {
            $row = self::DATA_START_ROW + $i;
            $hv = $hocVienRows[$i];
            if ($row > self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS - 1) {
                self::copyRowStyle($sheet, self::DATA_START_ROW, $row);
            }

            $hoDem = (string) ($hv['ho_dem'] ?? '');
            $tenRieng = (string) ($hv['ten_rieng'] ?? '');
            if ($hoDem === '' && $tenRieng === '') {
                $tach = DanhSachKemQuyetDinhHoanThanhExcelExporter::tachHoTen((string) ($hv['ho_ten'] ?? ''));
                $hoDem = (string) $tach['ho_dem'];
                $tenRieng = (string) $tach['ten'];
            }

            $sheet->setCellValue('A'.$row, (int) ($hv['stt'] ?? $i + 1));
            $sheet->setCellValue('B'.$row, $hoDem);
            $sheet->setCellValue('C'.$row, $tenRieng);
            $sheet->setCellValue('D'.$row, (string) ($hv['ngay_sinh'] ?? ''));
            $sheet->setCellValueExplicit('E'.$row, (string) ($hv['so_cmnd'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue('F'.$row, (string) ($hv['dia_chi_thuong_tru'] ?? ''));
            $sheet->setCellValue('G'.$row, (string) ($hv['ghi_chu'] ?? ''));
        }

        if ($count < self::TEMPLATE_DATA_ROWS) {
            for ($row = self::DATA_START_ROW + $count; $row < self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS; $row++) {
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
                    $sheet->setCellValue($col.$row, null);
                }
            }
        }

        $sheet->setCellValue('B'.$footerRow, '(Danh sách này bao gồm '.$count.' học viên)');
    }

    public static function tieuDeTuForm(string $hangForm, string $khoaSoForm): string
    {
        $khoaSo = trim($khoaSoForm);
        $hangForm = trim($hangForm);
        $hangBase = $hangForm;
        if (str_contains($hangForm, ',')) {
            $first = trim(explode(',', $hangForm)[0] ?? $hangForm);
            $hangBase = preg_replace('/\s*Khóa\s+\S+\s*$/u', '', $first) ?? $first;
            $hangBase = trim($hangBase);
        }
        $hang = mb_strtoupper(preg_replace('/\s+/u', ' ', str_replace(['(', ')'], [' ', ' '], $hangBase)) ?? $hangBase, 'UTF-8');

        return 'DANH SÁCH HỌC VIÊN HỌC LÁI XE Ô TÔ HẠNG '.$hang
            .($khoaSo !== '' ? ' KHÓA '.$khoaSo : '');
    }

    public static function dongQuyetDinhTuForm(
        string $soQd,
        CarbonInterface $ngay,
        string $hangForm,
        string $khoaSoForm,
    ): string {
        $soQdFull = trim($soQd).QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD;
        $hang = trim($hangForm);
        $khoa = trim($khoaSoForm);
        $coKhoaTrongHang = (bool) preg_match('/Khóa\s+\S+/u', $hang);
        $phanKhoa = ($khoa !== '' && ! $coKhoaTrongHang) ? ' Khóa '.$khoa : ($khoa !== '' && str_contains($hang, ',') ? '' : ($khoa !== '' ? ' Khóa '.$khoa : ''));

        return '(Kèm theo Quyết định số: '.$soQdFull.' ngày '.$ngay->format('d/m/Y')
            .' của Trung tâm GDNN Mạnh Linh về việc Tổ chức kiểm tra kết thúc môn học lý thuyết, thực hành lái xe ô tô hạng '
            .$hang.$phanKhoa.')';
    }

    private static function copyRowStyle(Worksheet $sheet, int $fromRow, int $toRow): void
    {
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->duplicateStyle($sheet->getStyle($col.$fromRow), $col.$toRow);
        }
        $sheet->getRowDimension($toRow)->setRowHeight($sheet->getRowDimension($fromRow)->getRowHeight());
    }
}