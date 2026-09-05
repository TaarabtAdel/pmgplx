<?php

use App\Http\Controllers\DaoTao\Dat\DanhSachDatDSPhienController;
use App\Http\Controllers\DaoTao\Dat\NhapFileDatDSPhienController;
use App\Http\Controllers\DaoTao\BaoCaoLuuLuongDaoTaoController as DaoTaoBaoCaoLuuLuongDaoTaoController;
use App\Http\Controllers\DaoTao\DanhSachPhanCongDaoTaoController;
use App\Http\Controllers\DaoTao\NhapFileInBangTenController;
use App\Http\Controllers\DaoTao\NhapFileSoPhanCongGiaoVienController;
use App\Http\Controllers\DaoTao\NhapFileTienDoDaoTaoController;
use App\Http\Controllers\PMGPLX\DanhSachGiaoVienController;
use App\Http\Controllers\PMGPLX\DanhSachHocVienController;
use App\Http\Controllers\PMGPLX\DanhSachLichGiaoVienController;
use App\Http\Controllers\PMGPLX\DanhSachLichXeTapController;
use App\Http\Controllers\PMGPLX\DanhSachXeController;
use App\Http\Controllers\PMGPLX\NhapHocVienTuFileController;
use App\Http\Controllers\PMGPLX\NhapLichTuFileController;
use App\Http\Controllers\PMGPLX\TaoLichDayLyThuyetController;
use App\Http\Controllers\PMGPLX\TaoLichTapHangLoatController;
use App\Http\Controllers\PMGPLXOLD\DanhSachHocVienController as OldDanhSachHocVienController;
use App\Http\Controllers\TrangChuController;
use App\Http\Controllers\TrungTam\DanhSachGiaoVienController as TrungTamDanhSachGiaoVienController;
use App\Http\Controllers\TrungTam\DanhSachXeTapLaiController as TrungTamDanhSachXeTapLaiController;
use App\Http\Controllers\TrungTam\GopGiaoVienController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrangChuController::class, 'index'])->name('home');

