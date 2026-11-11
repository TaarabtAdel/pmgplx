<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\NguoiLXHoSo;
use App\Support\PMGPLX\LichGvMonHoc;
use App\Support\PMGPLX\LichXeLoaiGhiChu;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Gợi ý mốc ngày cho lịch LT+TH từ PMGPLX (KhoaHoc_GiaoVien). */
final class LichHocLyThuyetThucHanhLichNgay
{
    public const HANG_C = 'C';

    public const HANG_B_SAN = 'B';

    public const HANG_B_TU_DONG = 'B01';

    /** @return array{C: string, B: string, B01: string} */
    public static function nhanHang(): array
    {
        return [
            self::HANG_C => 'Hạng C',
            self::HANG_B_SAN => 'B sàn',
            self::HANG_B_TU_DONG => 'B tự động',
        ];
    }

    public static function nhanHangTuMa(?string $ma): string
    {
        $ma = strtoupper(trim((string) $ma));

        return self::nhanHang()[$ma] ?? ($ma !== '' ? $ma : '—');
    }

    /** @return list<string> */
    public static function hangGoiYTuKhoa(KhoaHoc $khoa): array
    {
        $modes = NguoiLXHoSo::query()
            ->where('MaKhoaHoc', $khoa->MaKH)
            ->selectRaw('HangGPLX, COUNT(*) as c')
            ->groupBy('HangGPLX')
            ->orderByDesc('c')
            ->pluck('HangGPLX')
            ->map(fn ($h) => self::chuanHoaHangTuGplx((string) $h))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($modes === []) {
            $modes = [self::HANG_C];
        }

        return $modes;
    }

    public static function chuanHoaHangTuGplx(string $hang): ?string
    {
        $h = strtoupper(trim($hang));
        if ($h === '' || $h === 'C') {
            return self::HANG_C;
        }
        if (in_array($h, ['B', 'B1', 'B2', 'B11'], true)) {
            return $h === 'B11' || str_contains($h, '11') ? self::HANG_B_TU_DONG : self::HANG_B_SAN;
        }
        if (str_contains($h, 'C')) {
            return self::HANG_C;
        }

        return null;
    }

