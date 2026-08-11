<?php

use App\Http\Controllers\PMGPLX\DanhSachGiaoVienController;
use App\Http\Controllers\PMGPLX\DanhSachHocVienController;
use App\Http\Controllers\PMGPLX\DanhSachLichGiaoVienController;
use App\Http\Controllers\PMGPLX\DanhSachLichXeTapController;
use App\Http\Controllers\PMGPLX\DanhSachXeController;
use App\Http\Controllers\PMGPLX\TaoLichDayLyThuyetController;
use App\Http\Controllers\PMGPLX\TaoLichTapHangLoatController;
use App\Http\Controllers\PMGPLX\TrangChuController;
use App\Http\Controllers\PMGPLXOLD\DanhSachHocVienController as OldDanhSachHocVienController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pmgplx');

Route::prefix('pmgplx')->name('pmgplx.')->group(function () {
    Route::get('/', [TrangChuController::class, 'index'])->name('home');

    Route::get('/danh-muc/giao-vien', [DanhSachGiaoVienController::class, 'index'])
        ->name('dm.giao-vien.index');
    Route::post('/danh-muc/giao-vien/gan-xe', [DanhSachGiaoVienController::class, 'ganXe'])
        ->name('dm.giao-vien.gan-xe');

    Route::get('/danh-muc/hoc-vien', [DanhSachHocVienController::class, 'index'])
        ->name('dm.hoc-vien.index');
    Route::post('/danh-muc/hoc-vien/dong-bo', [DanhSachHocVienController::class, 'dongBo'])
        ->name('dm.hoc-vien.dong-bo');

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
});

Route::prefix('pmgplxold')->name('pmgplxold.')->group(function () {
    Route::redirect('/', '/pmgplxold/danh-muc/hoc-vien');

    Route::get('/danh-muc/hoc-vien', [OldDanhSachHocVienController::class, 'index'])
        ->name('dm.hoc-vien.index');
});
