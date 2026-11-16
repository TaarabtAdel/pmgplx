<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Support\QldtOto\LichHocThucHanhGiaoVienDanhSach;
use App\Support\QldtOto\LichHocThucHanhGiaoVienLayout;
use Carbon\Carbon;

/**
 * Ghép lịch giáo viên PMGPLX (KhoaHoc_GiaoVien) thành bảng 7 cột/GV giống file nhập lịch.
 */
final class PmgplxLichGiaoVienBangNhapFile
{
    /**
     * @return array{
     *     cot: list<array{key: string, ma_gv: string, ten_gv: string, xe: string}>,
     *     dong: list<array{
     *         thu: string,
     *         ngay: string,
     *         nghi: bool,
     *         o: array<string, object>
     *     }>,
     *     so_buoi: int
     * }
     */
    public static function build(string $maKh): array
    {
        $maKh = trim($maKh);
        if ($maKh === '') {
            throw new \InvalidArgumentException('Chưa chọn mã khóa.');
        }

        $rows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocGiaoVien', 0)
            ->where('TrangThai', 1)
            ->whereNotNull('NgayBD')
            ->orderBy('MaGV')
            ->orderBy('NgayBD')
            ->get();

        if ($rows->isEmpty()) {
            throw new \RuntimeException('Khóa chưa có buổi lịch giáo viên trên PMGPLX.');
        }

        $bienTheoMaGv = self::bienSoTheoGiaoVien($maKh);

        /** @var array<string, array{ma_gv: string, ten_gv: string, bien_so: string, slots: array<string, array<string, mixed>>}> $gv */
        $gv = [];
        $dates = [];

        foreach ($rows as $row) {
            $maGv = trim((string) $row->MaGV);
            if ($maGv === '') {
                continue;
            }

            $bd = Carbon::parse($row->NgayBD);
            $kt = $row->NgayKT ? Carbon::parse($row->NgayKT) : $bd->copy();
            $dateKey = $bd->format('Y-m-d');
            $dates[$dateKey] = $bd->copy()->startOfDay();

            [$noiDung, $chiTiet] = self::tachNoiDungChiTiet((string) ($row->GhiChu ?? ''));
            $soGio = self::tinhSoGio($bd, $kt);

            if (! isset($gv[$maGv])) {
                $ten = trim((string) ($row->TenGV ?? ''));
                $bien = trim((string) ($row->BienSoXe ?? ''));
                if ($bien === '') {
                    $bien = $bienTheoMaGv[$maGv] ?? '';
                }
                $gv[$maGv] = [
                    'ma_gv' => $maGv,
                    'ten_gv' => $ten !== '' ? $ten : $maGv,
                    'bien_so' => LichHocThucHanhGiaoVienDanhSach::formatBienSoTrenTieuDe($bien),
                    'slots' => [],
                ];
            }

            if (! isset($gv[$maGv]['slots'][$dateKey])) {
                $gv[$maGv]['slots'][$dateKey] = [
                    'noi_dung' => $noiDung,
                    'chi_tiet' => $chiTiet,
                    'so_gio' => $soGio,
                    'bat_dau' => self::formatGioNhap($bd),
                    'ket_thuc' => self::formatGioNhap($kt),
                ];
            }
        }

        $cot = [];
        foreach ($gv as $item) {
            $cot[] = [
                'key' => $item['ma_gv'],
                'ma_gv' => $item['ma_gv'],
                'ten_gv' => $item['ten_gv'],
                'xe' => $item['bien_so'],
            ];
        }
        usort($cot, static fn (array $a, array $b): int => strcmp($a['ten_gv'], $b['ten_gv']));

        $datesSorted = array_values($dates);
        usort($datesSorted, static fn (Carbon $a, Carbon $b): int => $a->timestamp <=> $b->timestamp);

        $dong = [];
        $soBuoi = 0;
        foreach ($datesSorted as $ngay) {
            $dateKey = $ngay->format('Y-m-d');
            $o = [];
            $coBuoi = false;
            foreach ($cot as $col) {
                $slot = $gv[$col['ma_gv']]['slots'][$dateKey] ?? null;
                if ($slot === null) {
                    continue;
                }
                $coBuoi = true;
                $soBuoi++;
                $o[$col['key']] = (object) [
                    'SoGio' => $slot['so_gio'],
                    'NoiDung' => $slot['noi_dung'],
                    'ChiTiet' => $slot['chi_tiet'],
                    'NgaySo' => '',
                    'GioBatDau' => $slot['bat_dau'],
                    'GioKetThuc' => $slot['ket_thuc'],
                ];
            }
            $dong[] = [
                'thu' => LichHocThucHanhGiaoVienLayout::nhanThu($ngay),
                'ngay' => $ngay->format('d/m/Y'),
                'nghi' => ! $coBuoi,
                'o' => $o,
            ];
        }

        return [
            'cot' => $cot,
            'dong' => $dong,
            'so_buoi' => $soBuoi,
        ];
    }

    /**
     * Chỉ giữ các dòng có buổi của một giáo viên (cho xuất 1 sheet / GV).
     *
     * @param  array{cot: list<array<string, mixed>>, dong: list<array<string, mixed>>, so_buoi: int}  $bangLich
     * @return array{cot: list<array<string, mixed>>, dong: list<array<string, mixed>>, so_buoi: int}|null
     */
    public static function thuBangChoMotGiaoVien(array $bangLich, string $maGv): ?array
    {
        $maGv = trim($maGv);
        $col = null;
        foreach ($bangLich['cot'] as $c) {
            if (trim((string) ($c['ma_gv'] ?? '')) === $maGv) {
                $col = $c;
                break;
            }
        }
        if ($col === null) {
            return null;
        }

        $key = (string) ($col['key'] ?? $maGv);
        $dong = [];
        $soBuoi = 0;
        foreach ($bangLich['dong'] as $row) {
            if (! isset($row['o'][$key])) {
                continue;
            }
            $soBuoi++;
            $dong[] = [
                'thu' => $row['thu'],
                'ngay' => $row['ngay'],
                'nghi' => false,
                'o' => [$key => $row['o'][$key]],
            ];
        }

        if ($dong === []) {
            return null;
        }

        return [
            'cot' => [$col],
            'dong' => $dong,
            'so_buoi' => $soBuoi,
        ];
    }

    /** @return array{0: string, 1: string} */
    private static function tachNoiDungChiTiet(string $ghiChu): array
    {
        $ghiChu = trim($ghiChu);
        if ($ghiChu === '') {
            return ['', ''];
        }
        $parts = preg_split('/\s*-\s*/u', $ghiChu, 2) ?: [];

        return [
            trim((string) ($parts[0] ?? $ghiChu)),
            trim((string) ($parts[1] ?? '')),
        ];
    }

    private static function tinhSoGio(Carbon $bd, Carbon $kt): float
    {
        $minutes = max(0, $kt->diffInMinutes($bd));
        $hours = $minutes / 60;
        $rounded = round($hours * 2) / 2;

        return $rounded > 0 ? $rounded : 8.0;
    }

    private static function formatGioNhap(Carbon $t): string
    {
        return $t->format('H:i');
    }

    /** @return array<string, string> */
    private static function bienSoTheoGiaoVien(string $maKh): array
    {
        $ds = LichHocThucHanhGiaoVienDanhSach::nhomTheoGiaoVien($maKh);
        $out = [];
        foreach ($ds as $g) {
            $out[(string) $g['ma_gv']] = (string) ($g['bien_so'] ?? '');
        }

        return $out;
    }
}