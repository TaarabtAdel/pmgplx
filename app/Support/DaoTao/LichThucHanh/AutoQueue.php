<?php

namespace App\Support\DaoTao\LichThucHanh;

final class AutoQueue
{
    /**
     * @param  array<int, list<string>>  $workDaysByCap
     * @return array<int, list<string>> cap_stt => [iso, iso]
     */
    public function blocksByCap(array $workDaysByCap, ?string $autoNeo = null): array
    {
        $ref = $workDaysByCap[1] ?? reset($workDaysByCap) ?: [];
        if ($autoNeo !== null) {
            $startIdx = array_search($autoNeo, $ref, true);
        } else {
            $startIdx = 5;
        }
        if ($startIdx === false) {
            $startIdx = 5;
        }
        $out = [];
        $caps = array_keys($workDaysByCap);
        sort($caps);
        foreach ($caps as $stt) {
            $days = $workDaysByCap[$stt] ?? $ref;
            $baseRef = $startIdx + (((int) $stt - 1) * 2);
            $iso1 = $ref[$baseRef] ?? null;
            $iso2 = $ref[$baseRef + 1] ?? null;
            if ($iso1 !== null && $iso2 !== null) {
                if (! in_array($iso1, $days, true) || ! in_array($iso2, $days, true)) {
                    $local = array_values(array_filter($days, fn ($d) => $d >= ($iso1 ?? '')));
                    $iso1 = $local[0] ?? $iso1;
                    $iso2 = $local[1] ?? $iso2;
                }
                $out[$stt] = [$iso1, $iso2];
            }
        }

        return $out;
    }
}
