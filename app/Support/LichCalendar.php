<?php

namespace App\Support;

use Carbon\Carbon;

class LichCalendar
{
    /**
     * Build HTML calendars spanning course months (NgayKG → NgayBG).
     * Every day of each month is selectable (no past/future filtering).
     */
    public static function render(?Carbon $from, ?Carbon $to): string
    {
        if (!$from || !$to || $from->gt($to)) {
            $from = Carbon::today()->startOfMonth();
            $to = Carbon::today()->addMonths(2)->endOfMonth();
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $html = '';
        $cursor = $from->copy()->startOfMonth();
        $lastMonth = $to->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $html .= '<h5 class="section-title">Tháng '.$cursor->format('m/Y').'</h5>';
            $html .= '<div class="day-calendar">';

            $daysInMonth = $cursor->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $cursor->copy()->day($day)->startOfDay();
                $iso = $date->format('Y-m-d');
                $html .= '<div class="day" onclick="chonNgay(this,\''.$iso.'\')">'.$day.'</div>';
            }

            $html .= '</div>';
            $cursor->addMonth();
        }

        return $html;
    }

    /**
     * Parse "Y-m-d,Y-m-d,..." into unique sorted date strings.
     *
     * @return list<string>
     */
    public static function parseSelectedDates(?string $csv): array
    {
        if (!$csv) {
            return [];
        }

        $dates = array_filter(array_map('trim', explode(',', $csv)));
        $dates = array_values(array_unique($dates));
        sort($dates);

        return $dates;
    }

    public static function combineDateAndTime(string $date, string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $date.' '.substr($time, 0, 5));
    }
}
