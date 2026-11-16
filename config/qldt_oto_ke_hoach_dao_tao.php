<?php

use App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay;

/** Mẫu & tham số lưới «Lịch học toàn khóa» — ký tự ô (không shape). */
return [
    LichHocLyThuyetThucHanhLichNgay::HANG_C => [
        'template' => 'ke-hoach-dao-tao-c.docx',
        'so_cot_tuan' => 10,
        'hang_hien_thi' => 'C1',
        'thang_mau' => 'T6-T7/2026, T8-T9/2026',
        'ghi_chu_lt' => 'Học viên học lý thuyết tự học có hướng dẫn từ ngày %s – %s. Trong thời gian nêu trên học viên nào không đủ thời gian học lý thuyết theo quy định sẽ học vào khoảng thời gian dự phòng từ ngày %s – %s ngoài thời gian học viên học thực hành trên xe tập lái.',
        'ngay_lt_tu' => 'ngay_khai_giang',
        'ngay_lt_den' => 'ngay_bd_thuc_hanh', // −1 ngày khi in
        'ngay_dp_tu' => 'ngay_bo_sung_dat',
        'ngay_dp_den' => 'ngay_kt_bo_sung_dat',
    ],
    LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN => [
        'template' => 'ke-hoach-dao-tao-b-so-san.docx',
        'so_cot_tuan' => 9,
        'hang_hien_thi' => null,
        'thang_mau' => null,
        'ghi_chu_lt' => 'Học viên học lý thuyết tự học có hướng dẫn từ ngày %s – %s. Trong thời gian nêu trên học viên nào không đủ thời gian học lý thuyết theo quy định sẽ học vào khoảng thời gian dự phòng từ ngày %s – %s ngoài thời gian học viên học thực hành trên xe tập lái.',
        'ngay_lt_tu' => 'ngay_khai_giang',
        'ngay_lt_den' => 'ngay_bd_thuc_hanh',
        'ngay_dp_tu' => 'ngay_bo_sung_dat',
        'ngay_dp_den' => 'ngay_kt_bo_sung_dat',
    ],
    LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG => [
        'template' => 'ke-hoach-dao-tao-so-tu-dong.docx',
        'so_cot_tuan' => 9,
        'hang_hien_thi' => 'B (STĐ)',
        'thang_mau' => null,
        'ghi_chu_lt' => 'Học viên học lý thuyết tự học có hướng dẫn từ ngày %s – %s. Trong thời gian nêu trên học viên nào không đủ thời gian học lý thuyết theo quy định sẽ học vào khoảng thời gian dự phòng từ ngày %s – %s ngoài thời gian học viên học lý thuyết tập trung tại trung tâm và thực hành trên xe tập lái.',
        'ngay_lt_tu' => 'ngay_khai_giang',
        'ngay_lt_den' => 'ngay_bd_thuc_hanh',
        'ngay_dp_tu' => 'ngay_bo_sung_dat',
        'ngay_dp_den' => 'ngay_kt_bo_sung_dat',
    ],
];