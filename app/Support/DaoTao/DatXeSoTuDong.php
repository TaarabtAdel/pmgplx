<?php

namespace App\Support\DaoTao;

use App\Models\PMGPLX\XeTap;
use App\Support\PMGPLX\LichExcelBienSo;

class DatXeSoTuDong
{
    /** @var list<string>|null */
    private static ?array $bienSoCache = null;

    public static function isHangTuDong(?string $hangGplxXe): bool
    {
        $hang = strtoupper(trim((string) $hangGplxXe));
        if ($hang === '') {
            return false;
        }

        foreach (preg_split('/[\s,\/\+;]+/', $hang) ?: [] as $token) {
            if (trim($token) === 'B11') {
                return true;
            }
        }

        return false;
    }

    public static function normalizeBienSo(?string $bienSo): string
    {
        return LichExcelBienSo::normalize((string) $bienSo);
    }

    /**
     * @return list<string>
     */
    public static function bienSoTuDong(): array
    {
        if (self::$bienSoCache !== null) {
            return self::$bienSoCache;
        }

        self::$bienSoCache = XeTap::query()
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->get(['BienSoXe', 'HangGPLXXe'])
            ->filter(fn (XeTap $xe): bool => self::isHangTuDong($xe->HangGPLXXe))
            ->map(fn (XeTap $xe): string => self::normalizeBienSo($xe->BienSoXe))
            ->filter(fn (string $bienSo): bool => $bienSo !== '')
            ->unique()
            ->values()
            ->all();

        return self::$bienSoCache;
    }

    public static function sqlNormalizedBienSo(string $column): string
    {
        return "UPPER(REPLACE(REPLACE(REPLACE(LTRIM(RTRIM({$column})), '-', ''), '.', ''), ' ', ''))";
    }

    public static function sqlSumGioTuDong(string $sessionTable = 'DatDSPhien'): string
    {
        $plates = self::bienSoTuDong();
        $normalizedExpr = self::sqlNormalizedBienSo("{$sessionTable}.BienSoXe");

        if ($plates === []) {
            return '0';
        }

        $quoted = implode(', ', array_map(
            static fn (string $plate): string => "'".str_replace("'", "''", $plate)."'",
            $plates
        ));

        return "SUM(CASE WHEN {$normalizedExpr} IN ({$quoted}) THEN COALESCE({$sessionTable}.ThoiGianThucHanhGio, 0) ELSE 0 END)";
    }
}
