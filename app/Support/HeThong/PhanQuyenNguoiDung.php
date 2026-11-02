<?php

namespace App\Support\HeThong;

use App\Models\HeThong\NguoiDung;
use App\Models\HeThong\PhanQuyen;
use Illuminate\Support\Str;

/**
 * Kiểm tra quyền của người dùng đang đăng nhập theo ma trận phân quyền (bảng PhanQuyen, MANHLINH).
 * Admin luôn đủ quyền. Người dùng có nhiều vai trò thì được gộp quyền của tất cả vai trò.
 */
final class PhanQuyenNguoiDung
{
    /** @var array<int, array<string, true>> Id người dùng => tập khoá lá được phép (cache trong 1 request) */
    private static array $cache = [];

    public static function laAdmin(?NguoiDung $user = null): bool
    {
        $user ??= self::user();

        return $user !== null && $user->coVaiTro('admin');
    }

    /**
     * Được dùng chức năng $khoa? $khoa có thể là nút lá, hoặc Tab / Menu 1 (được nếu có ít nhất một lá bên dưới).
     */
    public static function coQuyen(string $khoa, ?NguoiDung $user = null): bool
    {
        $user ??= self::user();
        if ($user === null) {
            return false;
        }
        if (self::laAdmin($user)) {
            return true;
        }

        $duoc = self::khoaDuocPhep($user);
        if (isset($duoc[$khoa])) {
            return true;
        }
        foreach ($duoc as $k => $_) {
            if (str_starts_with($k, $khoa.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Được mở route $tenRoute? Route không thuộc chức năng nào trong ma trận (trang chủ, đăng xuất…) thì cho qua.
     */
    public static function choPhepRoute(string $tenRoute, ?NguoiDung $user = null): bool
    {
        $user ??= self::user();
        if ($user === null) {
            return false;
        }
        if (self::laAdmin($user)) {
            return true;
        }

        $thuocChucNang = false;
        $duoc = self::khoaDuocPhep($user);
        foreach (CayChucNang::laNut() as $khoa => $la) {
            if ($la['active'] === [] || ! Str::is($la['active'], $tenRoute)) {
                continue;
            }
            $thuocChucNang = true;
            if (isset($duoc[$khoa])) {
                return true;
            }
        }

        return ! $thuocChucNang;
    }

    /** @return array<string, true> */
    public static function khoaDuocPhep(NguoiDung $user): array
    {
        $id = (int) $user->getKey();
        if (! isset(self::$cache[$id])) {
            $vaiTro = $user->maVaiTroList();
            self::$cache[$id] = $vaiTro === []
                ? []
                : array_fill_keys(
                    PhanQuyen::query()->whereIn('MaVaiTro', $vaiTro)->distinct()->pluck('MaChucNang')->all(),
                    true
                );
        }

        return self::$cache[$id];
    }

    public static function xoaCache(): void
    {
        self::$cache = [];
    }

    private static function user(): ?NguoiDung
    {
        $u = auth()->user();

        return $u instanceof NguoiDung ? $u : null;
    }
}
