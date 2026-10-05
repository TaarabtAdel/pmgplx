<?php

namespace App\Support\DaoTao\LichThucHanh;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Trích kế hoạch theo GV từ worksheet Excel lịch TH. */
final class LichKeHoachExcelImporter
{
    public function __construct(
        private readonly LichThucHanhExcelReader $reader = new LichThucHanhExcelReader,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $teacherBlocks
     * @param  list<array{iso: string, row: int}>  $dates
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function fromWorksheet(Worksheet $sheet, array $teacherBlocks, array $dates): array
    {
        $out = [];
        foreach ($teacherBlocks as $tb) {
            $ma = $tb['ma_gv'];
            $out[$ma] = [];
            foreach ($dates as $entry) {
                $row = $entry['row'];
                $iso = $entry['iso'];
                $baiRaw = $this->reader->readBai($sheet, (int) $tb['col_bai'], $row);
                $mau = LichThucHanhExcelReader::mapBaiToMau($baiRaw);
                if ($mau === null) {
                    continue;
                }
                [$bd, $kt] = $this->reader->readTimeRange($sheet, (int) $tb['col_bd'], (int) $tb['col_kt'], $row);
                $soGio = LichThucHanhExcelReader::parseGioFromExcelRange($bd, $kt, $mau, 8);
                $noiDungPhu = '';
                if ($mau === BaiGiang::PHUC_TAP
                    && str_contains($kt, '18H')
                    && str_contains($kt, '22H')
                    && ! str_contains(str_replace(' ', '', $kt), '20H01')) {
                    $noiDungPhu = 'BAN ĐÊM';
                }
                $out[$ma][$iso] = [
                    'mau' => $mau,
                    'bai' => trim($baiRaw),
                    'so_gio' => $soGio,
                    'bat_dau' => $bd,
                    'ket_thuc' => $kt,
                    'noi_dung_phu' => $noiDungPhu,
                ];
            }
        }

        return $out;
    }
}
