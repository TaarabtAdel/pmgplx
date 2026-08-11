<?php

use App\Http\Controllers\DanhSachGiaoVienController;
use App\Http\Controllers\DanhSachLichGiaoVienController;
use App\Http\Controllers\DanhSachLichXeTapController;
use App\Http\Controllers\DanhSachXeController;
use App\Http\Controllers\TaoLichDayLyThuyetController;
use App\Http\Controllers\TaoLichTapHangLoatController;
use App\Http\Controllers\TrangChuController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrangChuController::class, 'index'])->name('home');

Route::get('/danh-muc/giao-vien', [DanhSachGiaoVienController::class, 'index'])
    ->name('dm.giao-vien.index');

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

Route::get('/lich/thuc-hanh', [TaoLichTapHangLoatController::class, 'create'])
    ->name('lich.thuc-hanh.create');
Route::post('/lich/thuc-hanh', [TaoLichTapHangLoatController::class, 'store'])
    ->name('lich.thuc-hanh.store');
