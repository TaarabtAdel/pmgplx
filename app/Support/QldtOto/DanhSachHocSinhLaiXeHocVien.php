<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\NguoiLX;
use App\Support\PMGPLX\DmDvhcTenDayDu;
use App\Support\PMGPLX\NgayVn;

/** Học viên PMGPLX theo khoá — phục vụ xuất danh sách học sinh lái xe. */
final class DanhSachHocSinhLaiXeHocVien
{
    /**
     * @return list<array{
     *     stt: int,
     *     ho_ten: string,
     *     ngay_sinh: string,
     *     gioi_tinh: string,
     *     quoc_tich: string,
     *     so_cmnd: string,
     *     dia_chi_thuong_tru: string,
     *     noi_cu_tru: string,
     *     ghi_chu: string,
     * }>
     */
    public static function dongXuat(string $maKh): array
    {
        $rows = NguoiLX::query()
            ->from('NguoiLX as n')
            ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('h.MaKhoaHoc', $maKh)
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK')
            ->get([
                'n.HoVaTen', 'n.HoDemNLX', 'n.TenNLX', 'n.NgaySinh', 'n.GioiTinh',
                'n.MaQuocTich', 'n.SoCMT', 'n.GhiChu',
                'n.NoiTT', 'n.NoiTT_MaDVHC', 'n.NoiCT', 'n.NoiCT_MaDVHC',
            ]);

        $dvhcMap = DmDvhcTenDayDu::mapForHocVien($rows);

        $out = [];
        foreach ($rows->values() as $i => $n) {
            $diaChi = DmDvhcTenDayDu::diaChiTuNguoiLx($n, $dvhcMap);
            $hoTen = trim((string) ($n->HoVaTen ?? ''));
            if ($hoTen === '') {
                $hoTen = trim(($n->HoDemNLX ?? '').' '.($n->TenNLX ?? ''));
            }

            $out[] = [
                'stt' => $i + 1,
                'ho_ten' => $hoTen,
                'ngay_sinh' => NgayVn::format($n->NgaySinh ?? null, 'yyyymmdd'),
                'gioi_tinh' => self::gioiTinhLabel($n->GioiTinh ?? null),
                'quoc_tich' => self::quocTichLabel($n->MaQuocTich ?? null),
                'so_cmnd' => trim((string) ($n->SoCMT ?? '')),
                'dia_chi_thuong_tru' => $diaChi['thuong_tru'],
                'noi_cu_tru' => $diaChi['cu_tru'],
                'ghi_chu' => trim((string) ($n->GhiChu ?? '')),
            ];
        }

        return $out;
    }

    public static function gioiTinhLabel(mixed $value): string
    {
        $v = mb_strtoupper(trim((string) $value));

        return match ($v) {
            'F', 'N', 'NU', 'NỮ' => 'Nữ',
            'M', 'NAM' => 'Nam',
            default => $v !== '' ? $v : '',
        };
    }

    public static function quocTichLabel(mixed $ma): string
    {
        $m = mb_strtoupper(trim((string) $ma));

        return match ($m) {
            'VNM', 'VN', '' => 'Việt Nam',
            default => $m,
        };
    }

    /** Hạng trên dòng tiêu đề (vd. «HẠNG B»). */
    public static function hangTieuDe(?string $hangGplx, ?string $hangDt): string
    {
        $gplx = mb_strtoupper(trim((string) $hangGplx));
        if ($gplx !== '') {
            if (str_starts_with($gplx, 'B')) {
                return 'B';
            }

            return $gplx;
        }

        $dt = mb_strtoupper(trim((string) $hangDt));

        return $dt !== '' ? $dt : 'B';
    }
}
