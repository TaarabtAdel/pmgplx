<?php

namespace App\Support;

class LoaiGiaoVien
{
    public static function label(?string $loai): string
    {
        return match (strtoupper(trim((string) $loai))) {
            'LT' => 'Loại: GV lý thuyết - NDĐT: Lý thuyết',
            'TH' => 'Loại: GV thực hành - NDĐT: Thực hành',
            'AL' => 'Loại: GV an toàn - NDĐT: An toàn',
            default => $loai ? 'Loại: '.$loai : '',
        };
    }
}
