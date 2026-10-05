<?php

namespace App\Support\DaoTao\LichThucHanh;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Tổng hợp chỉ số từ file Excel tham chiếu (test / đối chiếu). */
final class LichThucHanhReferenceMetrics
{
    public function __construct(
        private readonly LichThucHanhExcelReader $reader = new LichThucHanhExcelReader,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fromWorksheet(Worksheet $sheet): array
    {
        $blocks = $this->reader->parseTeacherBlocks($sheet);
        $dates = $this->reader->scanDates($sheet, $blocks);
        $metrics = [];
        foreach ($blocks as $tb) {
            $ma = $tb['ma_gv'];
            $metrics[$ma] = [
                'ma_gv' => $ma,
                'cap_stt' => (int) floor(((int) $tb['block']) / 2) + 1,
                'so_ngay_lam' => 0,
                'gio_theo_loai' => [],
                'cabin_ngay' => null,
                'auto_block' => [],
                'ngay_cuoi' => null,
            ];
        }

        foreach ($blocks as $tb) {
            $ma = $tb['ma_gv'];
            foreach ($dates as $entry) {
                $row = $entry['row'];
                $iso = $entry['iso'];
                $baiRaw = $this->reader->readBai($sheet, (int) $tb['col_bai'], $row);
                $mau = LichThucHanhExcelReader::mapBaiToMau($baiRaw);
                if ($mau === null) {
                    continue;
                }
                [$bd, $kt] = $this->reader->readTimeRange($sheet, (int) $tb['col_bd'], (int) $tb['col_kt'], $row);
                $gio = LichThucHanhExcelReader::parseGioFromExcelRange($bd, $kt, $mau, 8);
                $metrics[$ma]['so_ngay_lam']++;
                $bucket = $mau;
                if ($mau === BaiGiang::BAN_DEM) {
                    $bucket = BaiGiang::PHUC_TAP;
                }
                $metrics[$ma]['gio_theo_loai'][$bucket] = ($metrics[$ma]['gio_theo_loai'][$bucket] ?? 0) + $gio;
                if ($mau === BaiGiang::CABIN && $metrics[$ma]['cabin_ngay'] === null) {
                    $metrics[$ma]['cabin_ngay'] = $iso;
                }
                if ($mau === BaiGiang::TU_DONG) {
                    $metrics[$ma]['auto_block'][] = $iso;
                }
                $metrics[$ma]['ngay_cuoi'] = $iso;
            }
            $metrics[$ma]['auto_block'] = array_values(array_unique($metrics[$ma]['auto_block']));
            sort($metrics[$ma]['auto_block']);
        }

        return $metrics;
    }

    /** @return array<string, array<string, mixed>> */
    public function fromPath(string $path): array
    {
        $sheet = $this->reader->load($path)->getActiveSheet();

        return $this->fromWorksheet($sheet);
    }
}
