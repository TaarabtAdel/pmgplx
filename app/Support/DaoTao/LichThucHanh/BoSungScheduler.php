<?php

namespace App\Support\DaoTao\LichThucHanh;

final class BoSungScheduler
{
    /** @param array<string, mixed> $cauHinh
     * @return list<string>
     */
    public function fixedDates(array $cauHinh): array
    {
        $moc = LichMocLich::fromCauHinh($cauHinh);

        return $moc['bo_sung'];
    }

    /**
     * @param  array<string, mixed>  $cauHinh
     * @return array{label: string, bien_override: ?string}
     */
    public function metaForGv(array $gv, string $iso, array $cauHinh): array
    {
        $td = trim((string) ($cauHinh['xe_bo_sung']['bien_so_tu_dong'] ?? '74A-452.04'));
        $san = trim((string) ($cauHinh['xe_bo_sung']['bien_so_san'] ?? ''));
        $dates = $this->fixedDates($cauHinh);
        $dayIndex = array_search($iso, $dates, true);
        if ($dayIndex === false) {
            return ['label' => 'BỔ SUNG', 'bien_override' => null];
        }
        $swap = ($gv['ca'] === 'sang') xor ($dayIndex === 1);

        return [
            'label' => $swap ? 'BỔ SUNG '.$td : 'BỔ SUNG XE SÀN',
            'bien_override' => $swap ? $td : ($san !== '' ? $san : null),
        ];
    }
}
