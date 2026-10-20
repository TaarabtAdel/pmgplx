<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongHocVien;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Một học viên có thể có nhiều dòng phân công (mỗi dòng một khoảng ngày).
 * Chọn dòng áp dụng cho một ngày cụ thể.
 */
final class DatPhanCongHocVienTheoNgay
{
    /**
     * @param  iterable<DatPhanCongHocVien>  $rows
     * @return array<string, list<DatPhanCongHocVien>> MaHocVien => các dòng, sắp theo TuNgay
     */
    public static function nhomTheoHocVien(iterable $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $ma = trim((string) ($row->MaHocVien ?? ''));
            if ($ma !== '') {
                $out[$ma][] = $row;
            }
        }
        foreach ($out as &$list) {
            usort($list, [self::class, 'soSanhTuNgay']);
        }
        unset($list);

        return $out;
    }

    /** @param  list<DatPhanCongHocVien>  $rows */
    public static function chonTheoNgay(array $rows, ?string $ngay): ?DatPhanCongHocVien
    {
        if ($rows === []) {
            return null;
        }
        if ($ngay === null || $ngay === '') {
            return self::hienTai($rows);
        }
        foreach ($rows as $row) {
            if ($row->apDungNgay($ngay)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Dòng áp dụng tại $ngay; nếu ngày rơi vào khoảng trống thì lấy dòng gần nhất trước đó
     * (hoặc dòng đầu tiên nếu $ngay trước mọi khoảng).
     *
     * @param  list<DatPhanCongHocVien>  $rows
     */
    public static function chonGanNhat(array $rows, string $ngay): ?DatPhanCongHocVien
    {
        $exact = self::chonTheoNgay($rows, $ngay);
        if ($exact !== null || $rows === []) {
            return $exact;
        }

        usort($rows, [self::class, 'soSanhTuNgay']);
        $truoc = null;
        foreach ($rows as $row) {
            if (($row->TuNgay?->toDateString() ?? '0000-01-01') <= $ngay) {
                $truoc = $row;
            }
        }

        return $truoc ?? $rows[0];
    }

    /**
     * Dòng đang áp dụng hôm nay; nếu không có thì dòng mới nhất.
     *
     * @param  list<DatPhanCongHocVien>  $rows
     */
    public static function hienTai(array $rows): ?DatPhanCongHocVien
    {
        if ($rows === []) {
            return null;
        }
        $homNay = Carbon::today()->toDateString();
        foreach ($rows as $row) {
            if ($row->apDungNgay($homNay)) {
                return $row;
            }
        }
        usort($rows, [self::class, 'soSanhTuNgay']);

        return $rows[count($rows) - 1];
    }

    /**
     * Hai khoảng [tuA, denA] và [tuB, denB] (NULL = mở) có giao nhau.
     */
    public static function khoangGiaoNhau(?string $tuA, ?string $denA, ?string $tuB, ?string $denB): bool
    {
        return ($tuA ?? '0000-01-01') <= ($denB ?? '9999-12-31')
            && ($tuB ?? '0000-01-01') <= ($denA ?? '9999-12-31');
    }

    /** @param  Collection<int, DatPhanCongHocVien>|list<DatPhanCongHocVien>  $rows */
    public static function coChongKhoang(iterable $rows): bool
    {
        $list = is_array($rows) ? $rows : $rows->all();
        $n = count($list);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if (self::khoangGiaoNhau(
                    $list[$i]->TuNgay?->toDateString(),
                    $list[$i]->DenNgay?->toDateString(),
                    $list[$j]->TuNgay?->toDateString(),
                    $list[$j]->DenNgay?->toDateString()
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function soSanhTuNgay(DatPhanCongHocVien $a, DatPhanCongHocVien $b): int
    {
        $ta = $a->TuNgay?->toDateString() ?? '0000-01-01';
        $tb = $b->TuNgay?->toDateString() ?? '0000-01-01';

        return [$ta, (int) $a->Id] <=> [$tb, (int) $b->Id];
    }
}
