<?php

/**
 * Trang QLĐT Ô TÔ (tên route) → các route xuất file dùng chung khối lịch sử accordion.
 */
return [
    'qldtoto.bao-cao-dang-ky.index' => ['qldtoto.bao-cao-dang-ky.export'],
    'qldtoto.hop-dong-dao-tao.index' => ['qldtoto.hop-dong-dao-tao.export'],
    'qldtoto.danh-sach-hoc-sinh-lai-xe.index' => ['qldtoto.danh-sach-hoc-sinh-lai-xe.export'],
    'qldtoto.lich-du-kien.ly-thuyet.index' => ['qldtoto.lich-du-kien.ly-thuyet.export'],
    'qldtoto.quyet-dinh-dieu-dong-xe.index' => ['qldtoto.quyet-dinh-dieu-dong-xe.export'],
    'qldtoto.quyet-dinh-dieu-dong-xe.preview' => ['qldtoto.quyet-dinh-dieu-dong-xe.export'],
    'qldtoto.bao-cao-de-nghi-sat-hach.index' => ['qldtoto.bao-cao-de-nghi-sat-hach.export'],
    'qldtoto.thong-bao-tap-trung-thuc-hanh.index' => ['qldtoto.thong-bao-tap-trung-thuc-hanh.export'],
    'qldtoto.quyet-dinh-hoan-thanh-khoa-hoc.index' => ['qldtoto.quyet-dinh-hoan-thanh-khoa-hoc.export'],
    'qldtoto.thong-bao-kiem-tra-ket-thuc-mon.index' => ['qldtoto.thong-bao-kiem-tra-ket-thuc-mon.export'],
    'qldtoto.thanh-lap-hoi-dong-xet-ktm.index' => [
        'qldtoto.thanh-lap-hoi-dong-xet-ktm.export-quyet-dinh',
        'qldtoto.thanh-lap-hoi-dong-xet-ktm.export-danh-sach',
        'qldtoto.thanh-lap-hoi-dong-xet-ktm.export-ca-hai',
    ],
    'qldtoto.quyet-dinh-to-chuc-ktm-bb.index' => [
        'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-quyet-dinh',
        'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-bien-ban',
        'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-ca-hai',
    ],
    'qldtoto.bien-ban-phan-cong-to-cham.index' => ['qldtoto.bien-ban-phan-cong-to-cham.export'],
    'qldtoto.quyet-dinh-bao-luu.index' => ['qldtoto.quyet-dinh-bao-luu.export'],
    'qldtoto.quyet-dinh-cho-thoi-hoc.index' => ['qldtoto.quyet-dinh-cho-thoi-hoc.export'],
    'qldtoto.bao-cao-phien-hoc-loi.index' => [
        'qldtoto.bao-cao-phien-hoc-loi.export-word',
        'qldtoto.bao-cao-phien-hoc-loi.export-danh-sach',
        'qldtoto.bao-cao-phien-hoc-loi.export-ca-hai',
    ],
    'qldtoto.quyet-dinh-huy-phien.index' => [
        'qldtoto.quyet-dinh-huy-phien.export-word',
        'qldtoto.quyet-dinh-huy-phien.export-danh-sach',
        'qldtoto.quyet-dinh-huy-phien.export-ca-hai',
    ],
    'qldtoto.quyet-dinh-doi-xe.index' => ['qldtoto.quyet-dinh-doi-xe.export'],
    'qldtoto.quyet-dinh-doi-xe.preview' => ['qldtoto.quyet-dinh-doi-xe.export'],
    'qldtoto.quyet-dinh-doi-giao-vien.index' => ['qldtoto.quyet-dinh-doi-giao-vien.export'],
    'qldtoto.quyet-dinh-doi-giao-vien.preview' => ['qldtoto.quyet-dinh-doi-giao-vien.export'],
    'qldtoto.so-theo-doi-ket-qua-hoc-tap.index' => ['qldtoto.so-theo-doi-ket-qua-hoc-tap.export'],
    'qldtoto.so-tay-giao-vien-ly-thuyet.index' => ['qldtoto.so-tay-giao-vien-ly-thuyet.export'],
    'qldtoto.so-tay-giao-vien-thuc-hanh.index' => ['qldtoto.so-tay-giao-vien-thuc-hanh.export'],
    'qldtoto.so-phan-cong-gv-xe-tap-lai.index' => ['qldtoto.so-phan-cong-gv-xe-tap-lai.export'],
    'qldtoto.so-phan-cong-gv-xe-tap-lai.preview' => ['qldtoto.so-phan-cong-gv-xe-tap-lai.export'],
    'qldtoto.bien-ban-pho-bien-noi-quy.index' => ['qldtoto.bien-ban-pho-bien-noi-quy.export'],
    'qldtoto.quyet-dinh-thanh-lap-lop-hoc.index' => ['qldtoto.quyet-dinh-thanh-lap-lop-hoc.export'],
    'qldtoto.lich-du-kien.thuc-hanh.index' => ['qldtoto.lich-du-kien.thuc-hanh.export-excel'],
    'qldtoto.lich-du-kien.thuc-hanh.edit' => ['qldtoto.lich-du-kien.thuc-hanh.export-excel'],
    'qldtoto.lich-du-kien.thuc-hanh.create' => ['qldtoto.lich-du-kien.thuc-hanh.export-excel'],
    'qldtoto.lam-lich-chinh-thuc.index' => ['qldtoto.lam-lich-chinh-thuc.export-excel'],
    'qldtoto.lam-lich-chinh-thuc.show' => ['qldtoto.lam-lich-chinh-thuc.export-excel'],
];