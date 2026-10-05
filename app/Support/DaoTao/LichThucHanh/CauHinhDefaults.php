<?php

namespace App\Support\DaoTao\LichThucHanh;

use App\Models\DaoTao\DatDieuKienDat;

final class CauHinhDefaults
{
    /**
     * Cấu hình mặc định (hạng B, 8h/ngày) — mọi con số có thể sửa trên màn hình.
     *
     * @return array<string, mixed>
     */
    public static function khung(string $maKhoaHoc = '', string $hang = 'B'): array
    {
        $dieuKien = DatDieuKienDat::forHang($hang);

        return [
            'ma_khoa' => $maKhoaHoc,
            'hang_dao_tao' => $hang,
            'ngay_khai_giang' => '',
            'ngay_ket_thuc_du_kien' => '',
            'he_so_quy_doi' => 2,
            'gio_day_moi_ngay' => 8,
            'gio_day_moi_ngay_toi_da' => 10,
            'khung_xe' => ['mo' => '05:59', 'dong' => '22:00'],
            'dieu_kien_dat_hang' => $dieuKien ? [
                'hang' => $dieuKien->Hang,
                'tap_lai_ban_dem_gio' => (float) $dieuKien->TapLaiBanDemGio,
                'xe_so_tu_dong_gio' => (float) $dieuKien->XeSoTuDongGio,
                'gio_cao_toc_gio' => (float) $dieuKien->GioCaoTocGio,
                'so_gio_hoc' => (float) $dieuKien->SoGioHoc,
                'tong_quang_duong_km' => (float) $dieuKien->TongQuangDuongKm,
            ] : null,
            'so_hoc_vien_mac_dinh' => 5,
            'cap_xe' => [],
            'xe_tu_dong_chung' => ['bien_so' => '74A-452.04'],
            'xe_bo_sung' => ['bien_so_tu_dong' => '74A-452.04', 'bien_so_san' => ''],
            'nghi_dinh_ky' => [
                'thu_trong_tuan' => '',
                'tu_ngay' => '',
                'den_ngay' => '',
                'ngoai_le_van_day' => [],
                'nghi_bu' => [],
            ],
            'nghi_co_dinh' => [],
            'nghi_rieng' => [],
            'cabin' => ['so_luong' => 2, 'tu' => '07:00', 'den' => '17:00'],
            'xe_tu_dong_khung' => [
                ['tu' => '07:00', 'den' => '12:00'],
                ['tu' => '13:00', 'den' => '18:00'],
            ],
            'doi_vai_phuc_tap_ngay' => 5,
            'chuong_trinh' => self::chuongTrinhHangB(),
            'mau_ca' => MauCa::defaults(),
        ];
    }

    /** Chương trình mặc định hạng B, 5 HV/GV. */
    /** @return list<array<string, mixed>> */
    public static function chuongTrinhHangB(): array
    {
        return [
            ['ma' => 'hinh_dau', 'ten' => 'Hình (đầu khóa)', 'gio' => 32, 'tinh_dat' => false, 'thu_tu' => 10, 'mau' => BaiGiang::HINH],
            ['ma' => 'cabin', 'ten' => 'Cabin', 'gio' => 10, 'tinh_dat' => false, 'thu_tu' => 20, 'mau' => BaiGiang::CABIN],
            ['ma' => 'tu_dong', 'ten' => 'Tự động', 'gio' => 10, 'tinh_dat' => true, 'thu_tu' => 30, 'mau' => BaiGiang::TU_DONG],
            ['ma' => 'phuc_tap', 'ten' => 'Phức tạp (+ ban đêm)', 'gio' => 110, 'tinh_dat' => true, 'thu_tu' => 40, 'mau' => BaiGiang::PHUC_TAP],
            ['ma' => 'doc_qc', 'ten' => 'Dốc/QC', 'gio' => 56, 'tinh_dat' => true, 'thu_tu' => 50, 'mau' => BaiGiang::DOC_QC],
            ['ma' => 'cao_toc', 'ten' => 'Cao tốc', 'gio' => 16, 'tinh_dat' => true, 'thu_tu' => 60, 'mau' => BaiGiang::CAO_TOC, 'lam_tron_nguyen_ngay' => true],
            ['ma' => 'co_tai', 'ten' => 'Có tải', 'gio' => 8, 'tinh_dat' => true, 'thu_tu' => 70, 'mau' => BaiGiang::CO_TAI],
            ['ma' => 'bo_sung', 'ten' => 'Bổ sung', 'gio' => 16, 'tinh_dat' => false, 'thu_tu' => 80, 'mau' => BaiGiang::BO_SUNG],
            ['ma' => 'hinh_cuoi', 'ten' => 'Hình (cuối khóa)', 'gio' => 48, 'tinh_dat' => false, 'thu_tu' => 90, 'mau' => BaiGiang::HINH],
            ['ma' => 'on_stl', 'ten' => 'Ôn luyện STL', 'gio' => 16, 'tinh_dat' => false, 'thu_tu' => 100, 'mau' => BaiGiang::ON_STL],
            ['ma' => 'on_td', 'ten' => 'Ôn luyện TĐ', 'gio' => 16, 'tinh_dat' => false, 'thu_tu' => 110, 'mau' => BaiGiang::ON_TD],
            ['ma' => 'kiem_tra', 'ten' => 'Kiểm tra', 'gio' => 0, 'tinh_dat' => false, 'thu_tu' => 120, 'mau' => BaiGiang::KIEM_TRA, 'theo_ngay' => 1],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function chuongTrinhHangB01(): array
    {
        $items = self::chuongTrinhHangB();
        $filtered = array_values(array_filter($items, fn (array $row): bool => ($row['ma'] ?? '') !== 'tu_dong'));

        return array_map(function (array $row): array {
            if (($row['ma'] ?? '') === 'phuc_tap') {
                $row['gio'] = 90;
            }

            return $row;
        }, $filtered);
    }

    /** @param array<string, mixed> $input */
    public static function merge(array $input, string $maKhoaHoc, string $hang): array
    {
        $base = self::khung($maKhoaHoc, $hang);
        if ($hang === 'B.01' || $hang === 'B01') {
            $base['chuong_trinh'] = self::chuongTrinhHangB01();
        }

        $merged = array_replace_recursive($base, $input);
        $merged['hang_dao_tao'] = $hang;
        $merged['dieu_kien_dat_hang'] = $base['dieu_kien_dat_hang'];

        return $merged;
    }
}
