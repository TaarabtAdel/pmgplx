<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;

/** Nhãn mốc trên file Excel + giải thích gợi ý ngày (công thức / lịch GV / xe-tập). */
final class LichHocLyThuyetThucHanhMocMoTa
{
    /** @var list<string> */
    public const KEYS = [
        'ngay_khai_giang', 'ngay_bd_thuc_hanh',
        'ngay_bd_hinh_tren', 'ngay_kt_hinh_tren',
        'ngay_bd_cabin', 'ngay_kt_cabin',
        'ngay_bd_dat', 'ngay_kt_dat', 'ngay_bo_sung_dat',
        'ngay_bd_hinh_duoi', 'ngay_kt_hinh_duoi',
        'ngay_bd_on_luyen', 'ngay_kt_on_luyen', 'ngay_kiem_tra',
    ];

    /**
     * Cột «Mốc trên file» — tên + (công thức / ô Excel).
     *
     * @return array<string, string>
     */
    public static function nhanMocTrenFile(?string $hang): array
    {
        $hang = $hang ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;

        $chung = [
            'ngay_khai_giang' => 'Ngày khai giảng (danh mục khoá học · cột Ngày KG /pmgplx/danh-muc/khoa-hoc → Excel hàng 6 cột D; cột E = Ngày KG + 17 ngày)',
            'ngay_bd_thuc_hanh' => 'Ngày BD học thực hành (min lịch GV môn TH / ngày xe sớm nhất; hàng 10 = BD TH − 1)',
        ];

        return match ($hang) {
            LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG => array_merge($chung, [
                'ngay_bd_hinh_tren' => 'BD HÌNH trên (BD TH → hàng 12–13 cột D)',
                'ngay_kt_hinh_tren' => 'KT HÌNH trên (BD TH + 5 ngày → cột E)',
                'ngay_bd_cabin' => 'BD cabin (BD TH + 16 ngày = sau HÌNH trên + 11 → hàng 18 D)',
                'ngay_kt_cabin' => 'KT cabin (BD cabin + 6 ngày → hàng 18 E)',
                'ngay_bd_dat' => 'BD DAT (BD TH + 6 ngày → hàng 19 D)',
                'ngay_kt_dat' => 'KT DAT (BD DAT + 24 ngày → hàng 19 E)',
                'ngay_bo_sung_dat' => 'Dự phòng DAT, cabin (KT DAT + 1 ngày → hàng 24 D–E)',
                'ngay_bd_hinh_duoi' => 'BD HÌNH dưới / chữ chi (KT DAT + 2 ngày → hàng 26 D–E)',
                'ngay_kt_hinh_duoi' => 'KT HÌNH dưới / tổng hợp (BD HÌNH dưới + 8 ngày → hàng 27 E)',
                'ngay_bd_on_luyen' => 'BD ôn luyện (KT HÌNH dưới + 1 ngày → hàng 28 D)',
                'ngay_kt_on_luyen' => 'KT ôn luyện (BD ôn + 1 ngày → hàng 28 E)',
                'ngay_kiem_tra' => 'Ngày kiểm tra (KT ôn + 1 hoặc max lịch GV → hàng 28 B01)',
            ]),
            LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN => array_merge($chung, [
                'ngay_bd_hinh_tren' => 'BD HÌNH trên + dự phòng LT (BD TH → hàng 12–13 cột D)',
                'ngay_kt_hinh_tren' => 'KT HÌNH trên (BD TH + 3 ngày → cột E)',
                'ngay_bd_cabin' => 'BD cabin (BD TH + 4 ngày → hàng 18 D)',
                'ngay_kt_cabin' => 'KT cabin (BD cabin + 10 ngày → hàng 18 E)',
                'ngay_bd_dat' => 'BD DAT (BD TH + 4 ngày, cùng ngày BD cabin → hàng 19 D)',
                'ngay_kt_dat' => 'KT DAT (BD DAT + 30 ngày → hàng 19 E)',
                'ngay_bo_sung_dat' => 'Dự phòng DAT, cabin (KT DAT + 1 → +2 → hàng 25 D–E)',
                'ngay_bd_hinh_duoi' => 'Chữ chi (KT DAT + 3 ngày → hàng 26 D–E)',
                'ngay_kt_hinh_duoi' => 'KT tổng hợp (chữ chi + 5 ngày → hàng 27 E; cột D = chữ chi + 1)',
                'ngay_bd_on_luyen' => 'BD ôn luyện (KT tổng hợp + 1 ngày → hàng 28 D)',
                'ngay_kt_on_luyen' => 'KT ôn luyện (BD ôn + 1 ngày → hàng 28 E)',
                'ngay_kiem_tra' => 'Ngày kiểm tra (KT ôn + 2 hoặc max lịch → hàng 29)',
            ]),
            default => array_merge($chung, [
                'ngay_bd_hinh_tren' => 'BD HÌNH trên (lịch GV/xe «hình», sân…; không có → = BD TH — hàng 12–13 D)',
                'ngay_kt_hinh_tren' => 'KT HÌNH trên (lịch GV/xe hoặc trước BD cabin 1 ngày → cột E)',
                'ngay_bd_cabin' => 'BD cabin (lịch GV/xe «cabin» → hàng 18 D)',
                'ngay_kt_cabin' => 'KT cabin (lịch GV/xe «cabin» → hàng 18 E)',
                'ngay_bd_dat' => 'BD DAT (lịch GV/xe DAT, đường phức tạp… → hàng 19 D)',
                'ngay_kt_dat' => 'KT DAT (lịch GV/xe DAT → hàng 19 E)',
                'ngay_bo_sung_dat' => 'Bổ sung DAT (lịch hoặc KT DAT → hàng 25)',
                'ngay_bd_hinh_duoi' => 'BD HÌNH dưới / chữ Z (lịch GV/xe hoặc sau DAT → hàng 26 D)',
                'ngay_kt_hinh_duoi' => 'KT tổng hợp (lịch GV/xe → hàng 27 E)',
                'ngay_bd_on_luyen' => 'BD ôn luyện (lịch GV/xe «ôn»/sát hạch → hàng 28 D)',
                'ngay_kt_on_luyen' => 'KT ôn luyện (lịch GV/xe → hàng 28 E)',
                'ngay_kiem_tra' => 'Ngày kiểm tra (lịch «kiểm tra» hoặc sau ôn → hàng 29)',
            ]),
        };
    }

