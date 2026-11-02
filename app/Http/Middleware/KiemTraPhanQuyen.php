<?php

namespace App\Http\Middleware;

use App\Support\HeThong\PhanQuyenNguoiDung;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Chặn route của chức năng mà vai trò người dùng không được phân quyền (Hệ Thống → Vai trò → Phân quyền). */
class KiemTraPhanQuyen
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenRoute = (string) $request->route()?->getName();
        if ($tenRoute === '' || PhanQuyenNguoiDung::choPhepRoute($tenRoute)) {
            return $next($request);
        }

        $thongBao = 'Tài khoản chưa được phân quyền dùng chức năng này. Liên hệ Admin (Hệ Thống → Vai trò → Phân quyền).';
        if ($request->expectsJson()) {
            return response()->json(['message' => $thongBao], 403);
        }

        abort(403, $thongBao);
    }
}
