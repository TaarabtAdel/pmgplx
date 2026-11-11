<?php

namespace App\Support\PMGPLX;

class LichExcelTimeParser
{
    /**
     * Parse cột Bắt đầu / Kết thúc thành danh sách khung giờ.
     *
     * - "5H59'" + "13H59'" → 1 slot
     * - "13H59' -17H59'" + "18H-22H" → 2 slots (mỗi cột là 1 khoảng)
     *
     * @return list<array{start: string, end: string}>
     */
    public static function expandSlots(string $batDau, string $ketThuc): array
    {
        $batDau = trim($batDau);
        $ketThuc = trim($ketThuc);

        if ($batDau === '' && $ketThuc === '') {
            return [];
        }

        $bdRange = self::isRange($batDau);
        $ktRange = self::isRange($ketThuc);

        if ($bdRange || $ktRange) {
            $slots = [];
            if ($bdRange) {
                $parsed = self::parseRange($batDau);
                if ($parsed !== null) {
                    $slots[] = $parsed;
                }
            } elseif ($batDau !== '' && $ketThuc !== '' && ! $ktRange) {
                $slots[] = [
                    'start' => self::parseTime($batDau) ?? '00:00',
                    'end' => self::parseTime($ketThuc) ?? '00:00',
                ];
            }

            if ($ktRange) {
                $parsed = self::parseRange($ketThuc);
                if ($parsed !== null) {
                    $slots[] = $parsed;
                }
            }

            return $slots;
        }

        $start = self::parseTime($batDau);
        $end = self::parseTime($ketThuc);
        if ($start === null || $end === null) {
            return [];
        }

        return [['start' => $start, 'end' => $end]];
    }

    public static function isRange(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        // Có dấu - / – ngăn cách 2 mốc giờ (không phải biển số)
        return (bool) preg_match('/\dH.*\s*[-–]\s*.*\d/iu', $value);
    }

    /**
     * @return array{start: string, end: string}|null
     */
    public static function parseRange(string $value): ?array
    {
        $parts = preg_split('/\s*[-–]\s*/u', trim($value), 2);
        if (! is_array($parts) || count($parts) < 2) {
            return null;
        }

        $start = self::parseTime($parts[0]);
        $end = self::parseTime($parts[1]);
        if ($start === null || $end === null) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * "5H59'" / "7H" / "13H59" / "05:59" → "HH:MM"
     */
    public static function parseTime(string $value): ?string
    {
        $value = trim($value);
        $value = str_replace("'", '', $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        if (preg_match('/^(\d{1,2})\s*[Hh]\s*(\d{1,2})?$/u', $value, $m)) {
            $h = (int) $m[1];
            $i = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

            return sprintf('%02d:%02d', $h, $i);
        }

        return null;
    }

    /**
     * Parse cột ngày lịch — chỉ chấp nhận <strong>dd/mm/yyyy</strong> (dấu / - hoặc .).
     *
     * @return string|null Y-m-d
     */
    public static function parseDate(string $value): ?string
    {
        $parsed = self::parseDateDmY($value);

        return $parsed['ok'] ? $parsed['iso'] : null;
    }

    /**
     * @return array{ok: true, iso: string, display: string}|array{ok: false, error: string}
     */
    public static function parseDateDmY(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['ok' => false, 'error' => 'Ngày trống.'];
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $value, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];

            if ($month < 1 || $month > 12) {
                return [
                    'ok' => false,
                    'error' => 'Tháng không hợp lệ (phải từ 1 đến 12, đang là '.$month.'). Dùng định dạng dd/mm/yyyy.',
                ];
            }

            if ($day < 1 || $day > 31) {
                return [
                    'ok' => false,
                    'error' => 'Ngày không hợp lệ (đang là '.$day.'). Dùng định dạng dd/mm/yyyy.',
                ];
            }

            if (! checkdate($month, $day, $year)) {
                return [
                    'ok' => false,
                    'error' => sprintf('Ngày %02d/%02d/%04d không tồn tại trong lịch.', $day, $month, $year),
                ];
            }

            $iso = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $display = sprintf('%02d/%02d/%04d', $day, $month, $year);

            return ['ok' => true, 'iso' => $iso, 'display' => $display];
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            return [
                'ok' => false,
                'error' => 'Không dùng yyyy-mm-dd. Vui lòng ghi ngày theo định dạng dd/mm/yyyy (ví dụ 18/08/2026).',
            ];
        }

        return [
            'ok' => false,
            'error' => 'Ngày «'.$value.'» không đúng định dạng dd/mm/yyyy (ví dụ 18/08/2026).',
        ];
    }
}
