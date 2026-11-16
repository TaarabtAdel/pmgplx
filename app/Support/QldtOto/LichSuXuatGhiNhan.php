<?php

namespace App\Support\QldtOto;

use App\Models\QldtOto\BieuMauXuat;
use App\Models\QldtOto\LichSuXuatFile;
use App\Support\MenuQldt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Ghi lịch sử xuất file QLĐT Ô TÔ (không làm hỏng luồng tải file nếu lỗi DB). */
final class LichSuXuatGhiNhan
{
    private const THAM_SO_TOI_DA = 12000;

    public static function tuPhanHoi(Request $request, Response $response): void
    {
        if (! self::laPhanHoiTaiFile($response)) {
            return;
        }

        $route = $request->route();
        $tenRoute = (string) ($route?->getName() ?? '');
        if ($tenRoute === '' || ! str_starts_with($tenRoute, MenuQldt::ROUTE_PREFIX)) {
            return;
        }
        if (! self::laHanhDongXuat($tenRoute, $request->path())) {
            return;
        }

        $tenFile = self::tenFileTuPhanHoi($response);
        $loaiFile = self::loaiFileTuTen($tenFile);
        $bieuMau = BieuMauXuat::query()->where('TenRouteExport', $tenRoute)->first();
        $vanBan = LichSuXuatVanBanTuRequest::trichXuat($request, $tenRoute);

        try {
            LichSuXuatFile::query()->create([
                'BieuMauId' => $bieuMau?->Id,
                'TenRoute' => mb_substr($tenRoute, 0, 160),
                'TenChucNang' => $bieuMau?->TenBieuMau ?: self::tenChucNangTuRoute($tenRoute),
                'DuongDan' => mb_substr('/'.$request->path(), 0, 500),
                'PhuongThuc' => mb_strtoupper($request->method()),
                'TenFile' => $tenFile !== null ? mb_substr($tenFile, 0, 255) : null,
                'LoaiFile' => $loaiFile ?: $bieuMau?->LoaiXuat,
                'SoCongVan' => $vanBan['so_cong_van'],
                'NgayVanBan' => $vanBan['ngay_van_ban'],
                'MaKh' => $vanBan['ma_kh'],
                'ThamSoJson' => self::thamSoJson($request),
                'NguoiXuatId' => auth()->id(),
                'ThoiGianXuat' => Carbon::now(),
                'DiaChiIp' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('QldtOto lịch sử xuất file: không ghi được', [
                'route' => $tenRoute,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function laPhanHoiTaiFile(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return true;
        }

        $type = (string) $response->headers->get('Content-Type', '');

        return str_contains($type, 'spreadsheet')
            || str_contains($type, 'wordprocessing')
            || str_contains($type, 'zip')
            || str_contains($type, 'octet-stream');
    }

    private static function laHanhDongXuat(string $tenRoute, string $path): bool
    {
        if (str_contains($tenRoute, '.export')) {
            return true;
        }

        return (bool) preg_match('#(^|/)xuat(-|$|/)#i', $path);
    }

    private static function tenFileTuPhanHoi(Response $response): ?string
    {
        $disposition = (string) $response->headers->get('Content-Disposition', '');
        if ($disposition !== '') {
            if (preg_match("/filename\\*=UTF-8''([^;]+)/i", $disposition, $m)) {
                return rawurldecode($m[1]);
            }
            if (preg_match('/filename="?([^";]+)"?/i', $disposition, $m)) {
                return $m[1];
            }
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();

            return $file !== null ? $file->getFilename() : null;
        }

        return null;
    }

    private static function loaiFileTuTen(?string $tenFile): ?string
    {
        if ($tenFile === null || $tenFile === '') {
            return null;
        }
        $ext = strtolower(pathinfo($tenFile, PATHINFO_EXTENSION));

        return $ext !== '' ? mb_substr($ext, 0, 20) : null;
    }

    private static function thamSoJson(Request $request): ?string
    {
        $raw = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
        ]);

        if ($request->route() !== null) {
            foreach ($request->route()->parameters() as $key => $value) {
                if (is_object($value) && method_exists($value, 'getKey')) {
                    $raw['_route'][$key] = [
                        'model' => class_basename($value),
                        'id' => $value->getKey(),
                    ];
                } elseif (! is_object($value)) {
                    $raw['_route'][$key] = $value;
                }
            }
        }

        if ($raw === []) {
            return null;
        }

        $json = json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return null;
        }
        if (strlen($json) > self::THAM_SO_TOI_DA) {
            $json = substr($json, 0, self::THAM_SO_TOI_DA - 20).'…[truncated]';
        }

        return $json;
    }

    private static function tenChucNangTuRoute(string $tenRoute): ?string
    {
        $routeIndex = self::routeIndexTuExport($tenRoute);
        if ($routeIndex === null) {
            return null;
        }

        $found = null;
        $walk = function (array $items) use (&$walk, &$found, $routeIndex): void {
            foreach ($items as $item) {
                if ($found !== null) {
                    return;
                }
                if (! empty($item['children'])) {
                    $walk($item['children']);
                } elseif (($item['route'] ?? null) === $routeIndex) {
                    $found = (string) ($item['title'] ?? '');
                }
            }
        };
        $walk(MenuQldt::tabs());

        if ($found !== null && $found !== '') {
            $suffix = self::nhanLoaiXuat($tenRoute);

            return $suffix !== '' ? $found.' — '.$suffix : $found;
        }

        return self::nhanLoaiXuat($tenRoute) ?: $tenRoute;
    }

    private static function routeIndexTuExport(string $tenRoute): ?string
    {
        if (! str_contains($tenRoute, '.export')) {
            return null;
        }
        $base = preg_replace('/\.export.*$/', '', $tenRoute);

        return $base !== '' ? $base.'.index' : null;
    }

    private static function nhanLoaiXuat(string $tenRoute): string
    {
        if (! preg_match('/\.export(?:-(.+))?$/', $tenRoute, $m)) {
            return '';
        }
        $suffix = $m[1] ?? '';
        if ($suffix === 'excel') {
            return 'Xuất Excel';
        }
        if ($suffix === '') {
            return 'Xuất file';
        }

        return match ($suffix) {
            'word' => 'Xuất Word',
            'quyet-dinh' => 'Xuất Quyết định',
            'bien-ban' => 'Xuất Biên bản',
            'danh-sach' => 'Xuất Danh sách',
            'ca-hai' => 'Xuất cả hai (ZIP)',
            default => 'Xuất file',
        };
    }
}