<?php

namespace App\Support\PMGPLX;

use Illuminate\Support\Facades\Schema;

/** Cột bảng KhoaHoc khi làm việc với PMGPLX bản cũ (sqlsrv_old). */
final class CopyBanCuColumnLists
{
    /** @var list<string> */
    public const KHOA_HOC_INSERT = [
        'MaKH', 'MaCSDT', 'MaSoGTVT', 'TenKH', 'HangGPLX', 'HangDT', 'SoQD_KhaiGiang',
        'NgayQD_KhaiGiang', 'NgayKG', 'NgayBG', 'MucTieuDT', 'NgayThi', 'NgaySH',
        'TongSoHV', 'SoHVTotNghiep', 'SoHVDuocCapGPLX', 'ThoiGianDT', 'SoNgayOnKT',
        'SoNgayThucHoc', 'SoNgayNghiLe', 'TongSoNgay', 'GhiChu', 'TrangThai',
        'NguoiTao', 'NguoiSua', 'NgayTao', 'NgaySua', 'TT_Xuly',
    ];

    /** @return list<string> */
    public static function khoaHocOnOld(): array
    {
        if (! Schema::connection('sqlsrv_old')->hasTable('KhoaHoc')) {
            return [];
        }

        $onTable = array_flip(Schema::connection('sqlsrv_old')->getColumnListing('KhoaHoc'));

        return array_values(array_filter(
            self::KHOA_HOC_INSERT,
            static fn (string $col): bool => isset($onTable[$col])
        ));
    }
}