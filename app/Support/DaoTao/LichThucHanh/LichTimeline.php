<?php

namespace App\Support\DaoTao\LichThucHanh;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

final class LichTimeline
{
    /** @param array<string, mixed> $cauHinh */
    public function __construct(
        private readonly array $cauHinh,
    ) {}

    /** @return list<string> */
    public function calendarDates(): array
    {
        $start = Carbon::parse(LichNgay::normalizeDate($this->cauHinh['ngay_khai_giang'] ?? ''));
        $endIso = LichNgay::normalizeDate($this->cauHinh['lich_den_ngay'] ?? $this->cauHinh['ngay_ket_thuc_du_kien'] ?? '');
        if ($endIso !== '' && $endIso >= $start->toDateString()) {
            $end = Carbon::parse($endIso);
        } else {
            $end = $start->copy()->addDays(47);
        }
        $out = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $out[] = $d->toDateString();
        }

        return $out;
    }

    /**
     * @param  list<string>  $dates
     * @return list<string>
     */
    public function workDaysForCap(array $dates, int $capStt): array
    {
        $out = [];
        foreach ($dates as $iso) {
            $d = Carbon::parse($iso);
            if (LichNgay::laNgayNghi($d, $this->cauHinh, null, $capStt)) {
                continue;
            }
            $out[] = $iso;
        }

        return $out;
    }

    /** @param list<string> $workDays */
    public function nthWorkDay(array $workDays, int $n): ?string
    {
        $idx = $n - 1;
        if ($idx < 0 || $idx >= count($workDays)) {
            return null;
        }

        return $workDays[$idx];
    }

    public function mauPhucTapForDate(string $iso): string
    {
        $pivot = '2026-09-07';

        return $iso >= $pivot ? 'phuc_tap_sau' : 'phuc_tap_giai_doan_dau';
    }
}
