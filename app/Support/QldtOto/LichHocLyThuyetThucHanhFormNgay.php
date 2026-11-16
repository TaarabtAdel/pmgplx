<?php

namespace App\Support\QldtOto;

/** Các khóa mốc ngày trên form lịch LT+TH. */
final class LichHocLyThuyetThucHanhFormNgay
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
}