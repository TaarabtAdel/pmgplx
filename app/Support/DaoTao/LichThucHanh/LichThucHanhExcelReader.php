<?php

namespace App\Support\DaoTao\LichThucHanh;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Đọc file Excel lịch TH (7 cột / giáo viên) — dùng cho mọi khóa, không gắn một mã khóa cụ thể. */
final class LichThucHanhExcelReader
{
    public const COLS_PER_BLOCK = 7;

    public function load(string $path): Spreadsheet
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException("Không đọc được file: {$path}");
        }

        return IOFactory::load($path);
    }

    /** @return list<array{block: int, col_bai: int, col_date: int, col_bd: int, col_kt: int, ho_ten: string, bien_so: string, ma_gv: string, ma_khoa: string, ca: string}> */
    public function parseTeacherBlocks(Worksheet $sheet): array
    {
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $blocks = intdiv($maxCol, self::COLS_PER_BLOCK);
        $out = [];
        for ($b = 0; $b < $blocks; $b++) {
            $base = 1 + $b * self::COLS_PER_BLOCK;
            $colName = $base + 3;
            $colMa = $base + 4;
            $headerName = trim(str_replace(["\r", "\n"], ' ', (string) $sheet->getCell(Coordinate::stringFromColumnIndex($colName).'1')->getValue()));
            $headerMa = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($colMa).'1')->getValue());
            preg_match('/(\d{2}A-\d+\.\d+)/u', $headerName, $mBien);
            $bien = $mBien[1] ?? '';
            preg_match('/^(\d+)-(\d+)/', $headerMa, $mMa);
            $maGv = $mMa[1] ?? '';
            $maKhoa = $mMa[2] ?? '';
            $hoTen = preg_replace('/\s*-\s*\d{2}A-.*/u', '', $headerName) ?? $headerName;
            $hoTen = trim($hoTen);
            $ca = ($b % 2 === 0) ? 'sang' : 'chieu';
            $out[] = [
                'block' => $b,
                'col_bai' => $base + 3,
                'col_date' => $base + 1,
                'col_bd' => $base + 5,
                'col_kt' => $base + 6,
                'ho_ten' => $hoTen,
                'bien_so' => $bien,
                'ma_gv' => $maGv,
                'ma_khoa' => $maKhoa,
                'ca' => $ca,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public function buildCapXe(array $blocks): array
    {
        $caps = [];
        $stt = 0;
        for ($i = 0; $i < count($blocks); $i += 2) {
            $stt++;
            $sang = $blocks[$i];
            $chieu = $blocks[$i + 1] ?? null;
            $caps[] = [
                'stt' => $stt,
                'bien_so' => $sang['bien_so'],
                'gv_sang' => $sang['ma_gv'],
                'ten_gv_sang' => $sang['ho_ten'],
                'gv_chieu' => $chieu['ma_gv'] ?? '',
                'ten_gv_chieu' => $chieu['ho_ten'] ?? '',
            ];
        }

        return $caps;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array{iso: string, row: int}>
     */
    public function scanDates(Worksheet $sheet, array $blocks): array
    {
        if ($blocks === []) {
            return [];
        }
        $colDate = (int) $blocks[0]['col_date'];
        $colLetter = Coordinate::stringFromColumnIndex($colDate);
        $maxRow = (int) $sheet->getHighestRow();
        $dates = [];
        for ($r = 2; $r <= $maxRow; $r++) {
            $iso = $this->cellToIso($sheet->getCell($colLetter.$r)->getValue());
            if ($iso !== '') {
                $dates[] = ['iso' => $iso, 'row' => $r];
            }
        }

        return $dates;
    }

    public function cellToIso(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return '';
            }
        }

        return LichNgay::normalizeDate($value);
    }

    public function readBai(Worksheet $sheet, int $colBai, int $row): string
    {
        return trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($colBai).$row)->getValue());
    }

    public function readTimeRange(Worksheet $sheet, int $colBd, int $colKt, int $row): array
    {
        $bd = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($colBd).$row)->getFormattedValue());
        $kt = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($colKt).$row)->getFormattedValue());

        return [$bd, $kt];
    }

    public static function mapBaiToMau(string $bai): ?string
    {
        $s = strtoupper(trim($bai));
        if ($s === '') {
            return null;
        }
        if (str_contains($s, 'HÌNH') || str_contains($s, 'HINH')) {
            return BaiGiang::HINH;
        }
        if (str_contains($s, 'CABIN')) {
            return BaiGiang::CABIN;
        }
        if (str_contains($s, 'ÔN') && (str_contains($s, 'STL') || str_contains($s, 'TĐ') || str_contains($s, 'TD'))) {
            return str_contains($s, 'STL') ? BaiGiang::ON_STL : BaiGiang::ON_TD;
        }
        if (str_contains($s, 'TỰ ĐỘNG') || str_contains($s, 'TU DONG') || str_contains($s, 'TỰĐỘNG')) {
            return BaiGiang::TU_DONG;
        }
        if (str_contains($s, 'PHỨC TẠP') || str_contains($s, 'PHUC TAP')) {
            return BaiGiang::PHUC_TAP;
        }
        if (str_contains($s, 'DỐC') || str_contains($s, 'QC')) {
            return BaiGiang::DOC_QC;
        }
        if (preg_match('/CAO\s*T[ỐO]C/u', $s)) {
            return BaiGiang::CAO_TOC;
        }
        if (str_contains($s, 'CÓ TẢI') || str_contains($s, 'CO TAI')) {
            return BaiGiang::CO_TAI;
        }
        if (str_contains($s, 'BỔ SUNG') || str_contains($s, 'BO SUNG') || str_contains($s, 'Bổ SUNG')) {
            return BaiGiang::BO_SUNG;
        }
        if (str_contains($s, 'KIỂM TRA') || str_contains($s, 'KIEM TRA')) {
            return BaiGiang::KIEM_TRA;
        }

        return null;
    }

    public static function parseGioFromExcelRange(string $bd, string $kt, string $mau, int $defaultDay): float
    {
        if ($mau === BaiGiang::CABIN) {
            return 10.0;
        }
        if ($mau === BaiGiang::TU_DONG) {
            return 5.0;
        }
        if ($mau === BaiGiang::KIEM_TRA) {
            return 0.0;
        }
        $combined = trim($bd.' '.$kt);
        if (preg_match_all("/(\d+)H(\d+)?'?/u", $combined, $matches, PREG_SET_ORDER) && count($matches) >= 2) {
            $segments = [];
            for ($i = 0; $i < count($matches) - 1; $i++) {
                $start = self::excelTimeToMinutes($matches[$i][0]);
                $end = self::excelTimeToMinutes($matches[$i + 1][0]);
                if ($start !== null && $end !== null && $end > $start) {
                    $segments[] = ($end - $start) / 60;
                }
            }
            if ($segments !== []) {
                return round(array_sum($segments), 2);
            }
        }
        $bdMin = self::excelTimeToMinutes($bd);
        $ktMin = self::excelTimeToMinutes($kt);
        if ($bdMin !== null && $ktMin !== null && $ktMin > $bdMin) {
            return round(($ktMin - $bdMin) / 60, 2);
        }

        return (float) $defaultDay;
    }

    private static function excelTimeToMinutes(string $raw): ?int
    {
        $raw = strtoupper(trim($raw));
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^(\d+)H(\d+)?/', $raw, $m)) {
            $h = (int) $m[1];
            $min = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

            return $h * 60 + $min;
        }
        if (preg_match('/^(\d+)H(\d+)\'/', $raw, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }

        return null;
    }
}