    /**
     * Gợi ý hiển thị: dd/mm/yyyy (nguồn / công thức khớp ngày đang gợi ý).
     *
     * @param  array<string, string|null>  $goiY
     * @return array<string, string>
     */
    public static function hienThiGoiY(array $goiY, ?string $hang): array
    {
        $hang = $hang ?? LichHocLyThuyetThucHanhLichNgay::HANG_C;
        $nguon = (string) ($goiY['goi_y_nguon'] ?? '');

        $kg = self::ngayTuChuoi($goiY['ngay_khai_giang'] ?? null);
        $bd = self::ngayTuChuoi($goiY['ngay_bd_thuc_hanh'] ?? null);
        $parsed = [];
        foreach (self::KEYS as $k) {
            $parsed[$k] = self::ngayTuChuoi($goiY[$k] ?? null);
        }

        $out = [];
        foreach (self::KEYS as $key) {
            $d = $parsed[$key];
            if ($d === null) {
                $out[$key] = '—';

                continue;
            }

            $chuThich = self::chuThichGoiYMoc($key, $d, $hang, $nguon, $kg, $bd, $parsed);
            $out[$key] = LichHocLyThuyetThucHanhLichNgay::dinhDangNgay($d)
                .($chuThich !== '' ? ' · '.$chuThich : '');
        }

        return $out;
    }

