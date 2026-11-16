<?php

namespace App\Support\QldtOto;

use App\Models\QldtOto\BieuMauXuat;

/** Đồng bộ config/qldt_oto_bieu_mau_xuat.php → QldtOtoBieuMauXuat. */
final class BieuMauXuatDongBo
{
    public function dongBoTuConfig(): void
    {
        $rows = config('qldt_oto_bieu_mau_xuat', []);
        foreach ($rows as $row) {
            $tenRoute = (string) ($row['ten_route'] ?? '');
            if ($tenRoute === '') {
                continue;
            }
            BieuMauXuat::query()->updateOrCreate(
                ['TenRouteExport' => $tenRoute],
                [
                    'Ma' => mb_substr((string) ($row['ma'] ?? $tenRoute), 0, 80),
                    'TenBieuMau' => mb_substr((string) ($row['ten'] ?? $tenRoute), 0, 255),
                    'LoaiXuat' => mb_substr((string) ($row['loai'] ?? ''), 0, 40) ?: null,
                    'ThuTu' => (int) ($row['thu_tu'] ?? 0),
                    'HoatDong' => true,
                ],
            );
        }
    }
}