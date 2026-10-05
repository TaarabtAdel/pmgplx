<?php

namespace App\Support\DaoTao\LichThucHanh;

final class CabinQueue
{
    /**
     * @param  array<int, list<string>>  $workDaysByCap
     * @return array<int, string> cap_stt => iso
     */
    public function datesByCap(array $workDaysByCap, ?string $cabinNeo = null): array
    {
        $ref = $workDaysByCap[1] ?? reset($workDaysByCap) ?: [];
        if ($cabinNeo !== null) {
            $anchorIdx = array_search($cabinNeo, $ref, true);
        } else {
            $anchorIdx = 4;
        }
        if ($anchorIdx === false) {
            $anchorIdx = 4;
        }
        $out = [];
        $caps = array_keys($workDaysByCap);
        sort($caps);
        foreach ($caps as $stt) {
            $days = $workDaysByCap[$stt] ?? $ref;
            $localAnchor = $cabinNeo !== null ? array_search($cabinNeo, $days, true) : false;
            if ($localAnchor === false) {
                $target = $anchorIdx + ((int) $stt - 1);
                $out[$stt] = $ref[$target] ?? ($days[$target] ?? '');
            } else {
                $out[$stt] = $days[$localAnchor + ((int) $stt - 1)] ?? $days[$localAnchor] ?? '';
            }
        }

        return $out;
    }
}
