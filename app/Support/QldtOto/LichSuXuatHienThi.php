<?php

namespace App\Support\QldtOto;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/** Dữ liệu accordion lịch sử xuất gắn vào view QldtOto. */
final class LichSuXuatHienThi
{
    /**
     * @return array{
     *     qldtOtoLichSuXuat: array{
     *         items: \Illuminate\Database\Eloquent\Collection,
     *     }
     * }|array{}
     */
    public static function choRequest(Request $request): array
    {
        $tenRouteTrang = (string) ($request->route()?->getName() ?? '');
        if ($tenRouteTrang === '' || ! str_starts_with($tenRouteTrang, 'qldtoto.')) {
            return [];
        }

        $exportRoutes = self::routeXuatChoTrang($tenRouteTrang);
        if ($exportRoutes === []) {
            return [];
        }

        return [
            'qldtOtoLichSuXuat' => [
                'items' => LichSuXuatTruyVan::theoRoutesExport($exportRoutes),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private static function routeXuatChoTrang(string $tenRouteTrang): array
    {
        $fromConfig = config('qldt_oto_lich_su_theo_trang.'.$tenRouteTrang);
        if (is_array($fromConfig) && $fromConfig !== []) {
            return $fromConfig;
        }

        if (! preg_match('/^qldtoto\.(.+)\.(index|preview|edit|create)$/', $tenRouteTrang, $m)) {
            return [];
        }

        $tienTo = 'qldtoto.'.$m[1].'.export';
        $found = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! is_string($name) || ! str_starts_with($name, $tienTo)) {
                continue;
            }
            $found[] = $name;
        }

        return array_values(array_unique($found));
    }
}