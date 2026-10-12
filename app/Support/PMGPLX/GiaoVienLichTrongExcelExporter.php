<?php

namespace App\Support\PMGPLX;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GiaoVienLichTrongExcelExporter
{
    private const HEADER_COLOR = 'FF1565C0';

    /**
     * @param  array{
     *     ok: bool,
     *     groups: list<array<string, mixed>>,
     *     stats?: array<string, mixed>,
     *     resolved_range?: array<string, mixed>|null,
     *     notice?: string|null
     * }  $result
     * @param  array<string, mixed>  $input
     */
    public static function download(array $result, array $input): StreamedResponse
    {
        $spreadsheet = self::buildSpreadsheet($result, $input);
        $filename = 'do-trong-lich-gv-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $input
     */
    private static function buildSpreadsheet(array $result, array $input): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lich trong GV');

        $lastCol = 14;
        $lastLetter = self::columnLetter($lastCol);
        $rowCount = self::countDayRows($result['groups'] ?? []);

        $sheet->setCellValue('A1', 'Dò lịch trống giáo viên (cặp GV sáng · chiều)');
        $sheet->mergeCells('A1:'.$lastLetter.'1');
        $sheet->setCellValue('A2', self::criteriaLine($input, $result));
        $sheet->mergeCells('A2:'.$lastLetter.'2');
        $sheet->setCellValue('A3', self::metaLine($result, $rowCount));
        $sheet->mergeCells('A3:'.$lastLetter.'3');

        $headerRow = 5;
        $headers = [
            'STT',
            'Cặp',
            'Khóa',
            'Mã KH',
            'GV sáng',
            'Mã GV sáng',
            'GV chiều',
            'Mã GV chiều',
            'Ngày',
            'Bận (sáng)',
            'Nội dung A',
            'Bận (chiều)',
            'Nội dung B',
            'Thời gian rảnh (cặp)',
        ];
        foreach ($headers as $i => $label) {
            $sheet->setCellValue(self::columnLetter($i + 1).$headerRow, $label);
        }
        self::styleHeader($sheet, 'A'.$headerRow.':'.$lastLetter.$headerRow);

        $rowIndex = $headerRow + 1;
        $stt = 0;
        foreach ($result['groups'] ?? [] as $group) {
            foreach ($group['days'] ?? [] as $dayRow) {
                $stt++;
                $sheet->fromArray([
                    $stt,
                    (int) ($group['pair_line'] ?? 0),
                    (string) ($group['ten_kh'] ?? ''),
                    (string) ($group['ma_kh'] ?? ''),
                    (string) ($group['ten_gv_sang'] ?? ''),
                    (string) ($group['ma_gv_sang_norm'] ?? ''),
                    (string) ($group['ten_gv_chieu'] ?? ''),
                    (string) ($group['ma_gv_chieu_norm'] ?? ''),
                    (string) ($dayRow['ngay_label'] ?? ''),
                    self::formatBusy($dayRow['busy_a_segments'] ?? [], (string) ($dayRow['busy_a_label'] ?? '')),
                    self::formatNoiDung($dayRow['busy_a_segments'] ?? [], (string) ($dayRow['noi_dung_a_label'] ?? '')),
                    self::formatBusy($dayRow['busy_b_segments'] ?? [], (string) ($dayRow['busy_b_label'] ?? '')),
                    self::formatNoiDung($dayRow['busy_b_segments'] ?? [], (string) ($dayRow['noi_dung_b_label'] ?? '')),
                    (string) ($dayRow['free_label'] ?? ''),
                ], null, 'A'.$rowIndex);
                $rowIndex++;
            }
        }

        $lastDataRow = max($headerRow, $rowIndex - 1);
        $sheet->getStyle('A'.$headerRow.':'.$lastLetter.$lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle('A'.($headerRow + 1).':B'.$lastDataRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I'.($headerRow + 1).':I'.$lastDataRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $widths = [5, 5, 18, 12, 16, 10, 16, 10, 11, 14, 22, 14, 22, 24];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(self::columnLetter($i + 1))->setWidth($w);
        }

        $sheet->getRowDimension($headerRow)->setRowHeight(28);
        if ($lastDataRow >= $headerRow + 1) {
            $sheet->getStyle('A'.($headerRow + 1).':'.$lastLetter.$lastDataRow)->getFont()->setSize(9);
        }
        $sheet->getStyle('A'.$headerRow.':'.$lastLetter.$headerRow)->getFont()->setSize(9);

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setPrintArea('A1:'.$lastLetter.$lastDataRow);

        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);

        return $spreadsheet;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     */
    private static function countDayRows(array $groups): int
    {
        $n = 0;
        foreach ($groups as $group) {
            $n += count($group['days'] ?? []);
        }

        return $n;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $result
     */
    private static function criteriaLine(array $input, array $result): string
    {
        $parts = [];
        $rr = $result['resolved_range'] ?? null;
        if (is_array($rr) && ! empty($rr['tu_ngay']) && ! empty($rr['den_ngay'])) {
            $parts[] = 'Quét: '.\Carbon\Carbon::parse($rr['tu_ngay'])->format('d/m/Y')
                .' – '.\Carbon\Carbon::parse($rr['den_ngay'])->format('d/m/Y');
        }
        $parts[] = 'Khung ngày '.($input['gio_bat_dau'] ?? '06:00').'–'.($input['gio_ket_thuc'] ?? '22:00');
        $parts[] = LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($input['bo_qua_loai'] ?? []);
        $minFree = (int) ($input['min_free_phut'] ?? 0);
        if ($minFree > 0) {
            $parts[] = 'Rảnh ≥ '.$minFree.' phút';
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function metaLine(array $result, int $rowCount): string
    {
        $line = 'Xuất lúc: '.now()->format('d/m/Y H:i:s').' · '.number_format($rowCount).' dòng';
        $stats = $result['stats'] ?? [];
        if (! empty($stats['rows_truncated'])) {
            $line .= ' · Lưu ý: màn hình cắt ở '.number_format(GiaoVienLichTrongScanner::MAX_ROWS)
                .' dòng; Excel gồm đủ '.number_format($rowCount).' dòng theo tiêu chí';
        }
        if (! empty($result['notice'])) {
            $line .= ' · '.(string) $result['notice'];
        }

        return $line;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private static function formatBusy(array $segments, string $fallback): string
    {
        if ($segments !== []) {
            $lines = [];
            foreach ($segments as $seg) {
                $t = trim((string) ($seg['time'] ?? ''));
                if ($t !== '') {
                    $lines[] = $t;
                }
            }
            if ($lines !== []) {
                return implode("\n", $lines);
            }
        }

        $fallback = trim($fallback);

        return $fallback !== '' ? $fallback : '—';
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private static function formatNoiDung(array $segments, string $fallback): string
    {
        if ($segments !== []) {
            $lines = [];
            foreach ($segments as $seg) {
                $t = trim((string) ($seg['noi_dung'] ?? ''));
                if ($t !== '') {
                    $lines[] = $t;
                }
            }
            if ($lines !== []) {
                return implode("\n", $lines);
            }
        }

        $fallback = trim($fallback);

        return $fallback !== '' ? $fallback : '—';
    }

    private static function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_COLOR],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    private static function columnLetter(int $columnIndex): string
    {
        $label = '';
        $n = $columnIndex;
        while ($n > 0) {
            $n--;
            $label = chr(65 + ($n % 26)).$label;
            $n = intdiv($n, 26);
        }

        return $label;
    }
}
