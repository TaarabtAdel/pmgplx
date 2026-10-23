<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\ThuTienHocVien;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class KetQuaSatHachExcelParser
{
    public const COL_STT = 'A';

    public const COL_HO_TEN = 'B';

    public const COL_NGAY_SINH = 'C';

    public const COL_SO_DINH_DANH = 'F';

    public const COL_HANG = 'I';

    public const COL_LT = 'L';

    public const COL_LT_LAN = 'M';

    public const COL_SA_HINH = 'N';

    public const COL_SA_HINH_LAN = 'O';

    public const COL_DUONG = 'P';

    public const COL_DUONG_LAN = 'Q';

    public const COL_TONG_HOP = 'R';

    private const HEADER_SCAN_MAX_ROW = 5;

    /** @var array<string, array{kq: string, lan: string, nhan: string}> */
    public const MON = [
        'ly_thuyet' => ['kq' => self::COL_LT, 'lan' => self::COL_LT_LAN, 'nhan' => 'Lý thuyết'],
        'sa_hinh' => ['kq' => self::COL_SA_HINH, 'lan' => self::COL_SA_HINH_LAN, 'nhan' => 'Sa hình'],
        'duong' => ['kq' => self::COL_DUONG, 'lan' => self::COL_DUONG_LAN, 'nhan' => 'Đường'],
    ];

    /**
     * @return array{
     *     file_name: string,
     *     sheet_name: string,
     *     records: list<array<string, mixed>>,
     *     meta: array{record_count: int, header_row: int, data_start_row: int}
     * }
     */
    public function parse(Spreadsheet $spreadsheet, string $fileName): array
    {
        $worksheet = $spreadsheet->getSheet(0);
        $context = $this->resolveSheetContext($worksheet);
        $all = $this->readRecords($worksheet, $context);

        if ($all === []) {
            throw new RuntimeException('Không tìm thấy dòng dữ liệu (cột F: Số định danh).');
        }

        return [
            'file_name' => $fileName,
            'sheet_name' => $worksheet->getTitle(),
            'records' => $all,
            'meta' => [
                'record_count' => count($all),
                'header_row' => $context['header_row'],
                'data_start_row' => $context['data_start_row'],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readAllRecordsFromFile(string $path): array
    {
        $spreadsheet = DatDSPhienExcelParser::loadSpreadsheet($path);

        try {
            return $this->readRecords(
                $spreadsheet->getSheet(0),
                $this->resolveSheetContext($spreadsheet->getSheet(0))
            );
        } finally {
            DatDSPhienExcelParser::releaseSpreadsheet($spreadsheet);
        }
    }

    /**
     * @return array{header_row: int, data_start_row: int}
     */
    private function resolveSheetContext(Worksheet $worksheet): array
    {
        for ($row = 1; $row <= self::HEADER_SCAN_MAX_ROW; $row++) {
            $sub = mb_strtolower(trim((string) $worksheet->getCell(self::COL_LT.$row)->getCalculatedValue()));
            if ($sub === 'lt') {
                return ['header_row' => max(1, $row - 1), 'data_start_row' => $row + 1];
            }
        }

        return ['header_row' => 1, 'data_start_row' => 3];
    }

    /**
     * @param  array{header_row: int, data_start_row: int}  $context
     * @return list<array<string, mixed>>
     */
    private function readRecords(Worksheet $worksheet, array $context): array
    {
        $records = [];
        $maxRow = (int) $worksheet->getHighestRow();

        for ($row = $context['data_start_row']; $row <= $maxRow; $row++) {
            $soDinhDanh = self::normalizeCccd($worksheet->getCell(self::COL_SO_DINH_DANH.$row)->getCalculatedValue());
            $hoTen = trim((string) $worksheet->getCell(self::COL_HO_TEN.$row)->getCalculatedValue());
            if ($soDinhDanh === '' && $hoTen === '') {
                continue;
            }

            $mon = [];
            foreach (self::MON as $khoan => $cols) {
                $rawKq = $worksheet->getCell($cols['kq'].$row)->getCalculatedValue();
                $mon[$khoan] = [
                    'kq_raw' => trim((string) $rawKq),
                    'kq' => self::parseKetQua($rawKq),
                    'lan' => self::parseLan($worksheet->getCell($cols['lan'].$row)->getCalculatedValue()),
                ];
            }

            $records[] = [
                'row' => $row,
                'stt' => trim((string) $worksheet->getCell(self::COL_STT.$row)->getCalculatedValue()),
                'ho_ten' => $hoTen,
                'ngay_sinh' => trim((string) $worksheet->getCell(self::COL_NGAY_SINH.$row)->getCalculatedValue()),
                'so_dinh_danh' => $soDinhDanh,
                'so_dinh_danh_raw' => trim((string) $worksheet->getCell(self::COL_SO_DINH_DANH.$row)->getCalculatedValue()),
                'hang' => trim((string) $worksheet->getCell(self::COL_HANG.$row)->getCalculatedValue()),
                'tong_hop' => trim((string) $worksheet->getCell(self::COL_TONG_HOP.$row)->getCalculatedValue()),
                'mon' => $mon,
            ];
        }

        return $records;
    }

    public static function normalizeCccd(mixed $value): string
    {
        return preg_replace('/\D+/', '', trim((string) $value)) ?? '';
    }

    public static function cccdMatches(string $a, string $b): bool
    {
        $a = self::normalizeCccd($a);
        $b = self::normalizeCccd($b);
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }

        $len = max(strlen($a), strlen($b));

        return str_pad($a, $len, '0', STR_PAD_LEFT) === str_pad($b, $len, '0', STR_PAD_LEFT);
    }

    /** @return 'dau'|'rot'|null Chưa thi / bỏ qua khi null */
    public static function parseKetQua(mixed $raw): ?string
    {
        $v = mb_strtoupper(trim((string) $raw));
        if ($v === '' || $v === '-') {
            return null;
        }
        if ($v === 'Đ' || $v === 'D' || $v === 'ĐẬU' || $v === 'DAU') {
            return ThuTienHocVien::DAU;
        }
        if ($v === 'KĐ' || $v === 'KD' || $v === 'K' || $v === 'RỚT' || $v === 'ROT') {
            return ThuTienHocVien::ROT;
        }

        return null;
    }

    public static function parseLan(mixed $raw): int
    {
        $n = (int) $raw;

        return $n >= 1 ? $n : 1;
    }

    public static function nhanKetQuaRaw(?string $kq): string
    {
        return match ($kq) {
            ThuTienHocVien::DAU => 'Đậu',
            ThuTienHocVien::ROT => 'Rớt',
            default => 'Chưa thi',
        };
    }
}
