<?php

namespace App\Support\QldtOto;

use App\Models\QldtOto\LichDuKienThucHanh;
use App\Models\QldtOto\LichDuKienThucHanhBuoi;
use App\Support\PMGPLX\LichExcelCellStyle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất lịch dự kiến đã sinh theo cấu trúc file nhập PMGPLX
 * (/pmgplx/lich/nhap-tu-file): mỗi giáo viên 7 cột
 * (Thứ, Thời Gian, Số Giờ, Nội dung, Chi tiết, Bắt đầu, Kết thúc).
 */
final class LichDuKienThucHanhPmgplxExcelExporter
{
    private const COLS_PER_TEACHER = 7;

    private const COL_THU = 0;

    private const COL_NGAY = 1;

    private const COL_SO_GIO = 2;

    private const COL_NOI_DUNG = 3;

    private const COL_CHI_TIET = 4;

    private const COL_BAT_DAU = 5;

    private const COL_KET_THUC = 6;

    private const COL_LABELS = ['Thứ', 'Thời Gian', 'Số Giờ', 'Nội dung', 'Chi tiết', 'Bắt đầu', 'Kết thúc'];

    /** Màu ô giống màn xem trước nhập lịch PMGPLX (ARGB, không #). */
    private const FILL_BY_CLASS = [
        'cell-phuc-tap' => 'FF00E5E5',
        'cell-ban-dem' => 'FFC77DFF',
        'cell-co-tai' => 'FFFF4D4D',
        'cell-doc-qc' => 'FF00C853',
        'cell-tu-dong' => 'FF9EB6C7',
        'cell-cao-toc' => 'FFFFE566',
        'off-day' => 'FFFFF3CD',
    ];

    /**
     * @param  LichDuKienThucHanh|\App\Models\QldtOto\LichChinhThuc  $lich
     * @param  array{cot: list<array<string, mixed>>, dong: list<array<string, mixed>>, so_buoi: int}  $bangLich
     */
    public static function download($lich, array $bangLich, string $filenamePrefix = 'lich-du-kien-th'): StreamedResponse
    {
        $cot = collect($bangLich['cot'])->filter(fn (array $c): bool => trim((string) ($c['ma_gv'] ?? '')) !== '')->values()->all();
        if ($cot === []) {
            throw new \RuntimeException('Không có giáo viên trong lịch đã sinh.');
        }

        $maKh = trim((string) ($lich->MaKH ?? $lich->TenKhoa));
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lich TH');

        $header = [];
        $body = [];

        foreach ($bangLich['dong'] as $dong) {
            $body[] = [];
        }

        foreach ($cot as $t => $col) {
            $base = $t * self::COLS_PER_TEACHER;
            for ($i = 0; $i < self::COLS_PER_TEACHER; $i++) {
                if ($i === self::COL_NOI_DUNG || $i === self::COL_CHI_TIET) {
                    continue;
                }
                $header[$base + $i] = self::COL_LABELS[$i];
            }
            $header[$base + self::COL_NOI_DUNG] = self::headerNoiDung((string) $col['ten_gv'], (string) $col['xe']);
            $header[$base + self::COL_CHI_TIET] = self::headerChiTiet((string) $col['ma_gv'], $maKh);

            foreach ($bangLich['dong'] as $rowIndex => $dong) {
                $row =& $body[$rowIndex];
                for ($i = 0; $i < self::COLS_PER_TEACHER; $i++) {
                    if (! array_key_exists($base + $i, $row)) {
                        $row[$base + $i] = '';
                    }
                }
                $row[$base + self::COL_THU] = $dong['thu'];
                $row[$base + self::COL_NGAY] = $dong['ngay'];

                if ($dong['nghi']) {
                    continue;
                }

                /** @var LichDuKienThucHanhBuoi|null $buoi */
                $buoi = $dong['o'][$col['key']] ?? null;
                if (! $buoi) {
                    continue;
                }

                $row[$base + self::COL_SO_GIO] = self::formatSoGio($buoi->SoGio);
                $row[$base + self::COL_NOI_DUNG] = trim($buoi->NgaySo.'. '.$buoi->NoiDung, '. ');
                $row[$base + self::COL_CHI_TIET] = '';
                $row[$base + self::COL_BAT_DAU] = self::formatGio($buoi->GioBatDau);
                $row[$base + self::COL_KET_THUC] = self::formatGio($buoi->GioKetThuc);
            }
            unset($row);
        }

        ksort($header);
        $maxCol = ($t + 1) * self::COLS_PER_TEACHER;
        $normalizedHeader = self::padRow($header, $maxCol);
        $sheet->fromArray([$normalizedHeader], null, 'A1');

        $rowNum = 2;
        foreach ($body as $row) {
            ksort($row);
            $sheet->fromArray([self::padRow($row, $maxCol)], null, 'A'.$rowNum);
            $rowNum++;
        }

        self::applyPreviewColors($sheet, $bangLich, $cot, $maxCol);
        self::autoSize($sheet, $maxCol);

        $safeName = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $lich->TenKhoa) ?: 'lich';
        $filename = $filenamePrefix.'-'.$safeName.'-'.$lich->Id.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private static function headerNoiDung(string $tenGv, string $bienSo): string
    {
        $tenGv = trim($tenGv);
        $bienSo = trim($bienSo);
        if ($tenGv === '') {
            return '';
        }

        return $bienSo !== '' ? $tenGv.' - '.$bienSo : $tenGv;
    }

