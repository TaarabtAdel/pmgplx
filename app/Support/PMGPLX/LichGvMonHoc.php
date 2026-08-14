<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\DmMonHoc;
use Illuminate\Support\Collection;

class LichGvMonHoc
{
    public static function normalizeMa(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Quy ước DB lịch TH nhập file: MaMonHoc = NULL, TenMonHoc = mã số MaMH.
     *
     * @return array{MaMonHoc: null, TenMonHoc: string}
     */
    public static function dbFields(mixed $selectedMaMh): array
    {
        $ma = self::normalizeMa($selectedMaMh);

        return [
            'MaMonHoc' => null,
            'TenMonHoc' => $ma !== null ? (string) $ma : '',
        ];
    }

    /** Mã môn đã chọn (dropdown) từ dữ liệu session/DB cũ. */
    public static function resolveSelected(mixed $rowMaMonHoc, mixed $rowTenMonHoc, ?int $defaultMa = null): ?int
    {
        $fromMa = self::normalizeMa($rowMaMonHoc);
        if ($fromMa !== null) {
            return $fromMa;
        }

        $ten = trim((string) ($rowTenMonHoc ?? ''));
        if ($ten !== '' && preg_match('/^\d+$/', $ten)) {
            return (int) $ten;
        }

        if ($ten !== '') {
            $found = DmMonHoc::active()
                ->where('TenMH', $ten)
                ->orWhere('TenMH', 'like', '%'.$ten.'%')
                ->orderBy('MaMH')
                ->value('MaMH');

            if ($found !== null) {
                return (int) $found;
            }
        }

        return $defaultMa;
    }

    /** Hiển thị tên môn (TenMonHoc trong DB có thể là mã số). */
    public static function displayLabel(?string $tenMonHoc, mixed $maMonHoc = null, ?Collection $monMap = null): string
    {
        $ten = trim((string) ($tenMonHoc ?? ''));
        $ma = self::normalizeMa($maMonHoc);

        if ($ten !== '' && preg_match('/^\d+$/', $ten)) {
            $maFromTen = (int) $ten;
            if ($monMap instanceof Collection) {
                $name = $monMap->get($maFromTen) ?? $monMap->get((string) $maFromTen);
                if (is_string($name) && $name !== '') {
                    return $name;
                }
            }

            return $ten;
        }

        if ($ten !== '') {
            return $ten;
        }

        if ($ma !== null && $monMap instanceof Collection) {
            $name = $monMap->get($ma) ?? $monMap->get((string) $ma);
            if (is_string($name) && $name !== '') {
                return $name;
            }

            return (string) $ma;
        }

        return '—';
    }
}
