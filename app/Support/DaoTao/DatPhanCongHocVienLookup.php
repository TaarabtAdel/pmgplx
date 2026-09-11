<?php

namespace App\Support\DaoTao;

use App\Models\PMGPLX\GiaoVien;
use Illuminate\Support\Collection;

class DatPhanCongHocVienLookup
{
    /**
     * @param  list<string>  $maGiaoVienList
     * @return Collection<string, bool>
     */
    public static function existingMaGiaoVienSet(array $maGiaoVienList): Collection
    {
        $codes = array_values(array_unique(array_filter(array_map(
            static fn (string $code): string => trim($code),
            $maGiaoVienList
        ))));

        if ($codes === []) {
            return collect();
        }

        return GiaoVien::query()
            ->whereIn('MaGV', $codes)
            ->pluck('MaGV')
            ->mapWithKeys(static fn (string $code): array => [$code => true]);
    }
}
