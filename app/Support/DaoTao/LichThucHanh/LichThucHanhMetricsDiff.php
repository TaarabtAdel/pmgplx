<?php

namespace App\Support\DaoTao\LichThucHanh;

final class LichThucHanhMetricsDiff
{
    /**
     * @param  array<string, array<string, mixed>>  $expected
     * @param  array<string, array<string, mixed>>  $actual
     * @return list<array{ma_gv: string, metric: string, expected: mixed, actual: mixed}>
     */
    public function diff(array $expected, array $actual, float $gioEpsilon = 0.5): array
    {
        $rows = [];
        foreach ($expected as $ma => $exp) {
            $act = $actual[$ma] ?? null;
            if ($act === null) {
                $rows[] = ['ma_gv' => $ma, 'metric' => 'missing_gv', 'expected' => 'present', 'actual' => 'missing'];

                continue;
            }
            if (($exp['so_ngay_lam'] ?? null) !== ($act['so_ngay_lam'] ?? null)) {
                $rows[] = ['ma_gv' => $ma, 'metric' => 'so_ngay_lam', 'expected' => $exp['so_ngay_lam'], 'actual' => $act['so_ngay_lam']];
            }
            if (($exp['cabin_ngay'] ?? null) !== ($act['cabin_ngay'] ?? null)) {
                $rows[] = ['ma_gv' => $ma, 'metric' => 'cabin_ngay', 'expected' => $exp['cabin_ngay'], 'actual' => $act['cabin_ngay']];
            }
            if (($exp['auto_block'] ?? []) !== ($act['auto_block'] ?? [])) {
                $rows[] = ['ma_gv' => $ma, 'metric' => 'auto_block', 'expected' => $exp['auto_block'], 'actual' => $act['auto_block']];
            }
            if (($exp['ngay_cuoi'] ?? null) !== ($act['ngay_cuoi'] ?? null)) {
                $rows[] = ['ma_gv' => $ma, 'metric' => 'ngay_cuoi', 'expected' => $exp['ngay_cuoi'], 'actual' => $act['ngay_cuoi']];
            }
            foreach ($exp['gio_theo_loai'] ?? [] as $loai => $gioExp) {
                $gioAct = $act['gio_theo_loai'][$loai] ?? 0;
                if (abs((float) $gioExp - (float) $gioAct) > $gioEpsilon) {
                    $rows[] = ['ma_gv' => $ma, 'metric' => "gio.$loai", 'expected' => $gioExp, 'actual' => $gioAct];
                }
            }
        }

        return $rows;
    }

    /** @param list<array<string, mixed>> $rows */
    public function formatTable(array $rows): string
    {
        if ($rows === []) {
            return "Khớp file tham chiếu.\n";
        }
        $lines = ['Lệch so với file tham chiếu:', str_repeat('-', 72)];
        foreach ($rows as $r) {
            $lines[] = sprintf(
                '%s | %s | expected=%s | actual=%s',
                $r['ma_gv'],
                $r['metric'],
                is_array($r['expected']) ? json_encode($r['expected']) : $r['expected'],
                is_array($r['actual']) ? json_encode($r['actual']) : $r['actual']
            );
        }

        return implode("\n", $lines)."\n";
    }
}
