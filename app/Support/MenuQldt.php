<?php

namespace App\Support;

/** Đọc menu QLĐT (config/menu_qldt.php): URL, mục đang chọn, tìm trang theo slug. */
final class MenuQldt
{
    public const ROUTE_PREFIX = 'qldtoto.';

    /** @param  array<string, mixed>  $item */
    public static function dangChon(array $item): bool
    {
        if (! empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (self::dangChon($child)) {
                    return true;
                }
            }

            return false;
        }

        $request = request();
        if (! empty($item['active']) && $request->routeIs($item['active'])) {
            return true;
        }
        $routeName = self::routeName($item);
        if ($routeName !== null && $request->routeIs($routeName)) {
            return true;
        }

        $url = self::url($item);

        return $url !== '#' && $request->url() === $url;
    }

    /** @param  array<string, mixed>  $item */
    public static function url(array $item): string
    {
        $routeName = self::routeName($item);
        if ($routeName !== null) {
            return route($routeName);
        }

        return (string) ($item['url'] ?? '#');
    }

    /** @param  array<string, mixed>  $item */
    public static function routeName(array $item): ?string
    {
        if (! empty($item['trang'])) {
            return self::ROUTE_PREFIX.self::tenRouteTrang((string) $item['trang']);
        }

        return ! empty($item['route']) ? (string) $item['route'] : null;
    }

    /** Slug 'lich-du-kien/ly-thuyet' → tên route 'lich-du-kien.ly-thuyet' (chưa gồm tiền tố). */
    public static function tenRouteTrang(string $slug): string
    {
        return str_replace('/', '.', $slug);
    }

    /** @return list<array<string, mixed>> */
    public static function tabs(): array
    {
        return config('menu_qldt.tabs', []);
    }

    /** @return list<string> */
    public static function tatCaTrang(): array
    {
        $out = [];
        $walk = function (array $items) use (&$walk, &$out): void {
            foreach ($items as $item) {
                if (! empty($item['children'])) {
                    $walk($item['children']);
                } elseif (! empty($item['trang'])) {
                    $out[] = (string) $item['trang'];
                }
            }
        };
        $walk(self::tabs());

        return $out;
    }

    /**
     * @return array{item: array<string, mixed>, duong_dan: list<string>}|null
     */
    public static function timTrang(string $slug): ?array
    {
        $found = null;
        $walk = function (array $items, array $path) use (&$walk, &$found, $slug): void {
            foreach ($items as $item) {
                if ($found !== null) {
                    return;
                }
                if (! empty($item['children'])) {
                    $walk($item['children'], [...$path, (string) $item['title']]);
                } elseif (($item['trang'] ?? null) === $slug) {
                    $found = ['item' => $item, 'duong_dan' => $path];
                }
            }
        };
        $walk(self::tabs(), []);

        return $found;
    }
}