    /**
     * @return array<string, string|null> khóa mốc → Y-m-d hoặc null
     */
    public static function goiYTuLichGiaoVien(string $maKh, ?Carbon $ngayKhaiGiang = null, ?string $hang = null): array
    {
        $tuXe = self::goiYTuLichXeTap($maKh, $ngayKhaiGiang);
        $hang = $hang !== null ? trim($hang) : null;

        $coBdTh = self::lichChiThucHanhLaiXe($maKh) || ($tuXe['ngay_bd_thuc_hanh'] ?? null) !== null;

        $dungBSanOffset = $hang === self::HANG_B_SAN && $coBdTh;
        if ($dungBSanOffset) {
            $offset = self::goiYBSanTuLichThuan($maKh, $ngayKhaiGiang, $tuXe);
            if (self::lichChiThucHanhLaiXe($maKh)) {
                return self::gopGoiYNguon($offset, $tuXe);
            }

            return self::gopGoiYNguon($tuXe, $offset);
        }

        $dungB01Offset = $hang === self::HANG_B_TU_DONG && $coBdTh;

        if ($dungB01Offset) {
            $offset = self::goiYB01TuLichThuan($maKh, $ngayKhaiGiang, $tuXe);
            if (self::lichChiThucHanhLaiXe($maKh)) {
                return self::gopGoiYNguon($offset, $tuXe);
            }

            return self::gopGoiYNguon($tuXe, $offset);
        }

        $monMap = DmMonHoc::query()->pluck('TenMH', 'MaMH');
        $lich = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->orderBy('NgayBD')
            ->get();

        $coNhan = $lich->map(function (KhoaHocGiaoVien $r) use ($monMap): array {
            $label = mb_strtolower(LichGvMonHoc::displayLabel($r->TenMonHoc, $r->MaMonHoc, $monMap));
            $bd = $r->NgayBD ? Carbon::parse($r->NgayBD)->startOfDay() : null;
            $kt = $r->NgayKT ? Carbon::parse($r->NgayKT)->startOfDay() : $bd;

            return ['label' => $label, 'bd' => $bd, 'kt' => $kt];
        });

        $thucHanh = $coNhan->filter(function (array $x): bool {
            if ($x['bd'] === null) {
                return false;
            }
            $l = $x['label'];
            if (str_contains($l, 'lý thuyết') || str_contains($l, 'ly thuyet')) {
                return false;
            }

            return str_contains($l, 'thực hành') || str_contains($l, 'lái xe')
                || str_contains($l, 'thuc hanh') || str_contains($l, 'lai xe');
        });

        $fmt = static fn (?Carbon $c): ?string => $c?->format('Y-m-d');

        $ngayBdThucHanh = $fmt($thucHanh->pluck('bd')->filter()->min());

        $cabin = $coNhan->filter(fn (array $x) => $x['bd'] && str_contains($x['label'], 'cabin'));
        $dat = $coNhan->filter(fn (array $x) => $x['bd'] && (
            str_contains($x['label'], 'dat') || str_contains($x['label'], 'đường phức tạp')
            || str_contains($x['label'], 'duong phuc tap')
        ));
        $hinh = $thucHanh->filter(fn (array $x) => str_contains($x['label'], 'hình') || str_contains($x['label'], 'hinh')
            || str_contains($x['label'], 'sân') || str_contains($x['label'], 'san')
            || str_contains($x['label'], 'tại chỗ') || str_contains($x['label'], 'bai phang'));
        $hinhDuoi = $thucHanh->filter(fn (array $x) => str_contains($x['label'], 'chữ z') || str_contains($x['label'], 'chu z')
            || str_contains($x['label'], 'tổng hợp') || str_contains($x['label'], 'tong hop')
            || str_contains($x['label'], 'ôn luyện') || str_contains($x['label'], 'on luyen'));

        $bdCabin = $fmt($cabin->pluck('bd')->filter()->min());
        $ktCabin = $fmt($cabin->pluck('kt')->filter()->max());
        $bdDat = $fmt($dat->pluck('bd')->filter()->min());
        $ktDat = $fmt($dat->pluck('kt')->filter()->max());

        $bdHinhTren = $fmt($hinh->pluck('bd')->filter()->min());
        $ktHinhTren = $fmt($hinh->pluck('kt')->filter()->max());
        if ($bdHinhTren === null && $ngayBdThucHanh !== null) {
            $bdHinhTren = $ngayBdThucHanh;
        }
        if ($ktHinhTren === null && $bdCabin !== null) {
            $ktHinhTren = Carbon::parse($bdCabin)->subDay()->format('Y-m-d');
        }

        $bdHinhDuoi = $fmt($hinhDuoi->pluck('bd')->filter()->min());
        $ktHinhDuoi = $fmt($hinhDuoi->pluck('kt')->filter()->max());

        $onLuyen = $coNhan->filter(fn (array $x) => $x['bd'] && (
            str_contains($x['label'], 'ôn luyện') || str_contains($x['label'], 'on luyen')
            || str_contains($x['label'], 'sát hạch')
        ));
        $kiemTra = $coNhan->filter(fn (array $x) => $x['bd'] && (
            str_contains($x['label'], 'kiểm tra') || str_contains($x['label'], 'kiem tra')
        ));

        $kg = $ngayKhaiGiang?->format('Y-m-d');

        $chinh = [
            'ngay_khai_giang' => $kg,
            'ngay_bd_thuc_hanh' => $ngayBdThucHanh,
            'ngay_bd_hinh_tren' => $bdHinhTren,
            'ngay_kt_hinh_tren' => $ktHinhTren,
            'ngay_bd_cabin' => $bdCabin,
            'ngay_kt_cabin' => $ktCabin,
            'ngay_bd_dat' => $bdDat,
            'ngay_kt_dat' => $ktDat,
            'ngay_bo_sung_dat' => $ktDat,
            'ngay_bd_hinh_duoi' => $bdHinhDuoi ?? $ktDat,
            'ngay_kt_hinh_duoi' => $ktHinhDuoi,
            'ngay_bd_on_luyen' => $fmt($onLuyen->pluck('bd')->filter()->min()),
            'ngay_kt_on_luyen' => $fmt($onLuyen->pluck('kt')->filter()->max()),
            'ngay_kiem_tra' => $fmt($kiemTra->pluck('bd')->filter()->min() ?? $onLuyen->pluck('kt')->filter()->max()),
            'so_buoi_lich' => (string) $lich->count(),
            'goi_y_nguon' => 'lich_giao_vien',
        ];

        return self::gopGoiYNguon($chinh, $tuXe);
    }