Route::prefix('pmgplx')->name('pmgplx.')->group(function () {
    Route::redirect('/', '/pmgplx/danh-muc/giao-vien');

    Route::get('/danh-muc/giao-vien', [DanhSachGiaoVienController::class, 'index'])
        ->name('dm.giao-vien.index');
    Route::post('/danh-muc/giao-vien/gan-xe', [DanhSachGiaoVienController::class, 'ganXe'])
        ->name('dm.giao-vien.gan-xe');

    Route::get('/danh-muc/hoc-vien', [DanhSachHocVienController::class, 'index'])
        ->name('dm.hoc-vien.index');
    Route::get('/danh-muc/hoc-vien/dong-bo', [DanhSachHocVienController::class, 'dongBoForm'])
        ->name('dm.hoc-vien.dong-bo.form');
    Route::post('/danh-muc/hoc-vien/dong-bo', [DanhSachHocVienController::class, 'dongBoStore'])
        ->name('dm.hoc-vien.dong-bo.store');
    Route::post('/danh-muc/hoc-vien/dong-bo/test-mot', [DanhSachHocVienController::class, 'dongBoTestMot'])
        ->name('dm.hoc-vien.dong-bo.test-mot');
    Route::post('/danh-muc/hoc-vien/dong-bo/khoi-phuc', [DanhSachHocVienController::class, 'dongBoKhoiPhuc'])
        ->name('dm.hoc-vien.dong-bo.khoi-phuc');
    Route::post('/danh-muc/hoc-vien/dong-bo-loc', [DanhSachHocVienController::class, 'dongBo'])
        ->name('dm.hoc-vien.dong-bo');

    Route::get('/danh-muc/hoc-vien/nhap-tu-file', [NhapHocVienTuFileController::class, 'create'])
        ->name('dm.hoc-vien.nhap-file.create');
    Route::post('/danh-muc/hoc-vien/nhap-tu-file', [NhapHocVienTuFileController::class, 'store'])
        ->name('dm.hoc-vien.nhap-file.store');
    Route::get('/danh-muc/hoc-vien/nhap-tu-file/xem-truoc', [NhapHocVienTuFileController::class, 'preview'])
        ->name('dm.hoc-vien.nhap-file.preview');
    Route::get('/danh-muc/hoc-vien/nhap-tu-file/huy', [NhapHocVienTuFileController::class, 'cancel'])
        ->name('dm.hoc-vien.nhap-file.cancel');

    Route::get('/danh-muc/xe', [DanhSachXeController::class, 'index'])
        ->name('dm.xe.index');

    Route::get('/lich/giao-vien', [DanhSachLichGiaoVienController::class, 'index'])
        ->name('lich.gv.index');

    Route::get('/lich/xe-tap', [DanhSachLichXeTapController::class, 'index'])
        ->name('lich.xe.index');

    Route::get('/lich/ly-thuyet', [TaoLichDayLyThuyetController::class, 'create'])
        ->name('lich.ly-thuyet.create');
    Route::post('/lich/ly-thuyet', [TaoLichDayLyThuyetController::class, 'store'])
        ->name('lich.ly-thuyet.store');
    Route::get('/lich/ly-thuyet/xem-truoc', [TaoLichDayLyThuyetController::class, 'preview'])
        ->name('lich.ly-thuyet.preview');
    Route::post('/lich/ly-thuyet/xac-nhan', [TaoLichDayLyThuyetController::class, 'confirm'])
        ->name('lich.ly-thuyet.confirm');
    Route::get('/lich/ly-thuyet/huy', [TaoLichDayLyThuyetController::class, 'cancel'])
        ->name('lich.ly-thuyet.cancel');

    Route::get('/lich/thuc-hanh', [TaoLichTapHangLoatController::class, 'create'])
        ->name('lich.thuc-hanh.create');
    Route::post('/lich/thuc-hanh', [TaoLichTapHangLoatController::class, 'store'])
        ->name('lich.thuc-hanh.store');
    Route::get('/lich/thuc-hanh/xem-truoc', [TaoLichTapHangLoatController::class, 'preview'])
        ->name('lich.thuc-hanh.preview');
    Route::post('/lich/thuc-hanh/xac-nhan', [TaoLichTapHangLoatController::class, 'confirm'])
        ->name('lich.thuc-hanh.confirm');
    Route::get('/lich/thuc-hanh/huy', [TaoLichTapHangLoatController::class, 'cancel'])
        ->name('lich.thuc-hanh.cancel');

    Route::get('/lich/nhap-tu-file', [NhapLichTuFileController::class, 'create'])
        ->name('lich.nhap-file.create');
    Route::post('/lich/nhap-tu-file', [NhapLichTuFileController::class, 'store'])
        ->name('lich.nhap-file.store');
    Route::get('/lich/nhap-tu-file/xem-truoc', [NhapLichTuFileController::class, 'preview'])
        ->name('lich.nhap-file.preview');
    Route::post('/lich/nhap-tu-file/xem-truoc-gv', [NhapLichTuFileController::class, 'toGvPreview'])
        ->name('lich.nhap-file.to-gv');
    Route::get('/lich/nhap-tu-file/lich-giao-vien', [NhapLichTuFileController::class, 'previewGv'])
        ->name('lich.nhap-file.preview-gv');
    Route::post('/lich/nhap-tu-file/lich-xe', [NhapLichTuFileController::class, 'toXePreview'])
        ->name('lich.nhap-file.to-xe');
    Route::get('/lich/nhap-tu-file/lich-xe', [NhapLichTuFileController::class, 'previewXe'])
        ->name('lich.nhap-file.preview-xe');
    Route::post('/lich/nhap-tu-file/xem-truoc-db', [NhapLichTuFileController::class, 'toDbPreview'])
        ->name('lich.nhap-file.to-db');
    Route::get('/lich/nhap-tu-file/xem-truoc-db', [NhapLichTuFileController::class, 'previewDb'])
        ->name('lich.nhap-file.preview-db');
    Route::post('/lich/nhap-tu-file/xac-nhan', [NhapLichTuFileController::class, 'confirm'])
        ->name('lich.nhap-file.confirm');
    Route::get('/lich/nhap-tu-file/huy', [NhapLichTuFileController::class, 'cancel'])
        ->name('lich.nhap-file.cancel');
});

