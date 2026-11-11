<?php

use App\Http\Controllers\QldtOto\BaoCaoDangKyController;
use App\Http\Controllers\QldtOto\BienBanXacNhanPhoBienNoiQuyController;
use App\Http\Controllers\QldtOto\BaoCaoDeNghiSatHachController;
use App\Http\Controllers\QldtOto\BienBanPhanCongToChamController;
use App\Http\Controllers\QldtOto\DanhSachHocSinhLaiXeController;
use App\Http\Controllers\QldtOto\HopDongDaoTaoLaiXeController;
use App\Http\Controllers\QldtOto\QuyetDinhThanhLapLopHocController;
use App\Http\Controllers\QldtOto\QuyetDinhThoiHocController;
use App\Http\Controllers\QldtOto\QuyetDinhToChucKiemTraBbPhanCongController;
use App\Http\Controllers\QldtOto\SoPhanCongGvXeTapLaiController;
use App\Http\Controllers\QldtOto\QuyetDinhBaoLuuController;
use App\Http\Controllers\QldtOto\QuyetDinhDieuDongXeController;
use App\Http\Controllers\QldtOto\QuyetDinhDoiGiaoVienController;
use App\Http\Controllers\QldtOto\QuyetDinhDoiXeController;
use App\Http\Controllers\QldtOto\QuyetDinhHoanThanhKhoaHocController;
use App\Http\Controllers\QldtOto\QuyetDinhHuyPhienController;
use App\Http\Controllers\QldtOto\SoTheoDoiKetQuaHocTapController;
use App\Http\Controllers\QldtOto\ThanhLapHoiDongXetKtmController;
use App\Http\Controllers\QldtOto\CauHinhBaiDayController;
use App\Http\Controllers\QldtOto\LichDuKien\ThucHanhController;
use App\Http\Controllers\QldtOto\ThongBaoKiemTraKetThucMonController;
use App\Http\Controllers\QldtOto\ThongBaoTapTrungThucHanhController;
use App\Http\Controllers\QldtOto\TrangController;
use App\Models\QldtOto\BaiDay;
use App\Support\MenuQldt;
use Illuminate\Support\Facades\Route;

/*
| QLĐT Ô TÔ.
| Màn đã có chức năng khai báo riêng bên dưới. Các mục 'trang' còn lại trong config/menu_qldt.php
| là trang trắng: /qldt-oto/{slug}, route 'qldtoto.{slug}' ('/' trong slug đổi thành '.').
| Làm màn thật cho một mục: khai báo route riêng ở đây và đổi mục đó trong config sang 'route'.
*/