    /**
     * Gợi ý mốc ngày từ lịch xe tập PMGPLX (/pmgplx/lich/xe-tap, {@see KhoaHocXeTap}).
     *
     * @return array<string, string|null>
     */
    public static function goiYTuLichXeTap(string $maKh, ?Carbon $ngayKhaiGiang = null): array
    {
        $rows = KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->orderBy('NgayBD')
            ->get();

        $fmt = static fn (?Carbon $c): ?string => $c?->format('Y-m-d');
        $kg = $ngayKhaiGiang?->copy()->startOfDay();

        $trong = static function (array $out): array {
            $out['so_buoi_lich_xe'] = (string) ($out['so_buoi_lich_xe'] ?? 0);
            $out['goi_y_nguon'] = 'lich_xe_tap';

            return $out;
        };

        if ($rows->isEmpty()) {
            return $trong([
                'ngay_khai_giang' => $fmt($kg),
                'ngay_bd_thuc_hanh' => null,
                'ngay_bd_hinh_tren' => null,
                'ngay_kt_hinh_tren' => null,
                'ngay_bd_cabin' => null,
                'ngay_kt_cabin' => null,
                'ngay_bd_dat' => null,
                'ngay_kt_dat' => null,
                'ngay_bo_sung_dat' => null,
                'ngay_bd_hinh_duoi' => null,
                'ngay_kt_hinh_duoi' => null,
                'ngay_bd_on_luyen' => null,
                'ngay_kt_on_luyen' => null,
                'ngay_kiem_tra' => null,
                'so_buoi_lich_xe' => '0',
            ]);
        }

        /** @var array<string, list<Carbon>> $theoLoai */
        $theoLoai = [];
        /** @var list<Carbon> $moiNgay */
        $moiNgay = [];

        foreach ($rows as $row) {
            foreach (self::ngayTuBuoiXe($row) as $ngay) {
                $moiNgay[] = $ngay;
                $loai = LichXeLoaiGhiChu::detectLoaiKeyFromGhiChu($row->GhiChu);
                if ($loai !== null) {
                    $theoLoai[$loai][] = $ngay;
                }
            }
        }

        $moiNgay = self::sapXepNgayUnique($moiNgay);
        [$bdTh, $maxNgay] = self::minMaxTuDanhSachNgay($moiNgay);

        $cabinNgay = self::sapXepNgayUnique($theoLoai[LichXeLoaiGhiChu::LOAI_CA_BIN] ?? []);
        $datNgay = self::sapXepNgayUnique($theoLoai[LichXeLoaiGhiChu::LOAI_DAT] ?? []);
        $hinhNgay = self::sapXepNgayUnique($theoLoai[LichXeLoaiGhiChu::LOAI_HINH] ?? []);
        $onNgay = self::sapXepNgayUnique($theoLoai[LichXeLoaiGhiChu::LOAI_ON_TAP] ?? []);

        [$bdDat, $ktDat] = self::minMaxTuDanhSachNgay($datNgay);
        [$bdCabin, $ktCabin] = self::minMaxTuDanhSachNgay($cabinNgay);
        [$bdOn, $ktOn] = self::minMaxTuDanhSachNgay($onNgay);

        $hinhTrenNgay = [];
        $hinhDuoiNgay = [];
        if ($bdDat !== null) {
            foreach ($hinhNgay as $ngay) {
                if ($ngay->lessThan($bdDat)) {
                    $hinhTrenNgay[] = $ngay;
                } elseif ($ktDat !== null && $ngay->greaterThan($ktDat)) {
                    $hinhDuoiNgay[] = $ngay;
                }
            }
        } elseif ($hinhNgay !== []) {
            if ($bdOn !== null) {
                foreach ($hinhNgay as $ngay) {
                    if ($ngay->lessThan($bdOn)) {
                        $hinhTrenNgay[] = $ngay;
                    } else {
                        $hinhDuoiNgay[] = $ngay;
                    }
                }
            } elseif ($bdCabin !== null) {
                foreach ($hinhNgay as $ngay) {
                    if ($ngay->lessThan($bdCabin)) {
                        $hinhTrenNgay[] = $ngay;
                    } elseif ($ktCabin !== null && $ngay->greaterThan($ktCabin)) {
                        $hinhDuoiNgay[] = $ngay;
                    } else {
                        $hinhTrenNgay[] = $ngay;
                    }
                }
            } else {
                $hinhTrenNgay = $hinhNgay;
            }
        }

        [$bdHinhTren, $ktHinhTren] = self::minMaxTuDanhSachNgay($hinhTrenNgay);
        [$bdHinhDuoi, $ktHinhDuoi] = self::minMaxTuDanhSachNgay($hinhDuoiNgay);

        if ($bdHinhTren === null && $bdTh !== null && $bdDat === null) {
            $bdHinhTren = $bdTh;
        }
        if ($ktHinhTren === null && $bdCabin !== null) {
            $ktHinhTren = $bdCabin->copy()->subDay();
        }

        $boSungDat = $ktDat?->copy()->addDay();
        $kiemTra = $ktOn?->copy()->addDay() ?? $maxNgay;

        return $trong([
            'ngay_khai_giang' => $fmt($kg),
            'ngay_bd_thuc_hanh' => $fmt($bdTh),
            'ngay_bd_hinh_tren' => $fmt($bdHinhTren),
            'ngay_kt_hinh_tren' => $fmt($ktHinhTren),
            'ngay_bd_cabin' => $fmt($bdCabin),
            'ngay_kt_cabin' => $fmt($ktCabin),
            'ngay_bd_dat' => $fmt($bdDat),
            'ngay_kt_dat' => $fmt($ktDat),
            'ngay_bo_sung_dat' => $fmt($boSungDat ?? $ktDat),
            'ngay_bd_hinh_duoi' => $fmt($bdHinhDuoi ?? $boSungDat ?? $ktDat),
            'ngay_kt_hinh_duoi' => $fmt($ktHinhDuoi),
            'ngay_bd_on_luyen' => $fmt($bdOn),
            'ngay_kt_on_luyen' => $fmt($ktOn),
            'ngay_kiem_tra' => $fmt($kiemTra),
            'so_buoi_lich_xe' => (string) $rows->count(),
        ]);
    }

