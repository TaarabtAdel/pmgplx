<?php

/*
| Menu "QLĐT" trên thanh điều hướng (layout PMGPLX.layouts.quan-ly).
| Nguồn: mindnote "Quản lý đào tạo" — nhánh "Chuẩn bị cho 1 khoá Ô TÔ".
| Nhóm có 'children' mở menu con. Mục lá dùng một trong:
|   'trang' => slug  — trang trắng /qldt-oto/{slug}, route 'qldtoto.{slug}' (routes/qldtoto.php);
|   'route' => tên route có sẵn; 'url' => URL / '#'.
| 'active' (tuỳ chọn): mẫu tên route để tô sáng mục + nhóm cha khi ở trang con.
| 'mo_ta': ghi chú từ mindnote, hiển thị trên trang.
*/

return [
    'tabs' => [
        [
            'title' => 'QLĐT Ô TÔ',
            'children' => [
                [
                    'title' => '1. Chuẩn bị khoá khai giảng',
                    'children' => [
                        [
                            'title' => 'Làm lịch giảng dạy của khoá',
                            'children' => [
                                [
                                    'title' => 'Làm lịch dự kiến',
                                    'children' => [
                                        [
                                            'title' => 'Lý thuyết',
                                            'trang' => 'lich-du-kien/ly-thuyet',
                                        ],
                                        [
                                            'title' => 'Thực hành',
                                            'route' => 'qldtoto.lich-du-kien.thuc-hanh.index',
                                            'active' => 'qldtoto.lich-du-kien.thuc-hanh.*',
                                        ],
                                    ],
                                ],
                                [
                                    'title' => 'Làm lịch chính thức',
                                    'trang' => '1-lam-lich-chinh-thuc',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Hồ sơ quản lý đào tạo',
                            'mo_ta' => 'Khi đến ngày khai giảng (đã đóng dấu báo cáo 1) thì làm',
                            'children' => [
                                [
                                    'title' => 'Báo cáo đăng ký khoá đào tạo lái xe (Báo cáo 1)',
                                    'mo_ta' => 'Nộp cho sở trong 7 ngày làm việc trước, nộp cho công an',
                                    'children' => [
                                        [
                                            'title' => 'Xuất file "Báo cáo đăng ký"',
                                            'trang' => '1-bao-cao-dang-ky',
                                            'route' => 'qldtoto.bao-cao-dang-ky.index',
                                            'active' => 'qldtoto.bao-cao-dang-ky.*',
                                        ],
                                        [
                                            'title' => 'Xuất file "Danh sách học sinh lái xe"',
                                            'mo_ta' => 'Chọn khoá PMGPLX, quyết định kèm theo — xuất Excel theo mẫu QLĐT',
                                            'trang' => '1-danh-sach-hoc-sinh-lai-xe',
                                            'route' => 'qldtoto.danh-sach-hoc-sinh-lai-xe.index',
                                            'active' => 'qldtoto.danh-sach-hoc-sinh-lai-xe.*',
                                        ],
                                        [
                                            'title' => 'Xuất file "Kế hoạch đào tạo"',
                                            'trang' => '1-ke-hoach-dao-tao',
                                        ],
                                    ],
                                ],
                                [
                                    'title' => 'Xuất file "Quyết định thành lập lớp học + danh sách học viên"',
                                    'trang' => '1-quyet-dinh-thanh-lap-lop-hoc-danh-sach-hoc-vien',
                                    'route' => 'qldtoto.quyet-dinh-thanh-lap-lop-hoc.index',
                                    'active' => 'qldtoto.quyet-dinh-thanh-lap-lop-hoc.*',
                                ],
                                [
                                    'title' => 'Xuất file "Biên bản xác nhận phổ biến nội quy quy chế chương trình đào tạo"',
                                    'trang' => '1-bien-ban-xac-nhan-pho-bien-noi-quy-quy-che-chuong-trinh-dao',
                                ],
                                [
                                    'title' => 'Xuất file "Danh sách học viên học tập trung lớp lý thuyết"',
                                    'trang' => '1-danh-sach-hoc-vien-hoc-tap-trung-lop-ly-thuyet',
                                ],
                                [
                                    'title' => 'Xuất file "Lịch học lý thuyết và lịch học thực hành" của từng giáo viên',
                                    'trang' => '1-lich-hoc-ly-thuyet-va-lich-hoc-thuc-hanh-cua-tung-giao-vien',
                                ],
                                [
                                    'title' => 'Xuất file "Quyết định điều động xe" của từng giáo viên',
                                    'trang' => '1-quyet-dinh-dieu-dong-xe-cua-tung-giao-vien',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Hồ sơ đào tạo',
                            'children' => [
                                [
                                    'title' => 'Xuất file "Sổ phân công giáo viên, xe tham gia giảng dạy của khoá"',
                                    'trang' => '1-so-phan-cong-giao-vien-xe-tham-gia-giang-day-cua-khoa',
                                    'route' => 'qldtoto.so-phan-cong-gv-xe-tap-lai.index',
                                    'active' => 'qldtoto.so-phan-cong-gv-xe-tap-lai.*',
                                ],
                                [
                                    'title' => 'Xuất file "Sổ theo dõi kết quả học tập của học viên"',
                                    'trang' => '1-so-theo-doi-ket-qua-hoc-tap-cua-hoc-vien',
                                ],
                                [
                                    'title' => 'Xuất file "Sổ tay các giáo viên lý thuyết"',
                                    'mo_ta' => 'Có quyết định lập lưu trước',
                                    'trang' => '1-so-tay-cac-giao-vien-ly-thuyet',
                                ],
                                [
                                    'title' => 'Xuất file "Sổ tay các giáo viên thực hành"',
                                    'mo_ta' => 'Có quyết định lập lưu trước',
                                    'trang' => '1-so-tay-cac-giao-vien-thuc-hanh',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Hợp đồng đào tạo',
                            'children' => [
                                [
                                    'title' => 'Xuất file "Danh sách lớp học"',
                                    'mo_ta' => 'Chị Chung hoặc Nga',
                                    'trang' => '1-danh-sach-lop-hoc',
                                ],
                                [
                                    'title' => 'Xuất file "Hợp đồng đào tạo lái xe" của từng học viên',
                                    'trang' => '1-hop-dong-dao-tao-lai-xe-cua-tung-hoc-vien',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => '2. Khai giảng xong',
                    'children' => [
                        [
                            'title' => 'Trước khi học lý thuyết',
                            'children' => [
                                [
                                    'title' => 'Xuất file "XML báo cáo 1"',
                                    'mo_ta' => 'Từ phần mềm, gửi cho NHÂN VIÊN LÝ THUYẾT và NHÂN VIÊN DAT',
                                    'trang' => '2-xml-bao-cao-1',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Trước khi học thực hành',
                            'children' => [
                                [
                                    'title' => 'Nhập lịch vào phần mềm',
                                    'trang' => '2-nhap-lich-vao-phan-mem',
                                ],
                                [
                                    'title' => 'Gửi lịch thực hành cho DAT',
                                    'mo_ta' => 'Gửi cho NHÂN VIÊN DAT',
                                    'trang' => '2-gui-lich-thuc-hanh-cho-dat',
                                ],
                                [
                                    'title' => 'Xuất file "Thông báo thời gian tập trung học thực hành lái xe"',
                                    'trang' => '2-thong-bao-thoi-gian-tap-trung-hoc-thuc-hanh-lai-xe',
                                ],
                                [
                                    'title' => 'Xuất file "Bảng tên" để in',
                                    'trang' => '2-bang-ten-de-in',
                                ],
                                [
                                    'title' => 'Gửi sổ tay thực hành cho giáo viên',
                                    'trang' => '2-gui-so-tay-thuc-hanh-cho-giao-vien',
                                ],
                                [
                                    'title' => 'Chuẩn bị thẻ chạy DAT cho Giáo Viên (GV Mới)',
                                    'trang' => '2-chuan-bi-the-chay-dat-cho-giao-vien-gv-moi',
                                ],
                                [
                                    'title' => 'Chuẩn bị thẻ chạy DAT cho Học Viên',
                                    'trang' => '2-chuan-bi-the-chay-dat-cho-hoc-vien',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Làm các quyết định (Nếu có)',
                            'mo_ta' => 'Đổi xe, đổi thầy, điều chỉnh loại phiên, bảo lưu, đình chỉ học',
                            'children' => [
                                [
                                    'title' => 'Xuất file "Quyết định đổi xe"',
                                    'trang' => '2-quyet-dinh-doi-xe',
                                ],
                                [
                                    'title' => 'Xuất file "Quyết định đổi giáo viên"',
                                    'trang' => '2-quyet-dinh-doi-giao-vien',
                                ],
                                [
                                    'title' => 'Xuất file "Quyết đinh bảo lưu"',
                                    'trang' => '2-quyet-dinh-bao-luu',
                                ],
                                [
                                    'title' => 'Xuất file "Quyết định đình chỉ học"',
                                    'trang' => '2-quyet-dinh-dinh-chi-hoc',
                                ],
                                [
                                    'title' => 'Huỷ phiên DAT (nếu có)',
                                    'children' => [
                                        [
                                            'title' => 'Xuất file "Báo cáo phiên học lỗi"',
                                            'mo_ta' => 'Nhân viên DAT làm',
                                            'trang' => '2-bao-cao-phien-hoc-loi',
                                        ],
                                        [
                                            'title' => 'Xuất file "Quyết định huỷ phiên kèm danh sách huỷ phiên"',
                                            'trang' => '2-quyet-dinh-huy-phien-kem-danh-sach-huy-phien',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => 'Sau khi kết thúc học thực hành',
                            'children' => [
                                [
                                    'title' => 'Xuất file "Thông báo thời gian kiểm tra kết thúc môn"',
                                    'trang' => '2-thong-bao-thoi-gian-kiem-tra-ket-thuc-mon',
                                ],
                                [
                                    'title' => 'Xuất file "Quyết định tổ chức kiểm tra kết thúc môn + biên bản phân công kiểm tra kỳ thi"',
                                    'trang' => '2-quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon-bien-ban-phan-cong',
                                ],
                                [
                                    'title' => 'Xuất file "Làm quyết định thành lập hội đồng xét kiểm tra kết thúc môn + danh sách hội đồng kèm theo"',
                                    'trang' => '2-lam-quyet-dinh-thanh-lap-hoi-dong-xet-kiem-tra-ket-thuc-mon',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Sau khi kiểm tra kết thúc khoá',
                            'children' => [
                                [
                                    'title' => 'Nhập điểm, thời gian, quảng đường, kết quả đào tạo từng học viên vào phần mềm',
                                    'children' => [
                                        [
                                            'title' => 'Nhập điểm, thời gian, quảng đường, kết quả đào tạo từng học viên vào phần mềm',
                                            'trang' => '2-nhap-diem-thoi-gian-quang-duong-ket-qua-dao-tao-tung-hoc',
                                        ],
                                        [
                                            'title' => 'Kết xuất file gửi SỞ',
                                            'trang' => '2-ket-xuat-file-gui-so',
                                        ],
                                    ],
                                ],
                                [
                                    'title' => 'Dựa vào file của Nhân, làm danh sách vắng, đạt, trượt + biên bản tổng hợp, biên bản họp kỳ thi kiểm tra kết thúc môn + thi lại',
                                    'trang' => '2-dua-vao-file-cua-nhan-lam-danh-sach-vang-dat-truot-bien-ban',
                                ],
                                [
                                    'title' => 'Xuất file "Biên bản tổng hợp kết quả kỳ kiểm tra kết thúc môn"',
                                    'trang' => '2-bien-ban-tong-hop-ket-qua-ky-kiem-tra-ket-thuc-mon',
                                ],
                                [
                                    'title' => 'Xuất file "Biên bản họp thông qua kết quả kỳ kiểm tra kết thúc môn học lý thuyết thực hành"',
                                    'trang' => '2-bien-ban-hop-thong-qua-ket-qua-ky-kiem-tra-ket-thuc-mon-hoc',
                                ],
                                [
                                    'title' => 'Xuất file "Quyết định hoàn thành của khoá học" + danh sách học viên được tốt nghiệp',
                                    'children' => [
                                        [
                                            'title' => 'Xuất file "Quyết định hoàn thành của khoá học" + danh sách học viên được tốt nghiệp',
                                            'trang' => '2-quyet-dinh-hoan-thanh-cua-khoa-hoc-danh-sach-hoc-vien-duoc',
                                        ],
                                        [
                                            'title' => 'Gửi cho DAT',
                                            'mo_ta' => 'Gửi DANH SÁCH',
                                            'trang' => '2-gui-cho-dat',
                                        ],
                                        [
                                            'title' => 'Gửi cho Cô Nga',
                                            'mo_ta' => 'Vào sổ xét hoàn thành khoá đào tạo',
                                            'trang' => '2-gui-cho-co-nga',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => '3. Sau khi hoàn thành khoá học',
                    'children' => [
                        [
                            'title' => 'Xuất file "Giấy chứng nhận hoàn thành cho học viên"',
                            'mo_ta' => 'Từ phần mềm, sau khi đi ra SỞ',
                            'trang' => '3-giay-chung-nhan-hoan-thanh-cho-hoc-vien',
                        ],
                        [
                            'title' => 'Làm danh sách sát hạch dự kiến',
                            'mo_ta' => 'Dựa vào danh sách hoàn thành + các danh sách vắng thi lại sát hạch kỳ trước ( Gửi ) | Gửi cho Nhung kiểm tra tiền SAU KHI KIỂM TRA XONG thì Nhung gửi lại cho bên QLDT để làm danh sách sát hạch chính thức',
                            'children' => [
                                [
                                    'title' => 'Xuất file "Báo cáo đề nghị tổ chức sát hạch" + Danh sách',
                                    'trang' => '3-bao-cao-de-nghi-to-chuc-sat-hach-danh-sach',
                                ],
                                [
                                    'title' => 'Xuất file "Danh sách thí sinh dự sát hạch"',
                                    'mo_ta' => 'Từ phần mềm CŨ',
                                    'trang' => '3-danh-sach-thi-sinh-du-sat-hach',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Chuẩn bị hồ sơ sát hạch',
                            'mo_ta' => 'XÁC NHẬN LẠI VỚI HẢI CẦN GIẤY TỜ GÌ | Hồ sơ sát hạch',
                            'children' => [
                                [
                                    'title' => '1. Hồ sơ học viên',
                                    'children' => [
                                        [
                                            'title' => 'Đơn đề nghị học',
                                            'trang' => '3-don-de-nghi-hoc',
                                        ],
                                        [
                                            'title' => 'Giấy xác nhận hoàn thành của HV',
                                            'trang' => '3-giay-xac-nhan-hoan-thanh-cua-hv',
                                        ],
                                        [
                                            'title' => 'Giấy khám sức khoẻ của CSYT (nhà nước) còn thời hạn đủ dấu mộc',
                                            'trang' => '3-giay-kham-suc-khoe-cua-csyt-nha-nuoc-con-thoi-han-du-dau-moc',
                                        ],
                                        [
                                            'title' => 'Căn cước',
                                            'trang' => '3-can-cuoc',
                                        ],
                                        [
                                            'title' => 'Giấy phép lái xe máy (nếu có)',
                                            'trang' => '3-giay-phep-lai-xe-may-neu-co',
                                        ],
                                    ],
                                ],
                                [
                                    'title' => '2. Hồ sơ nộp công an',
                                    'children' => [
                                        [
                                            'title' => 'Báo cáo 1 + danh sách + kế hoạch đào tạo',
                                            'trang' => '3-bao-cao-1-danh-sach-ke-hoach-dao-tao',
                                        ],
                                        [
                                            'title' => 'Lịch giảng dạy',
                                            'trang' => '3-lich-giang-day',
                                        ],
                                        [
                                            'title' => 'Quyết định hoàn thành',
                                            'trang' => '3-quyet-dinh-hoan-thanh',
                                        ],
                                        [
                                            'title' => 'Báo cáo 2 + danh sách',
                                            'trang' => '3-bao-cao-2-danh-sach',
                                        ],
                                        [
                                            'title' => 'Xác nhận hoàn thành của sở kèm danh sách',
                                            'trang' => '3-xac-nhan-hoan-thanh-cua-so-kem-danh-sach',
                                        ],
                                        [
                                            'title' => 'Báo cáo hoàn thành khoá đào tạo',
                                            'trang' => '3-bao-cao-hoan-thanh-khoa-dao-tao',
                                        ],
                                        [
                                            'title' => 'Biên bản kiểm tra hồ sơ kỳ sát hạch',
                                            'trang' => '3-bien-ban-kiem-tra-ho-so-ky-sat-hach',
                                        ],
                                        [
                                            'title' => 'Làm danh sách giấy phép lái xe máy của khoá',
                                            'trang' => '3-lam-danh-sach-giay-phep-lai-xe-may-cua-khoa',
                                        ],
                                        [
                                            'title' => 'Danh sách học viên thi lại của kỳ sát hạch',
                                            'trang' => '3-danh-sach-hoc-vien-thi-lai-cua-ky-sat-hach',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => '4. Lập lưu hồ sơ',
                    'children' => [
                        [
                            'title' => 'Lý thuyết học online + sổ theo dõi học lý thuyết',
                            'mo_ta' => 'Nhân viên theo dõi lý thuyết lưu trữ',
                            'trang' => '4-ly-thuyet-hoc-online-so-theo-doi-hoc-ly-thuyet',
                        ],
                        [
                            'title' => 'DAT+CABIN',
                            'mo_ta' => 'Nhân viên quản lý DAT lưu',
                            'trang' => '4-dat-cabin',
                        ],
                        [
                            'title' => 'Hồ sơ quản lý đào tạo',
                            'mo_ta' => 'Tạo check list các file',
                            'children' => [
                                [
                                    'title' => 'Báo cáo 1 + danh sách + kế hoạch',
                                    'trang' => '4-bao-cao-1-danh-sach-ke-hoach',
                                ],
                                [
                                    'title' => 'Quyết định thành lập khoá học + danh sách',
                                    'trang' => '4-quyet-dinh-thanh-lap-khoa-hoc-danh-sach',
                                ],
                                [
                                    'title' => 'Lịch giảng dạy',
                                    'trang' => '4-lich-giang-day',
                                ],
                                [
                                    'title' => 'Quyết định điều động xe',
                                    'trang' => '4-quyet-dinh-dieu-dong-xe',
                                ],
                                [
                                    'title' => 'Thông báo tập trung học thực hành',
                                    'trang' => '4-thong-bao-tap-trung-hoc-thuc-hanh',
                                ],
                                [
                                    'title' => 'Quyết định tạm thời',
                                    'mo_ta' => 'Đã ghi chú ở trên, nếu có thì lưu',
                                    'trang' => '4-quyet-dinh-tam-thoi',
                                ],
                                [
                                    'title' => 'Thông báo tổ chức kiểm tra kết thúc môn',
                                    'trang' => '4-thong-bao-to-chuc-kiem-tra-ket-thuc-mon',
                                ],
                                [
                                    'title' => 'Quyết định tổ chức kiểm tra + danh sách + biên bản phân công',
                                    'trang' => '4-quyet-dinh-to-chuc-kiem-tra-danh-sach-bien-ban-phan-cong',
                                ],
                                [
                                    'title' => 'Quyết định thành lập hội đồng xét kiểm tra kết thúc môn + danh sách',
                                    'trang' => '4-quyet-dinh-thanh-lap-hoi-dong-xet-kiem-tra-ket-thuc-mon-danh',
                                ],
                                [
                                    'title' => 'Danh sách vắng, đạt, trượt, biên bản chi tiết, biên bản chung ngày kiểm tra kết thúc môn + danh sách vắng đạt trượt ,biên bản chi tiết, biên bản chung ngày kiểm tra lại. Biên bản tổng hợp, biên bản họp',
                                    'trang' => '4-danh-sach-vang-dat-truot-bien-ban-chi-tiet-bien-ban-chung',
                                ],
                                [
                                    'title' => 'Quyết định hoàn thành + danh sách',
                                    'trang' => '4-quyet-dinh-hoan-thanh-danh-sach',
                                ],
                            ],
                        ],
                        [
                            'title' => 'Hồ sơ đào tạo',
                            'children' => [
                                [
                                    'title' => 'Sổ sách',
                                    'children' => [
                                        [
                                            'title' => 'Sổ sách mình lưu',
                                            'children' => [
                                                [
                                                    'title' => 'Sổ phân công giáo viên giảng dạy',
                                                    'trang' => '4-so-phan-cong-giao-vien-giang-day',
                                                ],
                                                [
                                                    'title' => 'Sổ kết quả hoàn thành',
                                                    'trang' => '4-so-ket-qua-hoan-thanh',
                                                ],
                                            ],
                                        ],
                                        [
                                            'title' => 'Sổ sách của phòng thực hành đưa xuống',
                                            'children' => [
                                                [
                                                    'title' => 'Sổ theo dõi quảng đường thời gian trong sân tập lái',
                                                    'trang' => '4-so-theo-doi-quang-duong-thoi-gian-trong-san-tap-lai',
                                                ],
                                                [
                                                    'title' => 'Sổ tay giáo viên',
                                                    'trang' => '4-so-tay-giao-vien',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'title' => 'Bài kiểm tra kết thúc môn',
                                    'mo_ta' => 'Của học viên',
                                    'trang' => '4-bai-kiem-tra-ket-thuc-mon',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
