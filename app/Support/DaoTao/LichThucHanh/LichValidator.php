<?php

namespace App\Support\DaoTao\LichThucHanh;

final class LichValidator
{
    /**
     * @param  array<string, mixed>  $cauHinh
     * @param  array<string, mixed>  $lich
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public static function validate(array $cauHinh, array $lich, array $tomTat = []): array
    {
        $errors = [];
        $warnings = [];

        $gioNgay = (int) ($cauHinh['gio_day_moi_ngay'] ?? 8);
        $maxGio = (float) ($cauHinh['gio_day_moi_ngay_toi_da'] ?? 10);
        $khung = $cauHinh['khung_xe'] ?? ['mo' => '05:59', 'dong' => '22:00'];
        $khungPhut = MauCa::soGioTuKhoang([$khung['mo'] ?? '05:59', $khung['dong'] ?? '22:00']) * 2;

        if ($gioNgay * 2 > $khungPhut + 0.01) {
            $errors[] = 'Tổng giờ hai ca/ngày ('.($gioNgay * 2).'h) vượt khung giờ xe — cần mở rộng khung hoặc giảm giờ/ngày.';
        }

        if ($gioNgay > $maxGio) {
            $warnings[] = 'Giờ dạy/ngày vượt giới hạn tối đa cấu hình ('.$maxGio.'h).';
        }

        $dk = $cauHinh['dieu_kien_dat_hang'] ?? null;
        if (is_array($dk) && (float) ($dk['gio_cao_toc_gio'] ?? 0) <= 0) {
            $warnings[] = 'Hạng '.($cauHinh['hang_dao_tao'] ?? 'B').' chưa khai giờ cao tốc trên Điều kiện đạt DAT.';
        }

        $cabinByDay = [];
        $tuDongByDay = [];
        foreach ($lich['cells'] ?? [] as $key => $cell) {
            if (! is_array($cell)) {
                continue;
            }
            [$iso] = explode('|', $key, 2);
            $mau = (string) ($cell['mau'] ?? '');
            $cap = (int) ($cell['cap_stt'] ?? 0);
            if ($mau === BaiGiang::CABIN) {
                $cabinByDay[$iso] = ($cabinByDay[$iso] ?? 0) + 1;
            }
            if ($mau === BaiGiang::TU_DONG) {
                $tuDongByDay[$iso][] = $cap;
            }
            if (($cell['nghi_ca_khoa'] ?? false) && ($cell['bai'] ?? '') !== 'NGHỈ' && ($cell['so_gio'] ?? 0) > 0) {
                $errors[] = 'Có bài học vào ngày nghỉ: '.$iso.' · '.($cell['ho_ten'] ?? '');
            }
            if (($cell['so_gio'] ?? 0) > $maxGio && $mau !== BaiGiang::CABIN) {
                $warnings[] = 'Giờ/ngày vượt tối đa cấu hình (ngoại trừ cabin): '.$iso.' · '.($cell['ho_ten'] ?? '').' ('.($cell['so_gio'] ?? 0).'h).';
            }
        }

        foreach ($cabinByDay as $iso => $count) {
            if ($count > 2) {
                $errors[] = 'Cabin: hơn 1 cặp trong ngày '.$iso.' (số ô '.$count.').';
            }
        }

        foreach ($tuDongByDay as $iso => $caps) {
            $unique = array_unique($caps);
            if (count($unique) > 1) {
                $errors[] = 'Xe tự động: nhiều cặp cùng ngày '.$iso.'.';
            }
        }

        $yeuCau = YeuCauGio::theoGiaoVien($cauHinh);
        foreach ($tomTat['giao_viens'] ?? [] as $ma => $row) {
            $yc = $yeuCau[$ma] ?? null;
            if ($yc === null) {
                continue;
            }
            foreach (['dat' => 'gio_dat', 'dem' => 'gio_dem', 'tu_dong' => 'gio_tu_dong', 'cao_toc' => 'gio_cao_toc'] as $ycKey => $actualKey) {
                $need = (float) ($yc[$ycKey] ?? 0);
                $have = (float) ($row[$actualKey] ?? 0);
                if ($need > 0 && $have + 0.5 < $need) {
                    $warnings[] = 'GV '.$row['ho_ten'].' thiếu '.$ycKey.' (cần ~'.$need.'h, có '.$have.'h).';
                }
                if ($need > 0 && $have > $need + 0.5) {
                    $warnings[] = 'GV '.$row['ho_ten'].' dư '.$ycKey.' (cần ~'.$need.'h, có '.$have.'h).';
                }
            }
        }

        return ['errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings))];
    }
}