    /**
     * Bổ sung mốc còn thiếu từ lịch xe tập (ưu tiên nguồn chính đã có).
     *
     * @param  array<string, string|null>  $chinh
     * @param  array<string, string|null>  $tuXe
     * @return array<string, string|null>
     */
    public static function gopGoiYNguon(array $chinh, array $phu): array
    {
        // Ngày khai giảng chỉ từ KhoaHoc.NgayKG (danh mục khoá), không gộp từ lịch xe.
        $moc = [
            'ngay_bd_thuc_hanh',
            'ngay_bd_hinh_tren', 'ngay_kt_hinh_tren',
            'ngay_bd_cabin', 'ngay_kt_cabin',
            'ngay_bd_dat', 'ngay_kt_dat', 'ngay_bo_sung_dat', 'ngay_kt_bo_sung_dat',
            'ngay_bd_hinh_duoi', 'ngay_kt_hinh_duoi',
            'ngay_bd_on_luyen', 'ngay_kt_on_luyen', 'ngay_kiem_tra',
        ];

        $boSung = false;
        foreach ($moc as $k) {
            $hien = trim((string) ($chinh[$k] ?? ''));
            $them = trim((string) ($phu[$k] ?? ''));
            if ($hien === '' && $them !== '') {
                $chinh[$k] = $phu[$k];
                $boSung = true;
            }
        }

        foreach (['so_buoi_lich_xe', 'so_buoi_lich'] as $meta) {
            if (($phu[$meta] ?? '0') !== '0' && ($chinh[$meta] ?? '0') === '0') {
                $chinh[$meta] = $phu[$meta];
            }
        }

        if ($boSung) {
            $nguonChinh = trim((string) ($chinh['goi_y_nguon'] ?? ''));
            $nguonPhu = trim((string) ($phu['goi_y_nguon'] ?? ''));
            if ($nguonPhu !== '') {
                if ($nguonChinh === '') {
                    $chinh['goi_y_nguon'] = $nguonPhu;
                } elseif (! str_contains($nguonChinh, $nguonPhu)) {
                    $chinh['goi_y_nguon'] = $nguonChinh.'+'.$nguonPhu;
                }
            } elseif ($nguonChinh === '') {
                $chinh['goi_y_nguon'] = 'lich_giao_vien';
            }
        }

        return $chinh;
    }