  /**
     * @param  array<string, Carbon|null>  $parsed
     */
    private static function chuThichGoiYMoc(
        string $key,
        Carbon $d,
        string $hang,
        string $nguon,
        ?Carbon $kg,
        ?Carbon $bd,
        array $parsed
    ): string {
        $tuCongThuc = match ($hang) {
            LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG => self::chuThichB01($key, $d, $kg, $bd, $parsed, $nguon),
            LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN => self::chuThichBSan($key, $d, $kg, $bd, $parsed, $nguon),
            default => self::chuThichHangC($key, $d, $kg, $bd, $parsed, $nguon),
        };

        if ($tuCongThuc !== '') {
            return $tuCongThuc;
        }

        return self::chuThichLichGvXe($nguon);
    }

    /**
     * @param  array<string, Carbon|null>  $parsed
     */
    private static function chuThichB01(string $key, Carbon $d, ?Carbon $kg, ?Carbon $bd, array $parsed, string $nguon): string
    {
        if (! str_contains($nguon, 'b01_offset')) {
            return '';
        }

        return match ($key) {
            'ngay_khai_giang' => self::neuKhop($d, $kg, 'Ngày KG danh mục khoá học'),
            'ngay_bd_thuc_hanh' => $bd && self::cungNgay($d, $bd) ? 'min lịch GV môn 6 / ngày xe sớm nhất' : '',
            'ngay_bd_hinh_tren' => self::neuKhop($d, $bd, 'BD TH'),
            'ngay_kt_hinh_tren' => self::neuKhop($d, self::cong($bd, 5), 'BD TH + 5 ngày'),
            'ngay_bd_dat' => self::neuKhop($d, self::cong($bd, 6), 'BD TH + 6 ngày'),
            'ngay_kt_dat' => self::neuKhop($d, self::cong($bd, 30), 'BD TH + 30 ngày'),
            'ngay_bd_cabin' => self::neuKhop($d, self::cong($bd, 16), 'BD TH + 16 ngày (BD DAT + 10)'),
            'ngay_kt_cabin' => self::neuKhop($d, self::cong($bd, 22), 'BD TH + 22 ngày'),
            'ngay_bo_sung_dat' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_kt_dat'), 1), 'KT DAT + 1 ngày'),
            'ngay_bd_hinh_duoi' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_kt_dat'), 2), 'KT DAT + 2 ngày'),
            'ngay_kt_hinh_duoi' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_bd_hinh_duoi'), 8), 'BD HÌNH dưới + 8 ngày'),
            'ngay_bd_on_luyen' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_kt_hinh_duoi'), 1), 'KT HÌNH dưới + 1 ngày'),
            'ngay_kt_on_luyen' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_bd_on_luyen'), 1), 'BD ôn + 1 ngày'),
            'ngay_kiem_tra' => self::chuThichKiemTra($d, self::cong(self::lay($parsed, 'ngay_kt_on_luyen'), 1)),
            default => '',
        };
    }

    /**
     * @param  array<string, Carbon|null>  $parsed
     */
    private static function chuThichBSan(string $key, Carbon $d, ?Carbon $kg, ?Carbon $bd, array $parsed, string $nguon): string
    {
        if (! str_contains($nguon, 'b_san_offset')) {
            return '';
        }

        $datKt = self::lay($parsed, 'ngay_kt_dat');
        $chuChi = self::cong($datKt, 3);

        return match ($key) {
            'ngay_khai_giang' => self::neuKhop($d, $kg, 'Ngày KG danh mục khoá học'),
            'ngay_bd_thuc_hanh' => $bd && self::cungNgay($d, $bd) ? 'min lịch GV / ngày xe sớm nhất' : '',
            'ngay_bd_hinh_tren' => self::neuKhop($d, $bd, 'BD TH'),
            'ngay_kt_hinh_tren' => self::neuKhop($d, self::cong($bd, 3), 'BD TH + 3 ngày'),
            'ngay_bd_cabin', 'ngay_bd_dat' => self::neuKhop($d, self::cong($bd, 4), 'BD TH + 4 ngày'),
            'ngay_kt_cabin' => self::neuKhop($d, self::cong($bd, 14), 'BD TH + 14 ngày'),
            'ngay_kt_dat' => self::neuKhop($d, self::cong($bd, 34), 'BD TH + 34 ngày'),
            'ngay_bo_sung_dat' => self::neuKhop($d, self::cong($datKt, 1), 'KT DAT + 1 ngày'),
            'ngay_bd_hinh_duoi' => self::neuKhop($d, $chuChi, 'KT DAT + 3 ngày (chữ chi)'),
            'ngay_kt_hinh_duoi' => self::neuKhop($d, self::cong($chuChi, 5), 'chữ chi + 5 ngày'),
            'ngay_bd_on_luyen' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_kt_hinh_duoi'), 1), 'KT tổng hợp + 1 ngày'),
            'ngay_kt_on_luyen' => self::neuKhop($d, self::cong(self::lay($parsed, 'ngay_bd_on_luyen'), 1), 'BD ôn + 1 ngày'),
            'ngay_kiem_tra' => self::chuThichKiemTra($d, self::cong(self::lay($parsed, 'ngay_kt_on_luyen'), 2)),
            default => '',
        };
    }

