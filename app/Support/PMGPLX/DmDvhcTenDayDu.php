<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\NguoiLX;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ghép địa chỉ NguoiLX.
 * Sau sáp nhập DVHC: ưu tiên {@see DM_DVHC.TenDayDu} theo MaDVHC (mục GhiChu «GOP DVHC …»),
 * rồi mới fallback chuỗi NoiTT / NoiCT (thường là địa chỉ cũ trước sáp nhập).
 */
final class DmDvhcTenDayDu
{
    /**
     * @param  iterable<string|null>  $maDvhcCodes
     * @return array<string, string> MaDvhc => TenDayDu
     */
    public static function map(iterable $maDvhcCodes): array
    {
        $mas = collect($maDvhcCodes)
            ->map(fn ($m) => trim((string) $m))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($mas === []) {
            return [];
        }

        $conn = (new NguoiLX)->getConnectionName();

        return DB::connection($conn)
            ->table('DM_DVHC')
            ->whereIn('MaDvhc', $mas)
            ->where('TrangThai', 1)
            ->pluck('TenDayDu', 'MaDvhc')
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->all();
    }

    /**
     * @param  Collection<string, object>  $hocVien
     * @return array<string, string>
     */
    public static function mapForHocVien(Collection $hocVien): array
    {
        $codes = [];
        foreach ($hocVien as $hv) {
            foreach (['NoiTT_MaDVHC', 'NoiCT_MaDVHC'] as $col) {
                $m = trim((string) ($hv->{$col} ?? ''));
                if ($m !== '') {
                    $codes[] = $m;
                }
            }
        }

        return self::map($codes);
    }

    /**
     * @param  array<string, string>  $dvhcMap
     * @return array{thuong_tru: string, cu_tru: string}
     */
    public static function diaChiTuNguoiLx(?object $hv, array $dvhcMap): array
    {
        if ($hv === null) {
            return ['thuong_tru' => '', 'cu_tru' => ''];
        }

        return [
            'thuong_tru' => self::resolve(
                trim((string) ($hv->NoiTT ?? '')),
                trim((string) ($hv->NoiTT_MaDVHC ?? '')),
                $dvhcMap
            ),
            'cu_tru' => self::resolve(
                trim((string) ($hv->NoiCT ?? '')),
                trim((string) ($hv->NoiCT_MaDVHC ?? '')),
                $dvhcMap
            ),
        ];
    }

    /** @param  array<string, string>  $dvhcMap */
    private static function resolve(string $text, string $maDvhc, array $dvhcMap): string
    {
        if ($maDvhc !== '' && ($dvhcMap[$maDvhc] ?? '') !== '') {
            return $dvhcMap[$maDvhc];
        }

        return $text;
    }
}