Route::prefix('daotao')->name('daotao.')->group(function () {
    Route::get('/phong-dao-tao/bao-cao/luu-luong-dao-tao', [DaoTaoBaoCaoLuuLuongDaoTaoController::class, 'index'])
        ->name('pdt.bc.luu-luong-dao-tao');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-tien-do-dao-tao', [NhapFileTienDoDaoTaoController::class, 'create'])
        ->name('pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao');
    Route::post('/phong-dao-tao/cong-cu-nhap/nhap-file-tien-do-dao-tao', [NhapFileTienDoDaoTaoController::class, 'store'])
        ->name('pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.store');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-tien-do-dao-tao/xem-truoc', [NhapFileTienDoDaoTaoController::class, 'preview'])
        ->name('pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.preview');
    Route::post('/phong-dao-tao/cong-cu-nhap/nhap-file-tien-do-dao-tao/xac-nhan', [NhapFileTienDoDaoTaoController::class, 'confirm'])
        ->name('pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.confirm');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-tien-do-dao-tao/huy', [NhapFileTienDoDaoTaoController::class, 'cancel'])
        ->name('pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao.cancel');

    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-so-phan-cong-giao-vien', [NhapFileSoPhanCongGiaoVienController::class, 'create'])
        ->name('pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien');
    Route::post('/phong-dao-tao/cong-cu-nhap/nhap-file-so-phan-cong-giao-vien', [NhapFileSoPhanCongGiaoVienController::class, 'store'])
        ->name('pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.store');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-so-phan-cong-giao-vien/xem-truoc', [NhapFileSoPhanCongGiaoVienController::class, 'preview'])
        ->name('pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.preview');
    Route::post('/phong-dao-tao/cong-cu-nhap/nhap-file-so-phan-cong-giao-vien/xac-nhan', [NhapFileSoPhanCongGiaoVienController::class, 'confirm'])
        ->name('pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.confirm');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-so-phan-cong-giao-vien/huy', [NhapFileSoPhanCongGiaoVienController::class, 'cancel'])
        ->name('pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.cancel');

    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-in-bang-ten', [NhapFileInBangTenController::class, 'create'])
        ->name('pdt.cong-cu-nhap.nhap-file-in-bang-ten');
    Route::post('/phong-dao-tao/cong-cu-nhap/nhap-file-in-bang-ten', [NhapFileInBangTenController::class, 'store'])
        ->name('pdt.cong-cu-nhap.nhap-file-in-bang-ten.store');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-in-bang-ten/xem-truoc', [NhapFileInBangTenController::class, 'preview'])
        ->name('pdt.cong-cu-nhap.nhap-file-in-bang-ten.preview');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-in-bang-ten/in', [NhapFileInBangTenController::class, 'printSheet'])
        ->name('pdt.cong-cu-nhap.nhap-file-in-bang-ten.print');
    Route::get('/phong-dao-tao/cong-cu-nhap/nhap-file-in-bang-ten/huy', [NhapFileInBangTenController::class, 'cancel'])
        ->name('pdt.cong-cu-nhap.nhap-file-in-bang-ten.cancel');

    Route::get('/phong-dao-tao/phan-cong-dao-tao/danh-sach', [DanhSachPhanCongDaoTaoController::class, 'index'])
        ->name('pdt.phan-cong-dao-tao.danh-sach');

    Route::get('/phong-dao-tao/dat/quan-ly-phien', [DanhSachDatDSPhienController::class, 'index'])
        ->name('pdt.dat.quan-ly-phien');
    Route::get('/phong-dao-tao/dat/nhap-du-lieu-phien', [NhapFileDatDSPhienController::class, 'create'])
        ->name('pdt.dat.nhap-du-lieu-phien');
    Route::post('/phong-dao-tao/dat/nhap-du-lieu-phien', [NhapFileDatDSPhienController::class, 'store'])
        ->name('pdt.dat.nhap-du-lieu-phien.store');
    Route::get('/phong-dao-tao/dat/nhap-du-lieu-phien/xem-truoc', [NhapFileDatDSPhienController::class, 'preview'])
        ->name('pdt.dat.nhap-du-lieu-phien.preview');
    Route::post('/phong-dao-tao/dat/nhap-du-lieu-phien/xac-nhan', [NhapFileDatDSPhienController::class, 'confirm'])
        ->name('pdt.dat.nhap-du-lieu-phien.confirm');
    Route::get('/phong-dao-tao/dat/nhap-du-lieu-phien/huy', [NhapFileDatDSPhienController::class, 'cancel'])
        ->name('pdt.dat.nhap-du-lieu-phien.cancel');
});

Route::prefix('trung-tam')->name('trungtam.')->group(function () {
    Route::get('/giao-vien', [TrungTamDanhSachGiaoVienController::class, 'index'])
        ->name('giao-vien.danh-sach');
    Route::get('/giao-vien/gop', [GopGiaoVienController::class, 'create'])
        ->name('giao-vien.gop');
    Route::post('/giao-vien/gop', [GopGiaoVienController::class, 'store'])
        ->name('giao-vien.gop.store');
    Route::get('/xe-tap-lai', [TrungTamDanhSachXeTapLaiController::class, 'index'])
        ->name('xe-tap-lai.danh-sach');
});

Route::prefix('pmgplxold')->name('pmgplxold.')->group(function () {
    Route::redirect('/', '/pmgplxold/danh-muc/hoc-vien');

    Route::get('/danh-muc/hoc-vien', [OldDanhSachHocVienController::class, 'index'])
        ->name('dm.hoc-vien.index');
});