Route::prefix('qldt-oto')->name('qldtoto.')->group(function () {
    Route::get('/', [TrangController::class, 'index'])->name('index');

    $hangBaiDay = array_keys(BaiDay::HANG);
    $caBaiDay = array_keys(BaiDay::CA);
    Route::get('/cau-hinh-bai-day/{hang?}/{ca?}', [CauHinhBaiDayController::class, 'index'])
        ->whereIn('hang', $hangBaiDay)
        ->whereIn('ca', $caBaiDay)
        ->name('cau-hinh-bai-day.index');
    Route::put('/cau-hinh-bai-day/{hang}/{ca}', [CauHinhBaiDayController::class, 'update'])
        ->whereIn('hang', $hangBaiDay)
        ->whereIn('ca', $caBaiDay)
        ->name('cau-hinh-bai-day.update');
    Route::post('/cau-hinh-bai-day/{hang}/{ca}/bai-hoc', [CauHinhBaiDayController::class, 'themBaiHoc'])
        ->whereIn('hang', $hangBaiDay)
        ->whereIn('ca', $caBaiDay)
        ->name('cau-hinh-bai-day.bai-hoc.store');
    Route::put('/cau-hinh-bai-day/{hang}/{ca}/bai-hoc/{baiHoc}', [CauHinhBaiDayController::class, 'suaBaiHoc'])
        ->whereIn('hang', $hangBaiDay)
        ->whereIn('ca', $caBaiDay)
        ->whereNumber('baiHoc')
        ->name('cau-hinh-bai-day.bai-hoc.update');
    Route::delete('/cau-hinh-bai-day/{hang}/{ca}/bai-hoc/{baiHoc}', [CauHinhBaiDayController::class, 'xoaBaiHoc'])
        ->whereIn('hang', $hangBaiDay)
        ->whereIn('ca', $caBaiDay)
        ->whereNumber('baiHoc')
        ->name('cau-hinh-bai-day.bai-hoc.destroy');

    Route::get('/1-bao-cao-dang-ky', [BaoCaoDangKyController::class, 'index'])->name('bao-cao-dang-ky.index');
    Route::post('/1-bao-cao-dang-ky/xuat', [BaoCaoDangKyController::class, 'export'])->name('bao-cao-dang-ky.export');
    Route::get('/1-bao-cao-dang-ky/khoa/{maKh}', [BaoCaoDangKyController::class, 'khoaJson'])->name('bao-cao-dang-ky.khoa');

    $slugHopDong = '1-hop-dong-dao-tao-lai-xe-cua-tung-hoc-vien';
    Route::get('/'.$slugHopDong, [HopDongDaoTaoLaiXeController::class, 'index'])->name('hop-dong-dao-tao.index');
    Route::post('/'.$slugHopDong.'/xuat', [HopDongDaoTaoLaiXeController::class, 'export'])->name('hop-dong-dao-tao.export');

    $slugDanhSachHv = '1-danh-sach-hoc-sinh-lai-xe';
    Route::get('/'.$slugDanhSachHv, [DanhSachHocSinhLaiXeController::class, 'index'])->name('danh-sach-hoc-sinh-lai-xe.index');
    Route::post('/'.$slugDanhSachHv.'/xuat', [DanhSachHocSinhLaiXeController::class, 'export'])->name('danh-sach-hoc-sinh-lai-xe.export');
    Route::get('/'.$slugDanhSachHv.'/khoa/{maKh}', [DanhSachHocSinhLaiXeController::class, 'khoaJson'])->name('danh-sach-hoc-sinh-lai-xe.khoa');

    $slugDieuDongXe = '1-quyet-dinh-dieu-dong-xe-cua-tung-giao-vien';
    Route::get('/'.$slugDieuDongXe, [QuyetDinhDieuDongXeController::class, 'index'])->name('quyet-dinh-dieu-dong-xe.index');
    Route::post('/'.$slugDieuDongXe.'/xem-truoc', [QuyetDinhDieuDongXeController::class, 'previewStore'])->name('quyet-dinh-dieu-dong-xe.preview.store');
    Route::get('/'.$slugDieuDongXe.'/xem-truoc', [QuyetDinhDieuDongXeController::class, 'preview'])->name('quyet-dinh-dieu-dong-xe.preview');
    Route::get('/'.$slugDieuDongXe.'/huy', [QuyetDinhDieuDongXeController::class, 'cancel'])->name('quyet-dinh-dieu-dong-xe.cancel');
    Route::get('/'.$slugDieuDongXe.'/xuat-word', [QuyetDinhDieuDongXeController::class, 'export'])->name('quyet-dinh-dieu-dong-xe.export');

    $slugBcSatHach = '3-bao-cao-de-nghi-to-chuc-sat-hach-danh-sach';
    Route::get('/'.$slugBcSatHach, [BaoCaoDeNghiSatHachController::class, 'index'])->name('bao-cao-de-nghi-sat-hach.index');
    Route::post('/'.$slugBcSatHach.'/xuat', [BaoCaoDeNghiSatHachController::class, 'export'])->name('bao-cao-de-nghi-sat-hach.export');

    $slugTbTapTrung = '2-thong-bao-thoi-gian-tap-trung-hoc-thuc-hanh-lai-xe';
    Route::get('/'.$slugTbTapTrung, [ThongBaoTapTrungThucHanhController::class, 'index'])->name('thong-bao-tap-trung-thuc-hanh.index');
    Route::post('/'.$slugTbTapTrung.'/xuat', [ThongBaoTapTrungThucHanhController::class, 'export'])->name('thong-bao-tap-trung-thuc-hanh.export');

    $slugHoanThanh = '2-quyet-dinh-hoan-thanh-cua-khoa-hoc-danh-sach-hoc-vien-duoc';
    Route::get('/'.$slugHoanThanh, [QuyetDinhHoanThanhKhoaHocController::class, 'index'])->name('quyet-dinh-hoan-thanh-khoa-hoc.index');
    Route::post('/'.$slugHoanThanh.'/xuat', [QuyetDinhHoanThanhKhoaHocController::class, 'export'])->name('quyet-dinh-hoan-thanh-khoa-hoc.export');

    $slugTbKtm = '2-thong-bao-thoi-gian-kiem-tra-ket-thuc-mon';
    Route::get('/'.$slugTbKtm, [ThongBaoKiemTraKetThucMonController::class, 'index'])->name('thong-bao-kiem-tra-ket-thuc-mon.index');
    Route::get('/'.$slugTbKtm.'/tim-hoc-vien', [ThongBaoKiemTraKetThucMonController::class, 'timHocVien'])->name('thong-bao-kiem-tra-ket-thuc-mon.tim-hoc-vien');
    Route::post('/'.$slugTbKtm.'/xuat', [ThongBaoKiemTraKetThucMonController::class, 'export'])->name('thong-bao-kiem-tra-ket-thuc-mon.export');

    $slugHoiDong = '2-lam-quyet-dinh-thanh-lap-hoi-dong-xet-kiem-tra-ket-thuc-mon';
    Route::get('/'.$slugHoiDong, [ThanhLapHoiDongXetKtmController::class, 'index'])->name('thanh-lap-hoi-dong-xet-ktm.index');
    Route::post('/'.$slugHoiDong.'/xuat-quyet-dinh', [ThanhLapHoiDongXetKtmController::class, 'exportQuyetDinh'])->name('thanh-lap-hoi-dong-xet-ktm.export-quyet-dinh');
    Route::post('/'.$slugHoiDong.'/xuat-danh-sach', [ThanhLapHoiDongXetKtmController::class, 'exportDanhSach'])->name('thanh-lap-hoi-dong-xet-ktm.export-danh-sach');
    Route::post('/'.$slugHoiDong.'/xuat-ca-hai', [ThanhLapHoiDongXetKtmController::class, 'exportCaHai'])->name('thanh-lap-hoi-dong-xet-ktm.export-ca-hai');

    $slugQdBbKtm = '2-quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon-bien-ban-phan-cong';
    Route::get('/'.$slugQdBbKtm, [QuyetDinhToChucKiemTraBbPhanCongController::class, 'index'])->name('quyet-dinh-to-chuc-ktm-bb.index');
    Route::post('/'.$slugQdBbKtm.'/xuat-quyet-dinh', [QuyetDinhToChucKiemTraBbPhanCongController::class, 'exportQuyetDinh'])->name('quyet-dinh-to-chuc-ktm-bb.export-quyet-dinh');
    Route::post('/'.$slugQdBbKtm.'/xuat-bien-ban', [QuyetDinhToChucKiemTraBbPhanCongController::class, 'exportBienBan'])->name('quyet-dinh-to-chuc-ktm-bb.export-bien-ban');
    Route::post('/'.$slugQdBbKtm.'/xuat-ca-hai', [QuyetDinhToChucKiemTraBbPhanCongController::class, 'exportCaHai'])->name('quyet-dinh-to-chuc-ktm-bb.export-ca-hai');

    $slugToCham = '2-bien-ban-phan-cong-nhiem-vu-to-cham-kiem-tra';
    Route::get('/'.$slugToCham, [BienBanPhanCongToChamController::class, 'index'])->name('bien-ban-phan-cong-to-cham.index');
    Route::post('/'.$slugToCham.'/xuat', [BienBanPhanCongToChamController::class, 'export'])->name('bien-ban-phan-cong-to-cham.export');

    $slugBaoLuu = '2-quyet-dinh-bao-luu';
    Route::get('/'.$slugBaoLuu, [QuyetDinhBaoLuuController::class, 'index'])->name('quyet-dinh-bao-luu.index');
    Route::post('/'.$slugBaoLuu.'/xuat', [QuyetDinhBaoLuuController::class, 'export'])->name('quyet-dinh-bao-luu.export');

    $slugThoiHoc = '2-quyet-dinh-cho-thoi-hoc';
    Route::get('/'.$slugThoiHoc, [QuyetDinhThoiHocController::class, 'index'])->name('quyet-dinh-cho-thoi-hoc.index');
    Route::post('/'.$slugThoiHoc.'/xuat', [QuyetDinhThoiHocController::class, 'export'])->name('quyet-dinh-cho-thoi-hoc.export');

    $slugHuyPhien = '2-quyet-dinh-huy-phien-kem-danh-sach-huy-phien';
    Route::get('/'.$slugHuyPhien, [QuyetDinhHuyPhienController::class, 'index'])->name('quyet-dinh-huy-phien.index');
    Route::get('/'.$slugHuyPhien.'/khoa/{maKh}', [QuyetDinhHuyPhienController::class, 'khoaJson'])->name('quyet-dinh-huy-phien.khoa');
    Route::post('/'.$slugHuyPhien.'/xuat-word', [QuyetDinhHuyPhienController::class, 'exportWord'])->name('quyet-dinh-huy-phien.export-word');
    Route::post('/'.$slugHuyPhien.'/xuat-danh-sach', [QuyetDinhHuyPhienController::class, 'exportDanhSach'])->name('quyet-dinh-huy-phien.export-danh-sach');
    Route::post('/'.$slugHuyPhien.'/xuat-ca-hai', [QuyetDinhHuyPhienController::class, 'exportCaHai'])->name('quyet-dinh-huy-phien.export-ca-hai');

    $slugDoiXe = '2-quyet-dinh-doi-xe';
    Route::get('/'.$slugDoiXe, [QuyetDinhDoiXeController::class, 'index'])->name('quyet-dinh-doi-xe.index');
    Route::get('/'.$slugDoiXe.'/khoa/{maKh}', [QuyetDinhDoiXeController::class, 'khoaJson'])->name('quyet-dinh-doi-xe.khoa');
    Route::post('/'.$slugDoiXe.'/xem-truoc', [QuyetDinhDoiXeController::class, 'previewStore'])->name('quyet-dinh-doi-xe.preview.store');
    Route::get('/'.$slugDoiXe.'/xem-truoc', [QuyetDinhDoiXeController::class, 'preview'])->name('quyet-dinh-doi-xe.preview');
    Route::get('/'.$slugDoiXe.'/xuat-word', [QuyetDinhDoiXeController::class, 'export'])->name('quyet-dinh-doi-xe.export');
    Route::get('/'.$slugDoiXe.'/huy', [QuyetDinhDoiXeController::class, 'cancel'])->name('quyet-dinh-doi-xe.cancel');
    Route::get('/'.$slugDoiXe.'/lam-moi', [QuyetDinhDoiXeController::class, 'reset'])->name('quyet-dinh-doi-xe.reset');

    $slugDoiGv = '2-quyet-dinh-doi-giao-vien';
    Route::get('/'.$slugDoiGv, [QuyetDinhDoiGiaoVienController::class, 'index'])->name('quyet-dinh-doi-giao-vien.index');
    Route::get('/'.$slugDoiGv.'/khoa/{maKh}', [QuyetDinhDoiGiaoVienController::class, 'khoaJson'])->name('quyet-dinh-doi-giao-vien.khoa');
    Route::post('/'.$slugDoiGv.'/xem-truoc', [QuyetDinhDoiGiaoVienController::class, 'previewStore'])->name('quyet-dinh-doi-giao-vien.preview.store');
    Route::get('/'.$slugDoiGv.'/xem-truoc', [QuyetDinhDoiGiaoVienController::class, 'preview'])->name('quyet-dinh-doi-giao-vien.preview');
    Route::get('/'.$slugDoiGv.'/huy', [QuyetDinhDoiGiaoVienController::class, 'cancel'])->name('quyet-dinh-doi-giao-vien.cancel');
    Route::get('/'.$slugDoiGv.'/lam-moi', [QuyetDinhDoiGiaoVienController::class, 'reset'])->name('quyet-dinh-doi-giao-vien.reset');
    Route::get('/'.$slugDoiGv.'/xuat-word', [QuyetDinhDoiGiaoVienController::class, 'export'])->name('quyet-dinh-doi-giao-vien.export');

    $slugSoTheoDoiKq = '1-so-theo-doi-ket-qua-hoc-tap-cua-hoc-vien';
    Route::get('/'.$slugSoTheoDoiKq, [SoTheoDoiKetQuaHocTapController::class, 'index'])->name('so-theo-doi-ket-qua-hoc-tap.index');
    Route::post('/'.$slugSoTheoDoiKq.'/xuat', [SoTheoDoiKetQuaHocTapController::class, 'export'])->name('so-theo-doi-ket-qua-hoc-tap.export');
    Route::get('/'.$slugSoTheoDoiKq.'/khoa/{maKh}', [SoTheoDoiKetQuaHocTapController::class, 'khoaJson'])->name('so-theo-doi-ket-qua-hoc-tap.khoa');

    $slugSoPhanCong = '1-so-phan-cong-giao-vien-xe-tham-gia-giang-day-cua-khoa';
    Route::get('/'.$slugSoPhanCong, [SoPhanCongGvXeTapLaiController::class, 'index'])->name('so-phan-cong-gv-xe-tap-lai.index');
    Route::post('/'.$slugSoPhanCong.'/xem-truoc', [SoPhanCongGvXeTapLaiController::class, 'previewStore'])->name('so-phan-cong-gv-xe-tap-lai.preview.store');
    Route::get('/'.$slugSoPhanCong.'/xem-truoc', [SoPhanCongGvXeTapLaiController::class, 'preview'])->name('so-phan-cong-gv-xe-tap-lai.preview');
    Route::get('/'.$slugSoPhanCong.'/xuat', [SoPhanCongGvXeTapLaiController::class, 'export'])->name('so-phan-cong-gv-xe-tap-lai.export');
    Route::post('/'.$slugSoPhanCong.'/nhap-phan-cong', [SoPhanCongGvXeTapLaiController::class, 'import'])->name('so-phan-cong-gv-xe-tap-lai.import');
    Route::get('/'.$slugSoPhanCong.'/huy', [SoPhanCongGvXeTapLaiController::class, 'cancel'])->name('so-phan-cong-gv-xe-tap-lai.cancel');
    Route::get('/'.$slugSoPhanCong.'/khoa/{maKh}', [SoPhanCongGvXeTapLaiController::class, 'khoaJson'])->name('so-phan-cong-gv-xe-tap-lai.khoa');

    $slugPhoBien = '1-bien-ban-xac-nhan-pho-bien-noi-quy-quy-che-chuong-trinh-dao';
    Route::get('/'.$slugPhoBien, [BienBanXacNhanPhoBienNoiQuyController::class, 'index'])->name('bien-ban-pho-bien-noi-quy.index');
    Route::post('/'.$slugPhoBien.'/xuat', [BienBanXacNhanPhoBienNoiQuyController::class, 'export'])->name('bien-ban-pho-bien-noi-quy.export');

    $slugQuyetDinh = '1-quyet-dinh-thanh-lap-lop-hoc-danh-sach-hoc-vien';
    Route::get('/'.$slugQuyetDinh, [QuyetDinhThanhLapLopHocController::class, 'index'])->name('quyet-dinh-thanh-lap-lop-hoc.index');
    Route::post('/'.$slugQuyetDinh.'/xuat', [QuyetDinhThanhLapLopHocController::class, 'export'])->name('quyet-dinh-thanh-lap-lop-hoc.export');
    Route::get('/'.$slugQuyetDinh.'/khoa/{maKh}', [QuyetDinhThanhLapLopHocController::class, 'khoaJson'])->name('quyet-dinh-thanh-lap-lop-hoc.khoa');

    Route::prefix('lich-du-kien/thuc-hanh')->name('lich-du-kien.thuc-hanh.')->group(function () {
        Route::get('/', [ThucHanhController::class, 'index'])->name('index');
        Route::get('/tao', [ThucHanhController::class, 'create'])->name('create');
        Route::post('/', [ThucHanhController::class, 'store'])->name('store');
        Route::get('/{lich}', [ThucHanhController::class, 'edit'])->whereNumber('lich')->name('edit');
        Route::put('/{lich}', [ThucHanhController::class, 'update'])->whereNumber('lich')->name('update');
        Route::delete('/{lich}', [ThucHanhController::class, 'destroy'])->whereNumber('lich')->name('destroy');
    });

    foreach (MenuQldt::tatCaTrang() as $slug) {
        Route::get('/'.$slug, [TrangController::class, 'show'])
            ->defaults('trang', $slug)
            ->name(MenuQldt::tenRouteTrang($slug));
    }
});
