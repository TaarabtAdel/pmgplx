<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\GiaoVien;
use App\Support\DaoTao\DatPhanCongHocVienSaver;
use Illuminate\Support\Collection;

/**
 * Đối chiếu mã + tên giáo viên trong file nhập lịch với danh mục GiaoVien (PMGPLX).
 * Tên so sánh không phân biệt hoa/thường, bỏ khoảng trắng thừa.
 */
final class KiemTraGiaoVienNhapLich
{
    public const KHOP = 'khop';

    public const LECH_TEN = 'lech_ten';

    public const KHONG_CO_MA = 'khong_co_ma';

    public const THIEU_MA = 'thieu_ma';

    /** @var Collection<int, GiaoVien>|null */
    private ?Collection $danhMuc = null;

    /**
     * @return array{
     *     trang_thai: string,
     *     ten_danh_muc: string,
     *     goi_y: list<array{ma_gv: string, ho_ten: string}>
     * }
     */
    public function kiemTra(string $maGv, string $tenGv): array
    {
        $maGv = trim($maGv);
        $tenFile = self::chuanHoaTen($tenGv);

        if ($maGv === '') {
            return $this->ketQua(self::THIEU_MA, '', $this->goiYTheoTen($tenFile));
        }

        $gv = $this->timTheoMa($maGv);
        if ($gv === null) {
            return $this->ketQua(self::KHONG_CO_MA, '', $this->goiYTheoTen($tenFile));
        }

        $tenDanhMuc = trim((string) $gv->ho_ten);
        if ($tenFile !== '' && self::chuanHoaTen($tenDanhMuc) === $tenFile) {
            return $this->ketQua(self::KHOP, $tenDanhMuc, []);
        }

        return $this->ketQua(self::LECH_TEN, $tenDanhMuc, $this->goiYTheoTen($tenFile, $gv->MaGV));
    }

    public static function chuanHoaTen(string $ten): string
    {
        $ten = trim($ten);
        if ($ten !== '' && class_exists(\Normalizer::class)) {
            $ten = \Normalizer::normalize($ten, \Normalizer::FORM_C) ?: $ten;
        }

        return mb_strtolower(preg_replace('/\s+/u', ' ', $ten) ?? $ten, 'UTF-8');
    }

    private function timTheoMa(string $maGv): ?GiaoVien
    {
        $exact = $this->danhMuc()->first(
            fn (GiaoVien $gv): bool => mb_strtolower(trim((string) $gv->MaGV)) === mb_strtolower($maGv)
        );
        if ($exact !== null) {
            return $exact;
        }

        $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv);

        return $this->danhMuc()->first(
            fn (GiaoVien $gv): bool => DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $gv->MaGV) === $norm
        );
    }

    /** @return list<array{ma_gv: string, ho_ten: string}> */
    private function goiYTheoTen(string $tenChuan, ?string $boQuaMa = null): array
    {
        if ($tenChuan === '') {
            return [];
        }

        return $this->danhMuc()
            ->filter(fn (GiaoVien $gv): bool => self::chuanHoaTen((string) $gv->ho_ten) === $tenChuan
                && trim((string) $gv->MaGV) !== $boQuaMa)
            ->map(fn (GiaoVien $gv): array => ['ma_gv' => trim((string) $gv->MaGV), 'ho_ten' => trim((string) $gv->ho_ten)])
            ->values()
            ->all();
    }

    /** @return Collection<int, GiaoVien> */
    private function danhMuc(): Collection
    {
        return $this->danhMuc ??= GiaoVien::query()->get(['MaGV', 'HoTenDem', 'TenGV']);
    }

    /**
     * @param  list<array{ma_gv: string, ho_ten: string}>  $goiY
     * @return array{trang_thai: string, ten_danh_muc: string, goi_y: list<array{ma_gv: string, ho_ten: string}>}
     */
    private function ketQua(string $trangThai, string $tenDanhMuc, array $goiY): array
    {
        return ['trang_thai' => $trangThai, 'ten_danh_muc' => $tenDanhMuc, 'goi_y' => $goiY];
    }
}
