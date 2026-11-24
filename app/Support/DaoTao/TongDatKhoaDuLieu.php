<?php

namespace App\Support\DaoTao;

use App\Models\PMGPLX\KhoaHoc;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Tổng hợp DAT theo khóa (báo cáo Tổng DAT). */
final class TongDatKhoaDuLieu
{
    public const CO_SO = 'Trung Tâm Giáo Dục Nghề Nghiệp Mạnh Linh';

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   ma_khoa: string,
     *   ten_khoa: string,
     *   hang_chinh: string,
     *   ngay_kg: ?string,
     *   ngay_bg: ?string,
     *   tom_tat_hang: list<array{stt: int, hang: string, label: string, tong_hv: int, phien_dat: int, phien_khong_dat: int}>,
     *   hoc_vien: list<array{
     *     stt: int,
     *     ma_hoc_vien: string,
     *     ho_ten: string,
     *     ngay_sinh: ?string,
     *     hang: string,
     *     tong_gio: float,
     *     tong_km: float,
     *     phien_dat: int,
     *     phien_khong_dat: int,
     *     ghi_chu: string,
     *   }>,
     * }
     */
    public static function tomTatKhoa(array $filters, string $maKhoa): array
    {
        $filters['ma_khoa_hoc'] = $maKhoa;
        $ctx = PhuLuc1DatBaoCaoHoTro::taiPhienVaKiemTra($filters);
        /** @var Collection<int, \App\Models\DaoTao\DatDSPhien> $sessions */
        $sessions = $ctx['sessions'];
        $violations = $ctx['violationsById'];

        $tenKhoa = '';
        $hangChinh = '';
        if ($sessions->isNotEmpty()) {
            $tenKhoa = trim((string) ($sessions->first()->TenKhoaHoc ?? ''));
            $hangChinh = trim((string) ($sessions->first()->LoaiKhoaHoc ?? ''));
        }

        $khoa = KhoaHoc::query()->find($maKhoa);
        $ngayKg = $khoa?->NgayKG?->format('Y-m-d');
        $ngayBg = $khoa?->NgayBG?->format('Y-m-d');
        if ($ngayKg === null && $sessions->isNotEmpty()) {
            $ngayKg = $sessions->min(fn ($s) => $s->ThoiGianBatDauPhienHoc?->format('Y-m-d'));
        }
        if ($ngayBg === null && $sessions->isNotEmpty()) {
            $ngayBg = $sessions->max(fn ($s) => $s->ThoiGianBatDauPhienHoc?->format('Y-m-d'));
        }

        /** @var array<string, array{hang: string, hv: array<string, true>, phien_dat: int, phien_khong_dat: int}> $theoHang */
        $theoHang = [];
        /** @var array<string, array<string, mixed>> $theoHv */
        $theoHv = [];

        foreach ($sessions as $session) {
            $maHv = trim((string) ($session->MaHocVien ?? ''));
            if ($maHv === '') {
                continue;
            }
            $hang = trim((string) ($session->LoaiKhoaHoc ?? '')) ?: '—';
            $key = $maHv."\0".$hang;
            $dat = DatDSPhienKiemTra::datPhien($violations, (int) $session->Id);
            $gio = (float) ($session->ThoiGianThucHanhGio ?? 0);
            $km = (float) ($session->QuangDuongThucHanhKm ?? 0);

            $theoHang[$hang] ??= [
                'hang' => $hang,
                'hv' => [],
                'phien_dat' => 0,
                'phien_khong_dat' => 0,
            ];
            $theoHang[$hang]['hv'][$maHv] = true;
            if ($dat) {
                $theoHang[$hang]['phien_dat']++;
            } else {
                $theoHang[$hang]['phien_khong_dat']++;
            }

            if (! isset($theoHv[$key])) {
                $theoHv[$key] = [
                    'ma_hoc_vien' => $maHv,
                    'ho_ten' => trim((string) ($session->HoTenHocVien ?? '')),
                    'hang' => $hang,
                    'tong_gio' => 0.0,
                    'tong_km' => 0.0,
                    'phien_dat' => 0,
                    'phien_khong_dat' => 0,
                    'phien' => [],
                ];
            }
            $theoHv[$key]['tong_gio'] += $gio;
            $theoHv[$key]['tong_km'] += $km;
            if ($dat) {
                $theoHv[$key]['phien_dat']++;
            } else {
                $theoHv[$key]['phien_khong_dat']++;
            }
            $theoHv[$key]['phien'][] = $session;
        }

        ksort($theoHang);
        $tomTatHang = [];
        $sttHang = 0;
        foreach ($theoHang as $row) {
            $sttHang++;
            $tomTatHang[] = [
                'stt' => $sttHang,
                'hang' => $row['hang'],
                'label' => self::nhanHang($row['hang']),
                'tong_hv' => count($row['hv']),
                'phien_dat' => $row['phien_dat'],
                'phien_khong_dat' => $row['phien_khong_dat'],
            ];
        }

        $maHvList = array_values(array_unique(array_map(static fn (array $r) => $r['ma_hoc_vien'], $theoHv)));
        $ngaySinh = PhuLuc1DatBaoCaoHoTro::ngaySinhTheoMaHocVien($maHvList);

        uasort($theoHv, static fn (array $a, array $b): int => strcmp($a['ma_hoc_vien'], $b['ma_hoc_vien']));

        $hocVien = [];
        $stt = 0;
        foreach ($theoHv as $row) {
            $stt++;
            $phien = $row['phien'];
            $ketLuan = PhuLuc1DatBaoCaoHoTro::ketLuanChuongTrinh(
                $row['ma_hoc_vien'],
                $row['hang'],
                $phien,
                $violations
            );
            $hocVien[] = [
                'stt' => $stt,
                'ma_hoc_vien' => $row['ma_hoc_vien'],
                'ho_ten' => $row['ho_ten'],
                'ngay_sinh' => $ngaySinh[$row['ma_hoc_vien']] ?? null,
                'hang' => $row['hang'],
                'tong_gio' => $row['tong_gio'],
                'tong_km' => $row['tong_km'],
                'phien_dat' => $row['phien_dat'],
                'phien_khong_dat' => $row['phien_khong_dat'],
                'ghi_chu' => $ketLuan === 'Đạt' ? 'HT' : '',
            ];
        }

        return [
            'ma_khoa' => $maKhoa,
            'ten_khoa' => $tenKhoa,
            'hang_chinh' => $hangChinh,
            'ngay_kg' => $ngayKg,
            'ngay_bg' => $ngayBg,
            'tom_tat_hang' => $tomTatHang,
            'hoc_vien' => $hocVien,
        ];
    }

    public static function nhanHang(string $hang): string
    {
        $h = trim($hang);
        if ($h === '' || $h === '—') {
            return '—';
        }
        if (str_starts_with(mb_strtolower($h), 'hạng')) {
            return $h;
        }

        return 'Hạng '.$h;
    }

    public static function khoiThongTinKhoa(array $data): string
    {
        $kg = self::ngayDmY($data['ngay_kg'] ?? null);
        $bg = self::ngayDmY($data['ngay_bg'] ?? null);

        return implode("\n", [
            'I. Thông tin khóa học',
            '1. Mã khóa học: '.($data['ma_khoa'] ?? ''),
            '2. Hạng đào tạo: '.($data['hang_chinh'] ?? ''),
            '3. Ngày khai giảng: '.$kg,
            '4. Ngày bế giảng: '.$bg,
            '5. Cơ sở đào tạo: '.self::CO_SO,
            'II. Thông tin quá trình đào tạo',
        ]);
    }

    public static function ngayDmY(?string $ymd): string
    {
        if ($ymd === null || $ymd === '') {
            return '—';
        }
        try {
            return Carbon::parse($ymd)->format('d/m/Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function chuoiGio(float $gio): string
    {
        return DatHocVienTongHop::formatNumber($gio, 4).' giờ';
    }

    public static function chuoiKm(float $km): string
    {
        return DatHocVienTongHop::formatNumber($km, 2).' km';
    }
}