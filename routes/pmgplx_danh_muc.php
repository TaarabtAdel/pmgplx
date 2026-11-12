<?php

use App\Http\Controllers\PMGPLX\AnhHocVienController;
use App\Http\Controllers\PMGPLX\DanhSachGiaoVienController;
use App\Http\Controllers\PMGPLX\DanhSachHocVienController;
use App\Http\Controllers\PMGPLX\DanhSachKhoaHocController;
use App\Http\Controllers\PMGPLX\DanhSachXeController;
use App\Http\Controllers\PMGPLX\DoThongTinHocVienController;
use App\Http\Controllers\PMGPLX\NhapHocVienTuFileController;
use Illuminate\Support\Facades\Route;

Route::prefix('pmgplx')->name('pmgplx.')->group(function (): void {
    Route::redirect('/', '/pmgplx/danh-muc/giao-vien');

    Route::get('/danh-muc/giao-vien', [DanhSachGiaoVienController::class, 'index'])
        ->name('dm.giao-vien.index');
    Route::get('/danh-muc/giao-vien/xuat-excel', [DanhSachGiaoVienController::class, 'export'])
        ->name('dm.giao-vien.export');
    Route::post('/danh-muc/giao-vien/gan-xe', [DanhSachGiaoVienController::class, 'ganXe'])
        ->name('dm.giao-vien.gan-xe');

    Route::get('/danh-muc/hoc-vien', [DanhSachHocVienController::class, 'index'])
        ->name('dm.hoc-vien.index');
    Route::match(['get', 'post'], '/danh-muc/hoc-vien/xuat-excel', [DanhSachHocVienController::class, 'export'])
        ->name('dm.hoc-vien.export');
    Route::post('/danh-muc/hoc-vien/thong-tin-phu', [DanhSachHocVienController::class, 'capNhatThongTinPhu'])
        ->name('dm.hoc-vien.thong-tin-phu');

    Route::prefix('/danh-muc/hoc-vien/do-thong-tin')->name('dm.hoc-vien.do-thong-tin.')->group(function (): void {
        Route::post('/ocr', [DoThongTinHocVienController::class, 'ocr'])
            ->middleware('throttle:30,1')
            ->name('ocr');
        Route::post('/phien', [DoThongTinHocVienController::class, 'taoPhien'])->name('phien.tao');
        Route::get('/phien/{token}', [DoThongTinHocVienController::class, 'xemPhien'])
            ->where('token', '[A-Za-z0-9]{32}')
            ->name('phien.xem');
        Route::get('/tim-hoc-vien', [DoThongTinHocVienController::class, 'timHocVien'])->name('tim-hoc-vien');
        Route::post('/luu', [DoThongTinHocVienController::class, 'luu'])->name('luu');
    });
    Route::get('/danh-muc/hoc-vien/anh', [AnhHocVienController::class, 'show'])
        ->name('dm.hoc-vien.anh');
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
    Route::get('/danh-muc/xe/xuat-excel', [DanhSachXeController::class, 'export'])
        ->name('dm.xe.export');

    Route::get('/danh-muc/khoa-hoc', [DanhSachKhoaHocController::class, 'index'])
        ->name('dm.khoa-hoc.index');
});
