<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;

/** Cột dòng lịch TH từng GV — hạng C vs B tự động (mẫu THUC HANH B11 NHOM 5.1). */
final class LichHocThucHanhGiaoVienLayout
{
    /**
     * @param  list<array<string, mixed>>  $buoi  Buổi thô từ {@see LichHocThucHanhGiaoVienDanhSach::nhomTheoGiaoVien}
     * @return list<array{
     *     thu: string,
     *     ngay: Carbon,
     *     so_gio: ?float,
     *     mon_hoc: string,
     *     dia_diem: string,
     *     ghi_chu_f: string,
     *     cot_g: string,
     *     cot_h: string,
     * }>
     */
    public static function dongRaFile(array $buoi, string $hang): array
    {
        if ($hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG) {
            return self::dongB01(self::gopCungNgay($buoi));
        }

        return self::dongHangC($buoi);
    }

    /**
     * @param  list<array<string, mixed>>  $dong
     */
    public static function ghiLenSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $dong, string $hang): void
    {
        $rowBatDau = 6;
        $rowCuoi = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG ? 53 : 74;
        $colsClear = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG
            ? ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K']
            : ['B', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        for ($r = $rowBatDau; $r <= $rowCuoi; $r++) {
            foreach ($colsClear as $col) {
                $sheet->setCellValue($col.$r, null);
            }
        }

        $row = $rowBatDau;
        foreach ($dong as $slot) {
            if ($row > $rowCuoi) {
                break;
            }
            /** @var Carbon $ngay */
            $ngay = $slot['ngay'];
            $sheet->getCell('B'.$row)->setValue(
                \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ngay->copy()->startOfDay())
            );
            $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('m/d/yyyy');

            if ($hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG) {
                self::ghiOHangB01($sheet, $row, $slot);
            } else {
                self::ghiOHangC($sheet, $row, $slot);
            }

            $row++;
        }
    }

    public static function nhanThu(Carbon $ngay): string
    {
        if ($ngay->dayOfWeek === 0) {
            return 'CN';
        }

        return 'T'.($ngay->dayOfWeek + 1);
    }

    /**
     * @param  list<array<string, mixed>>  $buoi
     * @return list<array<string, mixed>>
     */
    private static function gopCungNgay(array $buoi): array
    {
        /** @var array<string, list<array<string, mixed>>> $theoNgay */
        $theoNgay = [];
        foreach ($buoi as $slot) {
            /** @var Carbon $ngay */
            $ngay = $slot['ngay'];
            $k = $ngay->format('Y-m-d');
            $theoNgay[$k][] = $slot;
        }

        $out = [];
        foreach ($theoNgay as $slots) {
            if (count($slots) === 1) {
                $out[] = $slots[0];

                continue;
            }
            $out[] = self::gopSlotMotNgay($slots);
        }

        usort($out, static function (array $a, array $b): int {
            /** @var Carbon $na */
            $na = $a['ngay'];
            /** @var Carbon $nb */
            $nb = $b['ngay'];

            return $na->timestamp <=> $nb->timestamp;
        });

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $slots
     * @return array<string, mixed>
     */
    private static function gopSlotMotNgay(array $slots): array
    {
        $first = $slots[0];
        /** @var Carbon $ngay */
        $ngay = $first['ngay'];

        $mon = [];
        $chiTietF = [];
        $tongGio = 0.0;
        $diaDiem = '';

        foreach ($slots as $s) {
            $nd = trim((string) ($s['noi_dung'] ?? ''));
            if ($nd !== '' && ! in_array($nd, $mon, true)) {
                $mon[] = $nd;
            }
            $g = (float) ($s['so_gio'] ?? 0);
            $tongGio += $g;
            $loai = trim((string) ($s['ghi_chu_loai'] ?? ''));
            if ($g > 0 && $loai !== '' && $loai !== '—') {
                $h = $g === (float) (int) $g ? (string) (int) $g : (string) $g;
                $chiTietF[] = $h.' h '.mb_strtolower($loai);
            }
            $dd = trim((string) ($s['dia_diem'] ?? ''));
            if ($dd !== '') {
                $diaDiem = $dd;
            }
        }

        return [
            'ngay' => $ngay,
            'so_gio' => $tongGio > 0 ? $tongGio : ($first['so_gio'] ?? null),
            'noi_dung' => implode("\n + ", $mon),
            'dia_diem' => $diaDiem,
            'ghi_chu_loai' => $first['ghi_chu_loai'] ?? '',
            'ghi_chu_raw' => $first['ghi_chu_raw'] ?? '',
            'chi_tiet_gio' => implode("\n + ", $chiTietF),
            'gop_ngay' => true,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $buoi
     * @return list<array<string, mixed>>
     */
    private static function dongHangC(array $buoi): array
    {
        $out = [];
        foreach ($buoi as $slot) {
            /** @var Carbon $ngay */
            $ngay = $slot['ngay'];
            $out[] = [
                'thu' => self::nhanThu($ngay),
                'ngay' => $ngay,
                'so_gio' => isset($slot['so_gio']) ? (float) $slot['so_gio'] : null,
                'mon_hoc' => (string) ($slot['noi_dung'] ?? ''),
                'dia_diem' => (string) ($slot['dia_diem'] ?? ''),
                'ghi_chu_f' => (string) ($slot['ghi_chu'] ?? ''),
                'cot_g' => '',
                'cot_h' => '',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $buoi
     * @return list<array<string, mixed>>
     */
    private static function dongB01(array $buoi): array
    {
        $out = [];
        foreach ($buoi as $slot) {
            /** @var Carbon $ngay */
            $ngay = $slot['ngay'];
            $soGio = isset($slot['so_gio']) ? (float) $slot['so_gio'] : null;
            $loai = trim((string) ($slot['ghi_chu_loai'] ?? ''));
            $gop = (bool) ($slot['gop_ngay'] ?? false);

            $ghiChuF = $gop
                ? trim((string) ($slot['chi_tiet_gio'] ?? ''))
                : '';

            $cotG = '';
            $cotH = '';
            if ($soGio !== null && $soGio > 0) {
                $hInt = $soGio === (float) (int) $soGio ? (int) $soGio : $soGio;
                $nhanH = self::nhanLoaiCotH($slot);
                if ($nhanH !== '' && ! in_array(mb_strtolower($nhanH), ['hình', 'hinh'], true)) {
                    $cotG = (string) $hInt;
                    $cotH = $nhanH.' '.$hInt;
                    if ($gop && count(explode("\n + ", (string) ($slot['noi_dung'] ?? ''))) > 1) {
                        $cotH = $nhanH.' '.(int) floor($hInt / 2);
                    }
                }
            }

            $out[] = [
                'thu' => self::nhanThu($ngay),
                'ngay' => $ngay,
                'so_gio' => $soGio,
                'mon_hoc' => (string) ($slot['noi_dung'] ?? ''),
                'dia_diem' => (string) ($slot['dia_diem'] ?? ''),
                'ghi_chu_f' => $ghiChuF,
                'cot_g' => $cotG,
                'cot_h' => $cotH,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    private static function ghiOHangC(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, array $slot): void
    {
        $soGio = $slot['so_gio'] ?? null;
        if ($soGio !== null && $soGio > 0) {
            $sheet->setCellValue('D'.$row, $soGio === (float) (int) $soGio ? (int) $soGio : $soGio);
        }
        $mon = trim((string) ($slot['mon_hoc'] ?? ''));
        if ($mon !== '') {
            $sheet->setCellValue('E'.$row, $mon);
        }
        $dd = trim((string) ($slot['dia_diem'] ?? ''));
        if ($dd !== '') {
            $sheet->setCellValue('F'.$row, $dd);
        }
        $gc = trim((string) ($slot['ghi_chu_f'] ?? ''));
        if ($gc !== '' && $gc !== '—') {
            $sheet->setCellValue('G'.$row, $gc);
        }
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    /**
     * @param  array<string, mixed>  $slot
     */
    private static function nhanLoaiCotH(array $slot): string
    {
        $raw = mb_strtoupper(trim((string) ($slot['ghi_chu_raw'] ?? $slot['ghi_chu_loai'] ?? '')));
        if (str_contains($raw, 'PHỨC TẠP') || str_contains($raw, 'PHUC TAP')) {
            return 'Phức tạp';
        }
        if (str_contains($raw, 'BAN ĐÊM') || str_contains($raw, 'BAN DEM')) {
            return 'Ban đêm';
        }
        if (str_contains($raw, 'CÓ TẢI') || str_contains($raw, 'CO TAI')) {
            return 'Có tải';
        }

        $loai = trim((string) ($slot['ghi_chu_loai'] ?? ''));

        return $loai !== '—' ? $loai : '';
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    private static function ghiOHangB01(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, array $slot): void
    {
        $soGio = $slot['so_gio'] ?? null;
        if ($soGio !== null && $soGio > 0) {
            $sheet->setCellValue('C'.$row, $soGio === (float) (int) $soGio ? (int) $soGio : $soGio);
        }
        $mon = trim((string) ($slot['mon_hoc'] ?? ''));
        if ($mon !== '') {
            $sheet->setCellValue('D'.$row, $mon);
        }
        $dd = trim((string) ($slot['dia_diem'] ?? ''));
        if ($dd !== '') {
            $sheet->setCellValue('E'.$row, $dd);
        }
        $f = trim((string) ($slot['ghi_chu_f'] ?? ''));
        if ($f !== '') {
            $sheet->setCellValue('F'.$row, $f);
        }
        $g = trim((string) ($slot['cot_g'] ?? ''));
        if ($g !== '') {
            $sheet->setCellValue('G'.$row, $g);
        }
        $h = trim((string) ($slot['cot_h'] ?? ''));
        if ($h !== '') {
            $sheet->setCellValue('H'.$row, $h);
        }
    }
}