    /**
     * @return list<Carbon>
     */
    private static function ngayTuBuoiXe(KhoaHocXeTap $row): array
    {
        $bd = Carbon::parse($row->NgayBD)->startOfDay();
        $kt = $row->NgayKT ? Carbon::parse($row->NgayKT)->startOfDay() : $bd->copy();
        if ($kt->lessThan($bd)) {
            $kt = $bd->copy();
        }

        $out = [];
        for ($d = $bd->copy(); $d->lte($kt); $d->addDay()) {
            $out[] = $d->copy();
        }

        return $out;
    }

    /**
     * @param  list<Carbon>  $ngay
     * @return list<Carbon>
     */
    private static function sapXepNgayUnique(array $ngay): array
    {
        if ($ngay === []) {
            return [];
        }

        usort($ngay, static fn (Carbon $a, Carbon $b): int => $a->timestamp <=> $b->timestamp);
        $out = [];
        $seen = [];
        foreach ($ngay as $d) {
            $k = $d->format('Y-m-d');
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $d;
        }

        return $out;
    }

    /**
     * @param  list<Carbon>  $ngay
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private static function minMaxTuDanhSachNgay(array $ngay): array
    {
        if ($ngay === []) {
            return [null, null];
        }
        $ngay = self::sapXepNgayUnique($ngay);

        return [$ngay[0], $ngay[count($ngay) - 1]];
    }

    /**
     * @param  array<string, string|null>  $goiY
     * @param  array<string, string|null>  $form
     * @return array<string, Carbon|null>
     */
    public static function hopNhatMocNgay(array $goiY, array $form): array
    {
        $keys = [
            'ngay_khai_giang', 'ngay_bd_thuc_hanh',
            'ngay_bd_hinh_tren', 'ngay_kt_hinh_tren',
            'ngay_bd_cabin', 'ngay_kt_cabin',
            'ngay_bd_dat', 'ngay_kt_dat', 'ngay_bo_sung_dat', 'ngay_kt_bo_sung_dat',
            'ngay_bd_hinh_duoi', 'ngay_kt_hinh_duoi',
            'ngay_bd_on_luyen', 'ngay_kt_on_luyen', 'ngay_kiem_tra',
        ];
        $out = [];
        foreach ($keys as $k) {
            $raw = trim((string) ($form[$k] ?? $goiY[$k] ?? ''));
            try {
                $out[$k] = $raw !== '' ? Carbon::parse($raw)->startOfDay() : null;
            } catch (\Throwable) {
                $out[$k] = null;
            }
        }

        return $out;
    }

    public static function dinhDangNgay(?Carbon $c): string
    {
        return $c ? $c->format('d/m/Y') : '';
    }

