<?php

namespace App\Http\Middleware;

use App\Support\QldtOto\LichSuXuatHienThi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/** Chia sẻ dữ liệu accordion lịch sử xuất cho layout (mọi màn QLĐT Ô TÔ có xuất file). */
class ChuanBiLichSuXuatQldtOto
{
    public function handle(Request $request, Closure $next): Response
    {
        $data = LichSuXuatHienThi::choRequest($request);
        if ($data !== []) {
            View::share($data);
        }

        return $next($request);
    }
}