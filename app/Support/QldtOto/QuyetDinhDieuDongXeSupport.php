<?php

namespace App\Support\QldtOto;

use App\Models\DaoTao\KhoaDaoTao;
use App\Models\DaoTao\PhanCongDaoTao;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\XeTap;
use Carbon\Carbon;

final class QuyetDinhDieuDongXeSupport
{
    public const HAU_TO_SO_QD = '/QĐ-TTML';

    public static function resolveKhoaDaoTao(KhoaHoc $khoa): ?KhoaDaoTao
    {
        return KhoaDaoTao::findForPmKhoaHoc($khoa);
    }

    /** vd. «Hạng B (Số sàn) khóa 56» */
    public static function hangKhoaMacDinh(KhoaHoc $khoa): string
    {
        $hang = BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null);
        $khoaSo = BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '';

        return 'Hạng '.$hang.($khoaSo !== '' ? ' khóa '.$khoaSo : '');
    }

    public static function soQuyetDinhChiSo(?string $value): string
    {
        return preg_match('/^\s*(\d+)/', (string) $value, $m) ? $m[1] : '';
    }

    /** 74A14324 → 74A-143.24; giữ nguyên nếu không đúng dạng. */
    public static function bienSoHienThi(string $bienSo): string
    {
        $compact = mb_strtoupper(preg_replace('/[\s.\-]/u', '', $bienSo) ?? $bienSo);
        if (preg_match('/^(\d{2}[A-Z]{1,2}\d?)(\d{3})(\d{2})$/', $compact, $m)) {
            return $m[1].'-'.$m[2].'.'.$m[3];
        }
        if (preg_match('/^(\d{2}[A-Z]{1,2}\d?)(\d{4})$/', $compact, $m)) {
            return $m[1].'-'.$m[2];
        }

        return trim($bienSo);
    }

    /**
     * Thông tin xe từ danh mục PMGPLX (/pmgplx/danh-muc/xe), key = BienSoXe.
     *
     * @return array<string, array{loai_xe: string, nhan_hieu: string, mau_son: string}>
     */
    public static function xeThongTinMap(): array
    {
        $out = [];
        foreach (XeTap::query()->get(['BienSoXe', 'LoaiXe', 'NhanHieu', 'MauXe']) as $xe) {
            $out[(string) $xe->BienSoXe] = [
                'loai_xe' => self::loaiXeHienThi((string) ($xe->LoaiXe ?? '')),
                'nhan_hieu' => trim((string) ($xe->NhanHieu ?? '')),
                'mau_son' => trim((string) ($xe->MauXe ?? '')),
            ];
        }

        return $out;
    }

    /**
     * Đối chiếu lịch xe tập PMGPLX (/pmgplx/lich/xe-tap): cùng xe, khoá khác, trùng khoảng thời gian.
     *
     * @param  list<array<string, mixed>>  $xeBlocks
     * @return array<int, list<array{ma_kh: string, ten_kh: string, tu: string, den: string, so_buoi: int, giao_vien: string}>>
     */
    public static function canhBaoTrungLichXe(string $maKh, array $xeBlocks): array
    {
        $maKhKey = mb_strtoupper(trim($maKh));
        $out = [];

        foreach ($xeBlocks as $xi => $block) {
            $bien = trim((string) ($block['bien_so'] ?? ''));
            [$tu, $den] = self::khoangThoiGianXe($block['giao_vien'] ?? []);
            if ($bien === '' || $tu === null || $den === null) {
                continue;
            }

            $compact = mb_strtoupper(preg_replace('/[\s.\-]/u', '', $bien) ?? $bien);
            $rows = KhoaHocXeTap::query()
                ->where('IsKhoaHocXeTap', 0)
                ->whereNotNull('NgayBD')
                ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(BienSoXe, '-', ''), '.', ''), ' ', '')) = ?", [$compact])
                ->where('NgayBD', '<=', $den->format('Y-m-d').' 23:59:59')
                ->where(function ($q) use ($tu): void {
                    $q->where('NgayKT', '>=', $tu->format('Y-m-d'))
                        ->orWhere(function ($q2) use ($tu): void {
                            $q2->whereNull('NgayKT')->where('NgayBD', '>=', $tu->format('Y-m-d'));
                        });
                })
                ->get(['MaKH', 'NgayBD', 'NgayKT', 'TenGV']);

            $byKhoa = [];
            foreach ($rows as $row) {
                $ma = trim((string) $row->MaKH);
                if ($ma === '' || mb_strtoupper($ma) === $maKhKey) {
                    continue;
                }
                $key = mb_strtoupper($ma);
                $bd = $row->NgayBD;
                $kt = $row->NgayKT ?? $row->NgayBD;
                if (! isset($byKhoa[$key])) {
                    $byKhoa[$key] = ['ma_kh' => $ma, 'min' => $bd, 'max' => $kt, 'so_buoi' => 0, 'gv' => []];
                }
                $byKhoa[$key]['so_buoi']++;
                if ($bd < $byKhoa[$key]['min']) {
                    $byKhoa[$key]['min'] = $bd;
                }
                if ($kt > $byKhoa[$key]['max']) {
                    $byKhoa[$key]['max'] = $kt;
                }
                $tenGv = trim((string) ($row->TenGV ?? ''));
                if ($tenGv !== '') {
                    $byKhoa[$key]['gv'][$tenGv] = true;
                }
            }

            if ($byKhoa === []) {
                continue;
            }

            $tenKh = KhoaHoc::query()
                ->whereIn('MaKH', array_column($byKhoa, 'ma_kh'))
                ->pluck('TenKH', 'MaKH')
                ->mapWithKeys(fn ($ten, $ma) => [mb_strtoupper(trim((string) $ma)) => trim((string) $ten)]);

            foreach ($byKhoa as $key => $info) {
                $out[$xi][] = [
                    'ma_kh' => $info['ma_kh'],
                    'ten_kh' => (string) ($tenKh[$key] ?? ''),
                    'tu' => $info['min']->format('d/m/Y'),
                    'den' => $info['max']->format('d/m/Y'),
                    'so_buoi' => $info['so_buoi'],
                    'giao_vien' => implode(', ', array_keys($info['gv'])),
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $gvRows
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private static function khoangThoiGianXe(array $gvRows): array
    {
        $min = null;
        $max = null;
        foreach ($gvRows as $gv) {
            foreach (['tu_ngay' => 'min', 'den_ngay' => 'max'] as $field => $kind) {
                $v = trim((string) ($gv[$field] ?? ''));
                if ($v === '') {
                    continue;
                }
                try {
                    $d = Carbon::parse($v)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }
                if ($kind === 'min' && ($min === null || $d < $min)) {
                    $min = $d;
                }
                if ($kind === 'max' && ($max === null || $d > $max)) {
                    $max = $d;
                }
            }
        }

        return [$min, $max ?? $min];
    }

    public static function loaiXeHienThi(string $loaiXe): string
    {
        $loaiXe = trim($loaiXe);

        return mb_strtolower($loaiXe) === 'xe con' ? 'Ô tô con' : $loaiXe;
    }

    /**
     * Điền loại xe / nhãn hiệu / màu sơn còn trống từ danh mục xe.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, array{loai_xe: string, nhan_hieu: string, mau_son: string}>  $xeMap
     * @return list<array<string, mixed>>
     */
    public static function boSungThongTinXe(array $blocks, array $xeMap): array
    {
        foreach ($blocks as &$block) {
            $info = $xeMap[(string) ($block['bien_so'] ?? '')] ?? null;
            foreach (['loai_xe', 'nhan_hieu', 'mau_son'] as $field) {
                if (trim((string) ($block[$field] ?? '')) === '') {
                    $block[$field] = $info[$field] ?? '';
                }
            }
        }
        unset($block);

        return $blocks;
    }

    public static function urlDanhSachPhanCong(?int $khoaDaoTaoId): string
    {
        if ($khoaDaoTaoId === null) {
            return route('daotao.pdt.phan-cong-dao-tao.danh-sach');
        }

        return route('daotao.pdt.phan-cong-dao-tao.danh-sach', ['khoa_dao_tao_id' => $khoaDaoTaoId]);
    }

    public static function demPhanCongKhoa(int $khoaDaoTaoId): int
    {
        return PhanCongDaoTao::query()->where('KhoaDaoTaoId', $khoaDaoTaoId)->count();
    }

    /**
     * Nhóm phân công đào tạo theo xe (chỉ dòng có xe).
     *
     * @return list<array{bien_so: string, xe_tap_lai_id: int|null, giao_vien: list<array{ma_gv: string, ho_ten: string, tu_ngay: string, den_ngay: string, noi_dung: string}>}>
     */
    public static function loadXeBlocksFromPhanCong(int $khoaDaoTaoId): array
    {
        $rows = PhanCongDaoTao::query()
            ->with(['giaoVien', 'xeTapLai'])
            ->where('KhoaDaoTaoId', $khoaDaoTaoId)
            ->whereNotNull('XeTapLaiId')
            ->orderBy('TuNgay')
            ->orderBy('Id')
            ->get();

        /** @var array<string, array{bien_so: string, xe_tap_lai_id: int|null, giao_vien: list<array<string, string>>}> $byXe */
        $byXe = [];

        foreach ($rows as $pc) {
            $bien = trim((string) ($pc->xeTapLai?->BienSo ?? ''));
            if ($bien === '') {
                continue;
            }
            $key = (string) ($pc->XeTapLaiId ?? $bien);
            if (! isset($byXe[$key])) {
                $byXe[$key] = [
                    'bien_so' => PhanCongGvXeTapLaiFormSupport::bienSoTuDanhMucXe($bien),
                    'so_quyet_dinh' => '',
                    'loai_xe' => '',
                    'nhan_hieu' => '',
                    'mau_son' => '',
                    'xe_tap_lai_id' => $pc->XeTapLaiId ? (int) $pc->XeTapLaiId : null,
                    'giao_vien' => [],
                ];
            }

            $gv = $pc->giaoVien;
            $noiDung = trim((string) ($pc->NoiDungGiangDay ?? ''));
            if ($noiDung === '' && $pc->LoaiGiangDay) {
                $noiDung = PhanCongDaoTao::loaiGiangDayLabel($pc->LoaiGiangDay) ?: (string) $pc->LoaiGiangDay;
            }

            $byXe[$key]['giao_vien'][] = [
                'ma_gv' => trim((string) ($gv?->MaGV ?? '')),
                'ho_ten' => trim((string) ($gv?->HoTen ?? '')),
                'tu_ngay' => $pc->TuNgay?->format('Y-m-d') ?? '',
                'den_ngay' => $pc->DenNgay?->format('Y-m-d') ?? '',
                'noi_dung' => $noiDung,
            ];
        }

        $blocks = array_values($byXe);
        usort($blocks, fn (array $a, array $b): int => strcmp($a['bien_so'], $b['bien_so']));

        return $blocks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $gvRows
     * @return array<int, array<string, mixed>>
     */
    public static function resolveGiaoVienRows(array $gvRows): array
    {
        return PhanCongGvXeTapLaiFormSupport::resolveGiaoVienRows($gvRows);
    }

    /**
     * @param  list<array{bien_so: string, giao_vien: list<array<string, mixed>>}>  $xeBlocks
     * @return list<array{bien_so: string, ho_ten: string, thoi_gian: string, noi_dung: string}>
     */
    public static function flattenForPreview(array $xeBlocks): array
    {
        $out = [];
        foreach ($xeBlocks as $block) {
            $bien = trim((string) ($block['bien_so'] ?? ''));
            foreach ($block['giao_vien'] ?? [] as $gv) {
                $hoTen = trim((string) ($gv['ho_ten'] ?? ''));
                if ($bien === '' && $hoTen === '') {
                    continue;
                }
                $out[] = [
                    'bien_so' => $bien,
                    'so_quyet_dinh' => trim((string) ($block['so_quyet_dinh'] ?? '')),
                    'ho_ten' => $hoTen,
                    'thoi_gian' => self::formatThoiGian(
                        (string) ($gv['tu_ngay'] ?? ''),
                        (string) ($gv['den_ngay'] ?? '')
                    ),
                    'noi_dung' => trim((string) ($gv['noi_dung'] ?? '')),
                ];
            }
        }

        return $out;
    }

    private static function formatThoiGian(string $tu, string $den): string
    {
        $parts = [];
        if ($tu !== '') {
            try {
                $parts[] = Carbon::parse($tu)->format('d/m/Y');
            } catch (\Throwable) {
                $parts[] = $tu;
            }
        }
        if ($den !== '') {
            try {
                $parts[] = Carbon::parse($den)->format('d/m/Y');
            } catch (\Throwable) {
                $parts[] = $den;
            }
        }

        return implode(' – ', $parts);
    }
}