    /**
     * B tự động (vd. B01K39): lịch GV chỉ môn «Thực hành lái xe» — quy tắc lấy từ file B01K39.xlsx.
     *
     * @return array<string, string|null>
     */
    /**
     * @param  array<string, string|null>|null  $tuXe  Kết quả {@see goiYTuLichXeTap} (BD TH khi lịch GV thưa).
     */
    public static function goiYB01TuLichThuan(string $maKh, ?Carbon $ngayKhaiGiang = null, ?array $tuXe = null): array
    {
        $lich = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->whereNotNull('NgayBD')
            ->get();

        $fmt = static fn (?Carbon $c): ?string => $c?->format('Y-m-d');
        $kg = $ngayKhaiGiang?->copy()->startOfDay();

        $bdTh = null;
        $maxNgay = null;

        if ($lich->isNotEmpty()) {
            $bdTh = Carbon::parse($lich->min('NgayBD'))->startOfDay();
            $maxKt = $lich->max('NgayKT');
            $maxNgay = $maxKt ? Carbon::parse($maxKt)->startOfDay() : null;
        }

        if ($bdTh === null && $tuXe !== null) {
            $rawBd = trim((string) ($tuXe['ngay_bd_thuc_hanh'] ?? ''));
            if ($rawBd !== '') {
                $bdTh = Carbon::parse($rawBd)->startOfDay();
            }
            $rawKt = trim((string) ($tuXe['ngay_kiem_tra'] ?? ''));
            if ($rawKt !== '') {
                $maxNgay = Carbon::parse($rawKt)->startOfDay();
            }
        }

        if ($bdTh === null) {
            return [
                'ngay_khai_giang' => $fmt($kg),
                'ngay_bd_thuc_hanh' => null,
                'ngay_bd_hinh_tren' => null,
                'ngay_kt_hinh_tren' => null,
                'ngay_bd_cabin' => null,
                'ngay_kt_cabin' => null,
                'ngay_bd_dat' => null,
                'ngay_kt_dat' => null,
                'ngay_bo_sung_dat' => null,
                'ngay_bd_hinh_duoi' => null,
                'ngay_kt_hinh_duoi' => null,
                'ngay_bd_on_luyen' => null,
                'ngay_kt_on_luyen' => null,
                'ngay_kiem_tra' => null,
                'so_buoi_lich' => (string) $lich->count(),
                'goi_y_nguon' => 'b01_offset',
            ];
        }

        $hinhTrenKt = $bdTh->copy()->addDays(5);
        $datBd = $hinhTrenKt->copy()->addDay();
        $datKt = $datBd->copy()->addDays(24);
        $cabinBd = $datBd->copy()->addDays(10);
        $cabinKt = $cabinBd->copy()->addDays(6);
        $boSungDat = $datKt->copy()->addDay();
        $hinhDuoiBd = $datKt->copy()->addDays(2);
        $hinhDuoiKt = $hinhDuoiBd->copy()->addDays(8);
        $onBd = $hinhDuoiKt->copy()->addDay();
        $onKt = $onBd->copy()->addDay();
        $kiemTraTheoMau = $onKt->copy()->addDay();
        $kiemTra = $maxNgay && $maxNgay->greaterThan($kiemTraTheoMau) ? $maxNgay : $kiemTraTheoMau;

        return [
            'ngay_khai_giang' => $fmt($kg),
            'ngay_bd_thuc_hanh' => $fmt($bdTh),
            'ngay_bd_hinh_tren' => $fmt($bdTh),
            'ngay_kt_hinh_tren' => $fmt($hinhTrenKt),
            'ngay_bd_cabin' => $fmt($cabinBd),
            'ngay_kt_cabin' => $fmt($cabinKt),
            'ngay_bd_dat' => $fmt($datBd),
            'ngay_kt_dat' => $fmt($datKt),
            'ngay_bo_sung_dat' => $fmt($boSungDat),
            'ngay_bd_hinh_duoi' => $fmt($hinhDuoiBd),
            'ngay_kt_hinh_duoi' => $fmt($hinhDuoiKt),
            'ngay_bd_on_luyen' => $fmt($onBd),
            'ngay_kt_on_luyen' => $fmt($onKt),
            'ngay_kiem_tra' => $fmt($kiemTra),
            'so_buoi_lich' => (string) $lich->count(),
            'goi_y_nguon' => 'b01_offset',
        ];
    }

