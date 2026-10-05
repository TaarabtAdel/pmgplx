<?php

namespace App\Support\DaoTao\LichThucHanh;

/** Gộp cấu hình lịch TH từ file Excel (7 cột/GV) — mọi mã khóa qua tham số import. */
final class LichCauHinhTuExcel
{
    public const NGAY_KG = '2026-08-18';

    public const NGAY_KT = '2026-10-04';

    public const MA_KHOA = '44007K261005';

    /** @return array<string, mixed> */
    public static function nghiDinhKyMacDinhTuMau(): array
    {
        return [
            'thu_trong_tuan' => 5,
            'tu_ngay' => '2026-08-27',
            'den_ngay' => '2026-09-17',
            'ngoai_le_van_day' => ['2026-08-20', '2026-09-24', '2026-10-01'],
            'nghi_bu' => [
                ['tu' => '2026-09-24', 'den' => '2026-09-25'],
            ],
        ];
    }

    /** @return list<string> */
    public static function nghiCoDinhMacDinhTuMau(): array
    {
        return ['2026-09-02'];
    }

    /** @return list<string> */
    public static function bienSoNghiRiengTuMau(): array
    {
        return [
            '74A-267.72',
            '74A-143.29',
            '74A-203.85',
            '74A-143.24',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $capXe
     * @param  list<array<string, mixed>>  $nghiRiengParsed
     * @return array<string, mixed>
     */
    public static function buildCauHinh(array $capXe, array $nghiRiengParsed = [], ?string $maKhoa = null): array
    {
        $base = CauHinhDefaults::khung($maKhoa ?? self::MA_KHOA, 'B');
        $base['ma_khoa'] = $maKhoa ?? self::MA_KHOA;
        $base['ngay_khai_giang'] = self::NGAY_KG;
        $base['ngay_ket_thuc_du_kien'] = self::NGAY_KT;
        $base['he_so_quy_doi'] = 2;
        $base['gio_day_moi_ngay'] = 8;
        $base['so_hoc_vien_mac_dinh'] = 5;
        $base['cap_xe'] = $capXe;
        $base['nghi_dinh_ky'] = self::nghiDinhKyMacDinhTuMau();
        $base['nghi_co_dinh'] = self::nghiCoDinhMacDinhTuMau();
        $base['nghi_rieng'] = $nghiRiengParsed !== [] ? $nghiRiengParsed : self::loadNghiRiengFallback();
        $base['lich_den_ngay'] = self::NGAY_KT;
        $base['moc_lich'] = LichMocLich::mocMacDinhHangB();
        $base['import_meta'] = [
            'nguon' => 'excel_import',
            'ghi_chu' => 'Import từ file Excel lịch TH (layout 7 cột/GV)',
        ];

        return $base;
    }

    /** @return list<array<string, mixed>> */
    public static function loadNghiRiengFallback(): array
    {
        foreach ([
            config_path('lich_thuc_hanh_nghi_rieng_mau.json'),
            config_path('bk54_nghi_rieng.json'),
        ] as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $raw = json_decode((string) file_get_contents($path), true);

            return is_array($raw) ? $raw : [];
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $teacherBlocks
     * @param  list<array{iso: string, row: int}>  $dates
     * @param  list<array<string, mixed>>  $capXe
     */
    public static function inferNghiRiengFromSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        LichThucHanhExcelReader $reader,
        array $teacherBlocks,
        array $dates,
        array $capXe
    ): array {
        /** @var array<int, list<array<string, mixed>>> $blocksByCap */
        $blocksByCap = [];
        foreach ($teacherBlocks as $tb) {
            $stt = (int) floor(((int) $tb['block']) / 2) + 1;
            $blocksByCap[$stt][] = $tb;
        }

        $byCap = [];
        foreach ($dates as $entry) {
            $row = $entry['row'];
            $iso = $entry['iso'];
            $capHas = [];
            foreach ($blocksByCap as $stt => $tbs) {
                $has = false;
                foreach ($tbs as $tb) {
                    $bai = $reader->readBai($sheet, (int) $tb['col_bai'], $row);
                    if (LichThucHanhExcelReader::mapBaiToMau($bai) !== null) {
                        $has = true;
                        break;
                    }
                }
                $capHas[$stt] = $has;
            }
            $anyCap = in_array(true, $capHas, true);
            if (! $anyCap) {
                continue;
            }
            foreach ($capHas as $stt => $has) {
                if (! $has) {
                    $byCap[$stt][] = $iso;
                }
            }
        }

        $out = [];
        foreach ($capXe as $cap) {
            $stt = (int) ($cap['stt'] ?? 0);
            $bien = (string) ($cap['bien_so'] ?? '');
            if (! in_array($bien, self::bienSoNghiRiengTuMau(), true)) {
                continue;
            }
            $ngay = array_values(array_unique($byCap[$stt] ?? []));
            if ($ngay === []) {
                continue;
            }
            $out[] = [
                'cap_stt' => $stt,
                'bien_so' => $bien,
                'ngay' => $ngay,
            ];
        }

        return $out;
    }
}
