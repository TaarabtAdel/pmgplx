<?php

namespace App\Support\QldtOto;

use App\Models\QldtOto\LichSuXuatFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

/** Danh sách lịch sử xuất theo route (an toàn khi chưa migrate bảng). */
final class LichSuXuatTruyVan
{
    private const GIOI_HAN = 30;

    /**
     * @param  list<string>  $tenRoutesExport
     * @return Collection<int, LichSuXuatFile>
     */
    public static function theoRoutesExport(array $tenRoutesExport): Collection
    {
        if ($tenRoutesExport === []) {
            return new Collection;
        }

        try {
            if (! Schema::connection('sqlsrv_manhlinh')->hasTable('QldtOtoLichSuXuatFile')) {
                return new Collection;
            }
        } catch (\Throwable) {
            return new Collection;
        }

        return LichSuXuatFile::query()
            ->with('nguoiXuat')
            ->whereIn('TenRoute', $tenRoutesExport)
            ->orderByDesc('ThoiGianXuat')
            ->limit(self::GIOI_HAN)
            ->get();
    }

    /** @param  list<string>  $tenRoutesExport */
    public static function coDuLieu(array $tenRoutesExport): bool
    {
        return self::theoRoutesExport($tenRoutesExport)->isNotEmpty();
    }
}