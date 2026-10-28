<?php

use App\Http\Controllers\QldtOto\BaoCaoDangKyController;
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
