<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\GiaoVien;

/** Học viên khoá — bảng «nghỉ học» trên sổ tay GV. */
final class SoTayGiaoVienHocVien
{
    public const SO_DONG_BANG = 100;
    /**
     * @return list<array{stt: int, ho_ten: string}>
     */
    public static function bangNghi(string $maKh): array
    {
        $rows = DanhSachHocSinhLaiXeHocVien::dongXuat($maKh);

        return array_map(static fn (array $r): array => [
            'stt' => (int) $r['stt'],
            'ho_ten' => mb_strtoupper(trim((string) $r['ho_ten'])),
        ], $rows);
    }

    public static function soLuong(string $maKh): int
    {
        return count(self::bangNghi($maKh));
    }

    public static function hoTenTuMa(string $maGv): ?string
    {
        $ma = trim($maGv);
        if ($ma === '') {
            return null;
        }

        $gv = GiaoVien::query()->find($ma);
        if (! $gv instanceof GiaoVien) {
            return null;
        }

        $hoTen = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));

        return $hoTen !== '' ? $hoTen : $ma;
    }

    /**
     * Đủ {@see SO_DONG_BANG} dòng: có HV thì điền, còn lại để trống cho ghi tay.
     *
     * @param  list<array{stt: int|string, ho_ten: string}>  $coDuLieu
     * @return list<array{stt: int|string, ho_ten: string}>
     */
    public static function dayBangMotTram(array $coDuLieu): array
    {
        if (count($coDuLieu) > self::SO_DONG_BANG) {
            throw new \InvalidArgumentException(
                'Khoá có '.count($coDuLieu).' học viên; bảng sổ tay tối đa '.self::SO_DONG_BANG.'.'
            );
        }

        $out = $coDuLieu;
        while (count($out) < self::SO_DONG_BANG) {
            $out[] = ['stt' => '', 'ho_ten' => ''];
        }

        return $out;
    }
}
