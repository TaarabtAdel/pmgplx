<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Đếm HV đạt chương trình DAT (cùng logic tổng hợp học viên, dat_ct = dat). */
final class PhuLuc5DatDemHocVien
{
    /**
     * @return array{
     *   B: int,
     *   B.01: int,
     *   C1: int,
     *   khac: int,
     *   tong: int,
     *   chi_tiet: list<array{hang: string, so: int}>
     * }
     */
    public static function demTheoHang(Request|array $input): array
    {
        $filters = $input instanceof Request
            ? DatDSPhienBoLoc::parseFilters($input)
            : self::filtersTuMang($input);

        $filters['dat_ct'] = 'dat';

        if (($filters['ma_khoa_hoc'] ?? '') === '') {
            return self::rong();
        }

        $dieuKienByHang = DatDieuKienDat::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get()
            ->keyBy('Hang');

        $rows = DatHocVienTongHop::aggregatedHocVien($filters, $dieuKienByHang);

        $bTuDong = 0;
        $bCoKhi = 0;
        $c1 = 0;
        $khac = 0;

        foreach ($rows as $row) {
            $hang = trim((string) ($row->LoaiKhoaHoc ?? ''));
            match (self::chuongHang($hang)) {
                'b_tu_dong' => $bTuDong++,
                'b_co_khi' => $bCoKhi++,
                'c1' => $c1++,
                default => $khac++,
            };
        }

        return [
            'B' => $bTuDong,
            'B.01' => $bCoKhi,
            'C1' => $c1,
            'khac' => $khac,
            'tong' => $rows->count(),
            'chi_tiet' => self::chiTietTheoHangGoc($rows),
        ];
    }

    /** @param  array<string, mixed>  $input */
    private static function filtersTuMang(array $input): array
    {
        return DatDSPhienBoLoc::parseFilters(new Request($input));
    }

    /**
     * @return array{B: int, B.01: int, C1: int, khac: int, tong: int, chi_tiet: list<array{hang: string, so: int}>}
     */
    private static function rong(): array
    {
        return [
            'B' => 0,
            'B.01' => 0,
            'C1' => 0,
            'khac' => 0,
            'tong' => 0,
            'chi_tiet' => [],
        ];
    }

  /**
     * @param  Collection<int, object>  $rows
     * @return list<array{hang: string, so: int}>
     */
    private static function chiTietTheoHangGoc(Collection $rows): array
    {
        $dem = [];
        foreach ($rows as $row) {
            $hang = trim((string) ($row->LoaiKhoaHoc ?? '')) ?: '(trống)';
            $dem[$hang] = ($dem[$hang] ?? 0) + 1;
        }
        ksort($dem);
        $out = [];
        foreach ($dem as $hang => $so) {
            $out[] = ['hang' => (string) $hang, 'so' => (int) $so];
        }

        return $out;
    }

    private static function chuongHang(string $hang): string
    {
        $h = strtoupper(str_replace(' ', '', $hang));

        return match (true) {
            $h === 'B' || $h === 'B1' || str_contains($h, 'TUDONG') || $h === 'B11' => 'b_tu_dong',
            $h === 'B.01' || $h === 'B01' || $h === 'B2' => 'b_co_khi',
            str_starts_with($h, 'C1') => 'c1',
            default => 'khac',
        };
    }
}