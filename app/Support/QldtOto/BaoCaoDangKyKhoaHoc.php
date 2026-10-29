<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BaoCaoDangKyKhoaHoc
{
    /** Nhãn hạng GPLX trên báo cáo (theo mẫu Word). */
    public static function nhanHangGplx(?string $hangGplx): string
    {
        $code = mb_strtoupper(trim((string) $hangGplx));
        return match ($code) {
            'B1' => 'B (số sàn)',
            'B11' => 'B (tự động)',
            default => trim((string) $hangGplx) !== '' ? trim((string) $hangGplx) : '—',
        };
    }

    /** Nhãn hạng trên mẫu quyết định thành lập lớp (viết hoa «Số sàn»). */
    public static function nhanHangGplxQuyetDinh(?string $hangGplx): string
    {
        $code = mb_strtoupper(trim((string) $hangGplx));
        return match ($code) {
            'B1' => 'B (Số sàn)',
            'B11' => 'B (Tự động)',
            default => trim((string) $hangGplx) !== '' ? trim((string) $hangGplx) : '—',
        };
    }

    /** Số khóa (vd. 56) từ TenKH dạng …BK56 hoặc …K56. */
    public static function soKhoaTuTen(?string $tenKh): ?string
    {
        $ten = trim((string) $tenKh);
        if ($ten === '') {
            return null;
        }
        if (preg_match('/BK(\d+)/i', $ten, $m)) {
            return $m[1];
        }
        if (preg_match('/K(\d+)\s*$/i', $ten, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function dongNgayThangNam(CarbonInterface $date): string
    {
        return sprintf(
            '%02d tháng %02d năm %d',
            (int) $date->format('d'),
            (int) $date->format('m'),
            (int) $date->format('Y')
        );
    }

    public static function dinhDangNgay(?CarbonInterface $date): string
    {
        return $date ? $date->format('d/m/Y') : '';
    }

    public static function demHocVien(string $maKh): int
    {
        return (int) DB::connection('sqlsrv')
            ->table('NguoiLX_HoSo')
            ->where('MaKhoaHoc', $maKh)
            ->count();
    }

    /**
     * @return array{
     *   hang_dt: string,
     *   khoa_so: string,
     *   khoa_lop: string,
     *   hang_gplx: string,
     *   so_luong_hv: int,
     *   ngay_kg: string,
     *   ngay_bg: string,
     * }
     */
    public static function tuKhoaHoc(KhoaHoc $khoa): array
    {
        $soHv = self::demHocVien((string) $khoa->MaKH);
        if ($khoa->TongSoHV !== null && (int) $khoa->TongSoHV > 0) {
            $soHv = max($soHv, (int) $khoa->TongSoHV);
        }

        $khoaSo = self::soKhoaTuTen($khoa->TenKH) ?? '';

        return [
            'hang_dt' => trim((string) ($khoa->HangDT ?? '')),
            'khoa_so' => $khoaSo,
            'khoa_lop' => (string) $khoa->MaKH,
            'hang_gplx' => self::nhanHangGplx($khoa->HangGPLX ?? null),
            'so_luong_hv' => $soHv,
            'ngay_kg' => self::dinhDangNgay($khoa->NgayKG),
            'ngay_bg' => self::dinhDangNgay($khoa->NgayBG),
        ];
    }
}
