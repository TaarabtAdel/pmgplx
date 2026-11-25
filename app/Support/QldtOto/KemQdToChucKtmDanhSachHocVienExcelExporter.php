<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Danh sách học viên kèm QĐ tổ chức kiểm tra kết thúc môn — mỗi khoá một sheet. */
final class KemQdToChucKtmDanhSachHocVienExcelExporter
{
    public const TEMPLATE = 'templates/oto/kem-qd-to-chuc-kiem-tra-ket-thuc-mon.goc.xlsx';

    private const TIEU_DE_CELL = 'B1';

    private const DONG_QD_CELL = 'A2';

    private const DATA_START_ROW = 4;

    /** Dòng dữ liệu mẫu trong file gốc (để copy style). */
    private const TEMPLATE_DATA_ROWS = 1;

    private const FOOTER_ROW_TEMPLATE = 5;

    /**
     * @param  list<KhoaHoc>  $khoas
     * @param  array{so_qd: string, ngay_quyet_dinh: CarbonInterface}  $qd
     */
    public function download(array $khoas, array $qd, string $downloadName): BinaryFileResponse
    {
        $tmp = storage_path('app/tmp/kem-qd-ktm-ds-hv-'.uniqid('', true).'.xlsx');
        $this->generate($khoas, $qd, $tmp);

        return response()->download($tmp, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * @param  list<KhoaHoc>  $khoas
     * @param  array{so_qd: string, ngay_quyet_dinh: CarbonInterface}  $qd
     */
    public function generate(array $khoas, array $qd, string $outputPath): string
    {
        if ($khoas === []) {
            throw new \RuntimeException('Chưa chọn khoá học.');
        }

        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có file mẫu: '.self::TEMPLATE);
        }

        @mkdir(dirname($outputPath), 0755, true);

        /** @var Spreadsheet|null $spreadsheet */
        $spreadsheet = null;

        foreach ($khoas as $i => $khoa) {
            if (! $khoa instanceof KhoaHoc) {
                continue;
            }
            $maKh = (string) $khoa->MaKH;
            $hocVien = DanhSachHocSinhLaiXeHocVien::dongXuat($maKh);
            if ($hocVien === []) {
                throw new \RuntimeException('Khoá '.$maKh.' không có học viên trên PMGPLX.');
            }

            $tuKhoa = BaoCaoDangKyKhoaHoc::tuKhoaHoc($khoa);
            $hang = BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null);
            $meta = [
                'tieu_de' => self::tieuDe($hang, $tuKhoa['khoa_so']),
                'dong_quyet_dinh' => self::dongQuyetDinh($qd['so_qd'], $qd['ngay_quyet_dinh'], $hang, $tuKhoa['khoa_so']),
            ];

            $wb = IOFactory::load($template);
            $sheet = $wb->getActiveSheet();
            $sheet->setTitle(self::tenSheet($khoa, $i));

            if ($spreadsheet === null) {
                $spreadsheet = $wb;
            } else {
                $spreadsheet->addExternalSheet($sheet);
                $wb->disconnectWorksheets();
            }

            self::fillSheet($sheet, $meta, $hocVien);
        }

        if (! $spreadsheet instanceof Spreadsheet) {
            throw new \RuntimeException('Không tạo được workbook.');
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file Excel danh sách học viên.');
        }

        return $outputPath;
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
            if ($row > self::DATA_START_ROW + self::TEMPLATE_DATA_ROWS - 1 + $extra) {
                self::copyRowStyle($sheet, self::DATA_START_ROW, $row);
            }

            $tach = DanhSachKemQuyetDinhHoanThanhExcelExporter::tachHoTen((string) ($hv['ho_ten'] ?? ''));

            $sheet->setCellValue('A'.$row, (int) ($hv['stt'] ?? $i + 1));
            $sheet->setCellValue('B'.$row, $tach['ho_dem']);
            $sheet->setCellValue('C'.$row, $tach['ten']);
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

    public static function tieuDe(string $hangLabel, string $khoaSo): string
    {
        $hang = mb_strtoupper(trim($hangLabel), 'UTF-8');
        $hang = preg_replace('/\s+/u', ' ', trim(str_replace(['(', ')'], [' ', ''], $hang))) ?? $hang;
        $khoaSo = trim($khoaSo);

        return 'DANH SÁCH HỌC VIÊN HỌC LÁI XE Ô TÔ HẠNG '.$hang
            .($khoaSo !== '' ? ' KHÓA '.$khoaSo : '');
    }

    public static function dongQuyetDinh(
        string $soQd,
        CarbonInterface $ngay,
        string $hangLabel,
        string $khoaSo,
    ): string {
        $soQdFull = trim($soQd).QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD;
        $hang = trim($hangLabel);
        $khoa = trim($khoaSo);

        return '(Kèm theo Quyết định số: '.$soQdFull.' ngày '.$ngay->format('d/m/Y')
            .' của Trung tâm GDNN Mạnh Linh về việc Tổ chức kiểm tra kết thúc môn học lý thuyết, thực hành lái xe ô tô hạng '
            .$hang.($khoa !== '' ? ' Khóa '.$khoa : '').')';
    }

    private static function tenSheet(KhoaHoc $khoa, int $index): string
    {
        $ma = preg_replace('/[\\\\\\/*?:\\[\\]]/', '-', (string) $khoa->MaKH) ?: 'khoa';
        $ten = trim((string) ($khoa->TenKH ?? ''));
        $so = BaoCaoDangKyKhoaHoc::soKhoaTuTen($ten) ?? '';
        $base = $so !== '' ? 'Khoa '.$so : $ma;
        $title = mb_substr($base, 0, 28);
        if ($index > 0 && mb_strlen($base) > 28) {
            $title = mb_substr($ma, 0, 24).'-'.$index;
        }

        return $title !== '' ? $title : 'Sheet'.($index + 1);
    }

    private static function copyRowStyle(Worksheet $sheet, int $fromRow, int $toRow): void
    {
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->duplicateStyle($sheet->getStyle($col.$fromRow), $col.$toRow);
        }
        $sheet->getRowDimension($toRow)->setRowHeight($sheet->getRowDimension($fromRow)->getRowHeight());
    }
}