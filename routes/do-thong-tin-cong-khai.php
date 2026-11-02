<?php

use App\Http\Controllers\PMGPLX\DoThongTinHocVienController;
use Illuminate\Support\Facades\Route;

/*
| Trang chụp giấy tờ trên điện thoại (mở từ mã QR trong modal «Dò thông tin» của danh mục học viên).
| Không yêu cầu đăng nhập: chỉ dùng được khi có mã phiên còn hạn do máy tính (đã đăng nhập) tạo ra,
| và chỉ làm được hai việc — xem trang chụp, gửi ảnh để dò.
*/

Route::get('/dt/{token}', [DoThongTinHocVienController::class, 'dienThoai'])
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('do-thong-tin.dien-thoai');

Route::post('/dt/{token}/ocr', [DoThongTinHocVienController::class, 'ocrDienThoai'])
    ->where('token', '[A-Za-z0-9]{32}')
    ->middleware('throttle:30,1')
    ->name('do-thong-tin.dien-thoai.ocr');