    /**
     * @param  array<string, Carbon|null>  $parsed
     */
    private static function chuThichHangC(string $key, Carbon $d, ?Carbon $kg, ?Carbon $bd, array $parsed, string $nguon): string
    {
        if (str_contains($nguon, 'b01_offset') || str_contains($nguon, 'b_san_offset')) {
            return '';
        }

        return match ($key) {
            'ngay_khai_giang' => self::neuKhop($d, $kg, 'Ngày KG danh mục khoá học'),
            'ngay_bd_thuc_hanh' => $bd && self::cungNgay($d, $bd) ? 'min lịch GV môn thực hành / ngày xe' : '',
            'ngay_bd_hinh_tren' => $bd && self::cungNgay($d, $bd) ? 'BD TH (mặc định khi chưa có lịch hình)' : '',
            'ngay_kt_hinh_tren' => self::chuThichTruocCabin($d, self::lay($parsed, 'ngay_bd_cabin')),
            default => '',
        };
    }

    private static function chuThichTruocCabin(Carbon $d, ?Carbon $bdCabin): string
    {
        $expected = self::cong($bdCabin, -1);

        return self::neuKhop($d, $expected, 'BD cabin − 1 ngày');
    }

    private static function chuThichKiemTra(Carbon $d, ?Carbon $theoMau): string
    {
        if ($theoMau && self::cungNgay($d, $theoMau)) {
            return 'theo mẫu offset';
        }
        if ($theoMau && $d->greaterThan($theoMau)) {
            return 'max lịch GV (muộn hơn offset)';
        }

        return '';
    }

    private static function chuThichLichGvXe(string $nguon): string
    {
        if (str_contains($nguon, 'lich_xe_tap') && str_contains($nguon, 'offset')) {
            return 'lịch xe (GhiChu) ưu tiên, còn lại offset';
        }
        if (str_contains($nguon, 'lich_xe_tap')) {
            return 'lịch xe-tập (GhiChu)';
        }
        if (str_contains($nguon, 'lich_giao_vien')) {
            return 'lịch giáo viên (tên môn)';
        }

        return 'lịch GV / xe-tập';
    }

    private static function neuKhop(Carbon $actual, ?Carbon $expected, string $congThuc): string
    {
        if ($expected === null) {
            return '';
        }

        return self::cungNgay($actual, $expected) ? 'công thức: '.$congThuc : '';
    }

    private static function ngayTuChuoi(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function cong(?Carbon $base, int $days): ?Carbon
    {
        return $base?->copy()->addDays($days);
    }

    /** @param  array<string, Carbon|null>  $parsed */
    private static function lay(array $parsed, string $key): ?Carbon
    {
        return $parsed[$key] ?? null;
    }

    private static function cungNgay(Carbon $a, Carbon $b): bool
    {
        return $a->format('Y-m-d') === $b->format('Y-m-d');
    }
}
