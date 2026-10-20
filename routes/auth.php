<?php

use App\Http\Controllers\Auth\DangNhapController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [DangNhapController::class, 'create'])
        ->name('login');
    Route::post('/dang-nhap', [DangNhapController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::post('/dang-xuat', [DangNhapController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