    /**
     * B sàn (vd. BK56): quy tắc offset từ file BK56.xlsx, sheet «LỊCH SỐ SÀN 56» — ma_kh 44007K261010.
     *
     * @param  array<string, string|null>|null  $tuXe
     * @return array<string, string|null>
     */
    public static function goiYBSanTuLichThuan(string $maKh, ?Carbon $ngayKhaiGiang = null, ?array $tuXe = null): array
    {
        $lich = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->whereNotNull('NgayBD')
            ->get();

        $fmt = static fn (?Carbon $c): ?string => $c?->format('Y-m-d');
        $kg = $ngayKhaiGiang?->copy()->startOfDay();

        $bdTh = null;
        $maxNgay = null;

        if ($lich->isNotEmpty()) {
            $bdTh = Carbon::parse($lich->min('NgayBD'))->startOfDay();
            $maxKt = $lich->max('NgayKT');
            $maxNgay = $maxKt ? Carbon::parse($maxKt)->startOfDay() : null;
        }

        if ($bdTh === null && $tuXe !== null) {
            $rawBd = trim((string) ($tuXe['ngay_bd_thuc_hanh'] ?? ''));
            if ($rawBd !== '') {
                $bdTh = Carbon::parse($rawBd)->startOfDay();
            }
            $rawKt = trim((string) ($tuXe['ngay_kiem_tra'] ?? ''));
            if ($rawKt !== '') {
                $maxNgay = Carbon::parse($rawKt)->startOfDay();
            }
        }

        if ($bdTh === null) {
            return [
                'ngay_khai_giang' => $fmt($kg),
                'ngay_bd_thuc_hanh' => null,
                'ngay_bd_hinh_tren' => null,
                'ngay_kt_hinh_tren' => null,
                'ngay_bd_cabin' => null,
                'ngay_kt_cabin' => null,
                'ngay_bd_dat' => null,
                'ngay_kt_dat' => null,
                'ngay_bo_sung_dat' => null,
                'ngay_kt_bo_sung_dat' => null,
                'ngay_bd_hinh_duoi' => null,
                'ngay_kt_hinh_duoi' => null,
                'ngay_bd_on_luyen' => null,
                'ngay_kt_on_luyen' => null,
                'ngay_kiem_tra' => null,
                'so_buoi_lich' => (string) $lich->count(),
                'goi_y_nguon' => 'b_san_offset',
            ];
        }

        $hinhTrenKt = $bdTh->copy()->addDays(3);
        $cabinBd = $bdTh->copy()->addDays(4);
        $cabinKt = $cabinBd->copy()->addDays(10);
        $datBd = $cabinBd->copy();
        $datKt = $datBd->copy()->addDays(30);
        $boSungBd = $datKt->copy()->addDay();
        $boSungKt = $datKt->copy()->addDays(2);
        $chuChi = $datKt->copy()->addDays(3);
        $tongHopKt = $chuChi->copy()->addDays(5);
        $onBd = $tongHopKt->copy()->addDay();
        $onKt = $onBd->copy()->addDay();
        $kiemTraTheoMau = $onKt->copy()->addDays(2);
        $kiemTra = $maxNgay && $maxNgay->greaterThan($kiemTraTheoMau) ? $maxNgay : $kiemTraTheoMau;

        return [
            'ngay_khai_giang' => $fmt($kg),
            'ngay_bd_thuc_hanh' => $fmt($bdTh),
            'ngay_bd_hinh_tren' => $fmt($bdTh),
            'ngay_kt_hinh_tren' => $fmt($hinhTrenKt),
            'ngay_bd_cabin' => $fmt($cabinBd),
            'ngay_kt_cabin' => $fmt($cabinKt),
            'ngay_bd_dat' => $fmt($datBd),
            'ngay_kt_dat' => $fmt($datKt),
            'ngay_bo_sung_dat' => $fmt($boSungBd),
            'ngay_kt_bo_sung_dat' => $fmt($boSungKt),
            'ngay_bd_hinh_duoi' => $fmt($chuChi),
            'ngay_kt_hinh_duoi' => $fmt($tongHopKt),
            'ngay_bd_on_luyen' => $fmt($onBd),
            'ngay_kt_on_luyen' => $fmt($onKt),
            'ngay_kiem_tra' => $fmt($kiemTra),
            'so_buoi_lich' => (string) $lich->count(),
            'goi_y_nguon' => 'b_san_offset',
        ];
    }

    public static function lichChiThucHanhLaiXe(string $maKh): bool
    {
        $rows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->whereNotNull('NgayBD')
            ->get(['TenMonHoc', 'MaMonHoc']);

        if ($rows->isEmpty()) {
            return false;
        }

        $monMap = DmMonHoc::query()->pluck('TenMH', 'MaMH');

        return $rows->every(function (KhoaHocGiaoVien $r) use ($monMap): bool {
            $label = mb_strtolower(LichGvMonHoc::displayLabel($r->TenMonHoc, $r->MaMonHoc, $monMap));

            return str_contains($label, 'thực hành lái xe') || str_contains($label, 'thuc hanh lai xe')
                || trim((string) $r->TenMonHoc) === '6';
        });
    }
}
