<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\XeTap;
use Carbon\Carbon;

final class QuyetDinhDieuDongXeDocxData
{
    /**
     * @param  array{ma_kh?: string, ngay_quyet_dinh?: string, hang_khoa?: string, xe_blocks?: list<array<string, mixed>>}  $preview
     * @return list<array<string, string>>
     */
    public static function pagesFromPreview(array $preview): array
    {
        $maKh = trim((string) ($preview['ma_kh'] ?? ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;
        if ($khoa === null) {
            throw new \InvalidArgumentException('Không tìm thấy khoá PMGPLX.');
        }

        $hangKhoa = trim((string) ($preview['hang_khoa'] ?? ''));
        if ($hangKhoa === '') {
            $hangKhoa = QuyetDinhDieuDongXeSupport::hangKhoaMacDinh($khoa);
        }

        $ngay = self::parseDateString((string) ($preview['ngay_quyet_dinh'] ?? '')) ?? Carbon::now();
        $ngayDong = BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay);

        $blocks = $preview['xe_blocks'] ?? [];
        if ($blocks === []) {
            throw new \InvalidArgumentException('Không có xe để xuất quyết định.');
        }

        $pages = [];
        foreach ($blocks as $block) {
            $bienSo = trim((string) ($block['bien_so'] ?? ''));
            $soQd = QuyetDinhDieuDongXeSupport::soQuyetDinhChiSo((string) ($block['so_quyet_dinh'] ?? ''));
            if ($bienSo === '' || $soQd === '') {
                continue;
            }

            $xe = self::findXeTap($bienSo);
            [$tu, $den] = self::thoiGianTapLai($block['giao_vien'] ?? []);

            $loaiXe = trim((string) ($block['loai_xe'] ?? ''));
            if ($loaiXe === '') {
                $loaiXe = QuyetDinhDieuDongXeSupport::loaiXeHienThi((string) ($xe?->LoaiXe ?? ''));
            }

            $pages[] = [
                'SO_QD' => $soQd.QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
                'NGAY_DONG' => $ngayDong,
                'HANG_KHOA' => $hangKhoa,
                'BIEN_SO' => QuyetDinhDieuDongXeSupport::bienSoHienThi($bienSo),
                'LOAI_XE' => $loaiXe,
                'NHAN_HIEU' => trim((string) ($block['nhan_hieu'] ?? '')) ?: trim((string) ($xe?->NhanHieu ?? '')),
                'MAU_SON' => trim((string) ($block['mau_son'] ?? '')) ?: trim((string) ($xe?->MauXe ?? '')),
                'GP_XE_TEXT' => self::giayPhepXeText($xe),
                'GV_NHAN_XE' => self::danhSachGiaoVien($block['giao_vien'] ?? []),
                'TU_NGAY' => $tu,
                'DEN_NGAY' => $den,
            ];
        }

        if ($pages === []) {
            throw new \InvalidArgumentException('Thiếu biển số hoặc số quyết định trên từng xe.');
        }

        return $pages;
    }

    private static function findXeTap(string $bienSo): ?XeTap
    {
        $xe = XeTap::query()->find(PhanCongGvXeTapLaiFormSupport::bienSoTuDanhMucXe($bienSo));
        if ($xe instanceof XeTap) {
            return $xe;
        }

        $norm = mb_strtoupper(preg_replace('/[\s.\-]/u', '', $bienSo) ?? $bienSo);

        return XeTap::query()->get()->first(
            fn (XeTap $row): bool => mb_strtoupper(preg_replace('/[\s.\-]/u', '', (string) $row->BienSoXe) ?? '') === $norm
        );
    }

    /**
     * @param  list<array<string, mixed>>  $gvRows
     * @return array{0: string, 1: string}
     */
    private static function thoiGianTapLai(array $gvRows): array
    {
        $min = null;
        $max = null;
        foreach ($gvRows as $gv) {
            $tu = self::parseDateString((string) ($gv['tu_ngay'] ?? ''));
            $den = self::parseDateString((string) ($gv['den_ngay'] ?? ''));
            if ($tu !== null && ($min === null || $tu < $min)) {
                $min = $tu;
            }
            if ($den !== null && ($max === null || $den > $max)) {
                $max = $den;
            }
        }

        return [
            $min?->format('d/m/Y') ?? '',
            $max?->format('d/m/Y') ?? '',
        ];
    }

    private static function parseDateString(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        try {
            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value);
            }

            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Phần sau «Giấy phép xe tập lái số: » trong mẫu, vd. «14/2025 ngày 10/04/2026». */
    private static function giayPhepXeText(?XeTap $xe): string
    {
        $so = trim((string) ($xe?->SoGPXTL ?? ''));
        $ngay = $xe?->NgayCapGPXTL;
        $ngayStr = $ngay instanceof \DateTimeInterface ? $ngay->format('d/m/Y') : '';

        if ($so === '' && $ngayStr === '') {
            return '…';
        }

        return trim($so.($ngayStr !== '' ? ' ngày '.$ngayStr : ''));
    }

    /**
     * @param  list<array<string, mixed>>  $gvRows
     */
    private static function danhSachGiaoVien(array $gvRows): string
    {
        $names = [];
        foreach ($gvRows as $gv) {
            $ten = trim((string) ($gv['ho_ten'] ?? ''));
            if ($ten !== '') {
                $names[] = $ten;
            }
        }
        $names = array_values(array_unique($names));
        if ($names === []) {
            return '—';
        }
        if (count($names) === 1) {
            return $names[0];
        }
        $last = array_pop($names);

        return implode(', ', $names).' và '.$last;
    }
}