    /**
     * @param  list<array<string, mixed>>  $cot
     * @param  array{cot: list<array<string, mixed>>, dong: list<array<string, mixed>>, so_buoi: int}  $bangLich
     */
    private static function applyPreviewColors(Worksheet $sheet, array $bangLich, array $cot, int $maxCol): void
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($maxCol);
        $rowNum = 2;
        foreach ($bangLich['dong'] as $dong) {
            if ($dong['nghi']) {
                self::fillRow($sheet, 'A'.$rowNum.':'.$lastColLetter.$rowNum, self::FILL_BY_CLASS['off-day']);

                $rowNum++;

                continue;
            }

            foreach ($cot as $t => $col) {
                $base = $t * self::COLS_PER_TEACHER;
                /** @var LichDuKienThucHanhBuoi|null $buoi */
                $buoi = $dong['o'][$col['key']] ?? null;
                if (! $buoi) {
                    continue;
                }
                $noiDung = trim($buoi->NgaySo.'. '.$buoi->NoiDung, '. ');
                $class = LichExcelCellStyle::classFor($noiDung);
                if ($class === '' || ! isset(self::FILL_BY_CLASS[$class])) {
                    continue;
                }
                $fill = self::FILL_BY_CLASS[$class];
                $colNoiDung = Coordinate::stringFromColumnIndex($base + self::COL_NOI_DUNG + 1);
                $colChiTiet = Coordinate::stringFromColumnIndex($base + self::COL_CHI_TIET + 1);
                self::fillRow($sheet, $colNoiDung.$rowNum, $fill);
                self::fillRow($sheet, $colChiTiet.$rowNum, $fill);
            }

            $rowNum++;
        }
    }

    private static function fillRow(Worksheet $sheet, string $range, string $argb): void
    {
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($argb);
    }

    private static function headerChiTiet(string $maGv, string $maKh): string
    {
        $maGv = trim($maGv);
        $maKh = trim($maKh);
        if ($maGv === '' && $maKh === '') {
            return '';
        }
        if ($maKh === '') {
            return $maGv;
        }

        return $maGv.'-'.$maKh;
    }

    private static function formatGio(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{1,2}:\d{2}/', $value, $m)) {
            return substr($m[0], 0, 5);
        }

        return $value;
    }

    private static function formatSoGio(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $n = (float) $value;

        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /** @param  array<int, string>  $row */
    private static function padRow(array $row, int $length): array
    {
        $out = [];
        for ($i = 0; $i < $length; $i++) {
            $out[] = $row[$i] ?? '';
        }

        return $out;
    }

    private static function autoSize(Worksheet $sheet, int $colCount): void
    {
        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }
}