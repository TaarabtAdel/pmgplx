<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use Illuminate\Support\Collection;

/** Giáo viên lý thuyết (online / LT) trên lịch PMGPLX — loại trừ môn thực hành lái xe. */
final class SoTayGiaoVienLyThuyetGiaoVien
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

        $lt = $lich->filter(fn (KhoaHocGiaoVien $r) => self::laMonLyThuyet($r->TenMonHoc));
        if ($lt->isEmpty()) {
            $lt = $lich->reject(fn (KhoaHocGiaoVien $r) => self::laMonThucHanh($r->TenMonHoc));
        }

        /** @var Collection<string, array{ma_gv: string, ho_ten: string}> $uniq */
        $uniq = $lt
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
            || (str_contains($l, 'lái xe') && ! str_contains($l, 'lý thuyết') && ! str_contains($l, 'ly thuyet'));
    }

    private static function laMonLyThuyet(?string $tenMon): bool
    {
        if (self::laMonThucHanh($tenMon)) {
            return false;
        }
        $l = mb_strtolower(trim((string) $tenMon));

        return str_contains($l, 'lý thuyết')
            || str_contains($l, 'ly thuyet')
            || str_contains($l, 'online')
            || str_contains($l, 'mô phỏng')
            || str_contains($l, 'mo phong')
            || str_contains($l, 'môn 1')
            || str_contains($l, 'mon 1');
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
