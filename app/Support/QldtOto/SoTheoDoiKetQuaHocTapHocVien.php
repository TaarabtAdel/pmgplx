<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\NguoiLX;
use App\Support\PMGPLX\NgayVn;

/** Học viên + kết quả học tập PMGPLX theo khoá — xuất sổ theo dõi. */
final class SoTheoDoiKetQuaHocTapHocVien
{
    /**
     * @return list<array{
     *     stt: int,
     *     ho_ten: string,
     *     khoa_hoc: string,
     *     ly_thuyet: string,
     *     thuc_hanh: string,
     *     ket_qua_khoa: string,
     *     ngay_hoan_thanh: string,
     *     xac_nhan: string,
     * }>
     */
    public static function dongXuat(string $maKh, string $nhanKhoaHoc): array
    {
        $rows = NguoiLX::query()
            ->from('NguoiLX as n')
            ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('h.MaKhoaHoc', $maKh)
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK')
            ->get([
                'n.HoVaTen', 'n.HoDemNLX', 'n.TenNLX',
                'h.DiemKQLyThuyet', 'h.DiemKQThucHanh', 'h.KetLuanCSDT', 'h.NgayRaQDTN',
            ]);

        $out = [];
        foreach ($rows->values() as $i => $n) {
            $hoTen = trim((string) ($n->HoVaTen ?? ''));
            if ($hoTen === '') {
                $hoTen = trim(($n->HoDemNLX ?? '').' '.($n->TenNLX ?? ''));
            }

            $out[] = [
                'stt' => $i + 1,
                'ho_ten' => mb_strtoupper($hoTen),
                'khoa_hoc' => $nhanKhoaHoc,
                'ly_thuyet' => self::nhanDiemKq($n->DiemKQLyThuyet ?? null),
                'thuc_hanh' => self::nhanDiemKq($n->DiemKQThucHanh ?? null),
                'ket_qua_khoa' => self::nhanKetLuanKhoa($n->KetLuanCSDT ?? null),
                'ngay_hoan_thanh' => NgayVn::format($n->NgayRaQDTN ?? null, 'yyyymmdd'),
                'xac_nhan' => '',
            ];
        }

        return $out;
    }

    private static function nhanDiemKq(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            $f = (float) $value;
            if ($f <= 0) {
                return '';
            }

            return rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }

    private static function nhanKetLuanKhoa(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (int) $value === 1 ? 'Đạt' : 'Không đạt';
    }
}
