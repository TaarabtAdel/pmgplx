<?php

namespace App\Http\Middleware;

use App\Support\QldtOto\LichSuXuatGhiNhan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sau khi xuất file QLĐT Ô TÔ thành công, ghi một dòng vào QldtOtoLichSuXuatFile. */
class GhiLichSuXuatQldtOto
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        LichSuXuatGhiNhan::tuPhanHoi($request, $response);

        return $response;
    }
}