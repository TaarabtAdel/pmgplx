<?php

/**
 * Danh mục mọi route xuất file QLĐT Ô TÔ (đồng bộ vào QldtOtoBieuMauXuat).
 * ten_route: tên route đầy đủ (tiền tố qldtoto.).
 */
return [
    ['ten_route' => 'qldtoto.bao-cao-dang-ky.export', 'ma' => 'bao-cao-dang-ky', 'ten' => 'Báo cáo đăng ký khoá đào tạo', 'loai' => 'Word', 'thu_tu' => 10],
    ['ten_route' => 'qldtoto.hop-dong-dao-tao.export', 'ma' => 'hop-dong-dao-tao', 'ten' => 'Hợp đồng đào tạo lái xe', 'loai' => 'Word', 'thu_tu' => 20],
    ['ten_route' => 'qldtoto.danh-sach-hoc-sinh-lai-xe.export', 'ma' => 'danh-sach-hoc-sinh-lai-xe', 'ten' => 'Danh sách học sinh lái xe', 'loai' => 'Excel', 'thu_tu' => 30],
    ['ten_route' => 'qldtoto.lich-du-kien.ly-thuyet.export', 'ma' => 'lich-hoc-lt-th', 'ten' => 'Lịch dự kiến lý thuyết (LT & TH)', 'loai' => 'Excel', 'thu_tu' => 40],
    ['ten_route' => 'qldtoto.lich-hoc-lt-th-tung-giao-vien.export-excel', 'ma' => 'lich-hoc-lt-th-gv', 'ten' => 'Lịch giáo viên PMGPLX (mẫu nhập lịch)', 'loai' => 'Excel', 'thu_tu' => 41],
    ['ten_route' => 'qldtoto.quyet-dinh-dieu-dong-xe.export', 'ma' => 'quyet-dinh-dieu-dong-xe', 'ten' => 'Quyết định điều động xe', 'loai' => 'Word', 'thu_tu' => 50],
    ['ten_route' => 'qldtoto.bao-cao-de-nghi-sat-hach.export', 'ma' => 'bao-cao-de-nghi-sat-hach', 'ten' => 'Báo cáo đề nghị sát hạch', 'loai' => 'Word', 'thu_tu' => 60],
    ['ten_route' => 'qldtoto.thong-bao-tap-trung-thuc-hanh.export', 'ma' => 'thong-bao-tap-trung-th', 'ten' => 'Thông báo tập trung thực hành', 'loai' => 'Word', 'thu_tu' => 70],
    ['ten_route' => 'qldtoto.quyet-dinh-hoan-thanh-khoa-hoc.export', 'ma' => 'quyet-dinh-hoan-thanh', 'ten' => 'Quyết định hoàn thành khoá học', 'loai' => 'ZIP (Word + Excel)', 'thu_tu' => 80],
    ['ten_route' => 'qldtoto.thong-bao-kiem-tra-ket-thuc-mon.export', 'ma' => 'thong-bao-kiem-tra-ktm', 'ten' => 'Thông báo kiểm tra kết thúc môn', 'loai' => 'Word', 'thu_tu' => 90],
    ['ten_route' => 'qldtoto.thanh-lap-hoi-dong-xet-ktm.export-quyet-dinh', 'ma' => 'thanh-lap-hoi-dong-ktm', 'ten' => 'Thành lập hội đồng xét hoàn thành — Quyết định', 'loai' => 'Word', 'thu_tu' => 100],
    ['ten_route' => 'qldtoto.thanh-lap-hoi-dong-xet-ktm.export-danh-sach', 'ma' => 'thanh-lap-hoi-dong-ktm', 'ten' => 'Thành lập hội đồng xét hoàn thành — Danh sách', 'loai' => 'Excel', 'thu_tu' => 101],
    ['ten_route' => 'qldtoto.thanh-lap-hoi-dong-xet-ktm.export-ca-hai', 'ma' => 'thanh-lap-hoi-dong-ktm', 'ten' => 'Thành lập hội đồng xét hoàn thành — ZIP', 'loai' => 'ZIP', 'thu_tu' => 102],
    ['ten_route' => 'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-quyet-dinh', 'ma' => 'qd-to-chuc-ktm-bb', 'ten' => 'QĐ tổ chức KTM + BB phân công — Quyết định', 'loai' => 'Word', 'thu_tu' => 110],
    ['ten_route' => 'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-bien-ban', 'ma' => 'qd-to-chuc-ktm-bb', 'ten' => 'QĐ tổ chức KTM + BB phân công — Biên bản', 'loai' => 'Word', 'thu_tu' => 111],
    ['ten_route' => 'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-ca-hai', 'ma' => 'qd-to-chuc-ktm-bb', 'ten' => 'QĐ tổ chức KTM + BB phân công — ZIP', 'loai' => 'ZIP', 'thu_tu' => 112],
    ['ten_route' => 'qldtoto.quyet-dinh-to-chuc-ktm-bb.export-danh-sach-hoc-vien', 'ma' => 'qd-to-chuc-ktm-bb', 'ten' => 'QĐ tổ chức KTM — Danh sách học viên kèm theo', 'loai' => 'Excel', 'thu_tu' => 113],
    ['ten_route' => 'qldtoto.bien-ban-phan-cong-to-cham.export', 'ma' => 'bien-ban-phan-cong-to-cham', 'ten' => 'Biên bản phân công tổ chấm', 'loai' => 'Word', 'thu_tu' => 120],
    ['ten_route' => 'qldtoto.quyet-dinh-bao-luu.export', 'ma' => 'quyet-dinh-bao-luu', 'ten' => 'Quyết định bảo lưu', 'loai' => 'Word', 'thu_tu' => 130],
    ['ten_route' => 'qldtoto.quyet-dinh-cho-thoi-hoc.export', 'ma' => 'quyet-dinh-thoi-hoc', 'ten' => 'Quyết định cho thôi học', 'loai' => 'Word', 'thu_tu' => 140],
    ['ten_route' => 'qldtoto.bao-cao-phien-hoc-loi.export-word', 'ma' => 'bao-cao-phien-hoc-loi', 'ten' => 'Báo cáo phiên học lỗi — Word', 'loai' => 'Word', 'thu_tu' => 148],
    ['ten_route' => 'qldtoto.bao-cao-phien-hoc-loi.export-danh-sach', 'ma' => 'bao-cao-phien-hoc-loi', 'ten' => 'Báo cáo phiên học lỗi — Danh sách', 'loai' => 'Excel', 'thu_tu' => 149],
    ['ten_route' => 'qldtoto.bao-cao-phien-hoc-loi.export-ca-hai', 'ma' => 'bao-cao-phien-hoc-loi', 'ten' => 'Báo cáo phiên học lỗi — ZIP', 'loai' => 'ZIP', 'thu_tu' => 147],
    ['ten_route' => 'qldtoto.quyet-dinh-huy-phien.export-word', 'ma' => 'quyet-dinh-huy-phien', 'ten' => 'Quyết định huỷ phiên — Word', 'loai' => 'Word', 'thu_tu' => 150],
    ['ten_route' => 'qldtoto.quyet-dinh-huy-phien.export-danh-sach', 'ma' => 'quyet-dinh-huy-phien', 'ten' => 'Quyết định huỷ phiên — Danh sách', 'loai' => 'Excel', 'thu_tu' => 151],
    ['ten_route' => 'qldtoto.quyet-dinh-huy-phien.export-ca-hai', 'ma' => 'quyet-dinh-huy-phien', 'ten' => 'Quyết định huỷ phiên — ZIP', 'loai' => 'ZIP', 'thu_tu' => 152],
    ['ten_route' => 'qldtoto.quyet-dinh-doi-xe.export', 'ma' => 'quyet-dinh-doi-xe', 'ten' => 'Quyết định đổi xe', 'loai' => 'Word', 'thu_tu' => 160],
    ['ten_route' => 'qldtoto.quyet-dinh-doi-giao-vien.export', 'ma' => 'quyet-dinh-doi-gv', 'ten' => 'Quyết định đổi giáo viên', 'loai' => 'Word', 'thu_tu' => 170],
    ['ten_route' => 'qldtoto.so-theo-doi-ket-qua-hoc-tap.export', 'ma' => 'so-theo-doi-kq', 'ten' => 'Sổ theo dõi kết quả học tập', 'loai' => 'Word', 'thu_tu' => 180],
    ['ten_route' => 'qldtoto.so-tay-giao-vien-ly-thuyet.export', 'ma' => 'so-tay-gv-lt', 'ten' => 'Sổ tay giáo viên lý thuyết', 'loai' => 'Word', 'thu_tu' => 190],
    ['ten_route' => 'qldtoto.so-tay-giao-vien-thuc-hanh.export', 'ma' => 'so-tay-gv-th', 'ten' => 'Sổ tay giáo viên thực hành', 'loai' => 'Word', 'thu_tu' => 200],
    ['ten_route' => 'qldtoto.so-phan-cong-gv-xe-tap-lai.export', 'ma' => 'so-phan-cong-gv-xe', 'ten' => 'Sổ phân công GV — xe tập lái', 'loai' => 'Word', 'thu_tu' => 210],
    ['ten_route' => 'qldtoto.bien-ban-pho-bien-noi-quy.export', 'ma' => 'bien-ban-pho-bien-nq', 'ten' => 'Biên bản phổ biến nội quy', 'loai' => 'Word', 'thu_tu' => 220],
    ['ten_route' => 'qldtoto.quyet-dinh-thanh-lap-lop-hoc.export', 'ma' => 'qd-thanh-lap-lop', 'ten' => 'Quyết định thành lập lớp học', 'loai' => 'Word', 'thu_tu' => 230],
    ['ten_route' => 'qldtoto.lich-du-kien.thuc-hanh.export-excel', 'ma' => 'lich-du-kien-thuc-hanh', 'ten' => 'Lịch dự kiến thực hành (mẫu nhập lịch)', 'loai' => 'Excel', 'thu_tu' => 240],
    ['ten_route' => 'qldtoto.lam-lich-chinh-thuc.export-excel', 'ma' => 'lich-chinh-thuc-th', 'ten' => 'Lịch chính thức thực hành (mẫu nhập lịch)', 'loai' => 'Excel', 'thu_tu' => 250],
];