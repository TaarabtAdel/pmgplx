<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use Illuminate\Support\Collection;

/** Danh sách giáo viên thực hành trên lịch PMGPLX của khoá. */
final class SoTayGiaoVienThucHanhGiaoVien
{
    /**
     * @return list<array{ma_gv: string, ho_ten: string}>
     */
    public static function danhSach(string $maKh): array
    {
        $lich = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->whereNotNull('MaGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'TenGV', 'TenMonHoc']);

        $th = $lich->filter(fn (KhoaHocGiaoVien $r) => self::laMonThucHanh($r->TenMonHoc));
        if ($th->isEmpty()) {
            $th = $lich;
        }

        /** @var Collection<string, array{ma_gv: string, ho_ten: string}> $uniq */
        $uniq = $th
            ->map(function (KhoaHocGiaoVien $r): array {
                $ma = trim((string) $r->MaGV);

                return [
                    'ma_gv' => $ma,
                    'ho_ten' => self::hoTenGv($ma, $r->TenGV),
                ];
            })
            ->filter(fn (array $row) => $row['ma_gv'] !== '')
            ->keyBy('ma_gv');

        return $uniq->values()->all();
    }

    private static function laMonThucHanh(?string $tenMon): bool
    {
        $l = mb_strtolower(trim((string) $tenMon));

        return str_contains($l, 'thực hành')
            || str_contains($l, 'thuc hanh')
            || str_contains($l, 'lai xe');
    }

    private static function hoTenGv(string $maGv, ?string $tenTrenLich): string
    {
        $gv = GiaoVien::query()->find($maGv);
        if ($gv instanceof GiaoVien) {
            $hoTen = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));
            if ($hoTen !== '') {
                return $hoTen;
            }
        }

        return trim((string) $tenTrenLich) !== '' ? trim((string) $tenTrenLich) : $maGv;
    }
}
