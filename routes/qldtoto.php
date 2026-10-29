<?php

use App\Http\Controllers\QldtOto\BaoCaoDangKyController;
use App\Http\Controllers\QldtOto\BaoCaoDeNghiSatHachController;
use App\Http\Controllers\QldtOto\DanhSachHocSinhLaiXeController;
use App\Http\Controllers\QldtOto\QuyetDinhThanhLapLopHocController;
use App\Http\Controllers\QldtOto\SoPhanCongGvXeTapLaiController;
use App\Http\Controllers\QldtOto\QuyetDinhDieuDongXeController;
use App\Http\Controllers\QldtOto\QuyetDinhDoiGiaoVienController;
use App\Http\Controllers\QldtOto\SoTheoDoiKetQuaHocTapController;
use App\Http\Controllers\QldtOto\CauHinhBaiDayController;
use App\Http\Controllers\QldtOto\LichDuKien\ThucHanhController;
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
