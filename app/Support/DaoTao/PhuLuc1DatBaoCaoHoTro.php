<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatDSPhien;
use App\Models\PMGPLX\NguoiLX;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Dữ liệu báo cáo DAT từng học viên (phụ lục 1 / báo cáo quá trình). */
final class PhuLuc1DatBaoCaoHoTro
{
    public const CO_SO = 'Trung tâm Giáo dục nghề nghiệp Mạnh Linh';

    /** @return list<string> */
    public static function danhSachMaHocVien(array $filters, ?string $maHocVienLoc = null, ?array $chiChon = null): array
    {
        $ctx = self::taiPhienVaKiemTra($filters);
        $theoHv = self::nhomTheoHocVien($ctx['sessions']);
        if ($theoHv === []) {
            return [];
        }

        if ($chiChon !== null && $chiChon !== []) {
            $mas = [];
            foreach ($chiChon as $composed) {
                [$maHv,] = DatDSPhienBoLoc::parseMaHocVienFilter((string) $composed);
                if ($maHv !== '' && isset($theoHv[$maHv])) {
                    $mas[] = $maHv;
                }
            }

            return array_values(array_unique($mas));
        }

        if ($maHocVienLoc !== null && trim($maHocVienLoc) !== '') {
            [$maHv,] = DatDSPhienBoLoc::parseMaHocVienFilter(trim($maHocVienLoc));

            return ($maHv !== '' && isset($theoHv[$maHv])) ? [$maHv] : [];
        }

        $mas = array_keys($theoHv);
        sort($mas);

        return $mas;
    }

    /**
     * @return list<array{
     *   ma_hoc_vien: string,
     *   ma_loc: string,
     *   ho_ten: string,
     *   so_phien: int,
     *   tong_gio: float,
     *   tong_km: float,
     *   ket_luan: string,
     * }>
     */
    public static function danhSachHocVienTomTat(array $filters): array
    {
        $ctx = self::taiPhienVaKiemTra($filters);
        $theoHv = self::nhomTheoHocVien($ctx['sessions']);
        $out = [];
        foreach ($theoHv as $maHv => $phien) {
            $tom = self::baoCaoMotHocVien($maHv, $phien, $ctx['violationsById']);
            $out[] = [
                'ma_hoc_vien' => $maHv,
                'ma_loc' => DatDSPhienBoLoc::composeMaHocVienValue($maHv, $tom['ma_khoa_hoc']),
                'ho_ten' => $tom['ho_ten'],
                'so_phien' => count($tom['phien']),
                'tong_gio' => $tom['tong_thoi_gian'],
                'tong_km' => $tom['tong_quang_duong'],
                'ket_luan' => self::ketLuanChuongTrinh($maHv, $tom['hang_dao_tao'], $phien, $ctx['violationsById']),
            ];
        }
        usort($out, static fn (array $a, array $b): int => strcmp($a['ma_hoc_vien'], $b['ma_hoc_vien']));

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   sessions: Collection<int, DatDSPhien>,
     *   violationsById: array<int, list<string>>,
     *   expectedPhanCongById: array<int, array<string, mixed>>,
     * }
     */
    public static function taiPhienVaKiemTra(array $filters): array
    {
        $resolved = DatDSPhienLocResolver::resolve($filters);
        $sessions = (clone $resolved['query'])
            ->with('phanLoai')
            ->get();

        return [
            'sessions' => $sessions,
            'violationsById' => $resolved['violationsById'],
            'expectedPhanCongById' => $resolved['expectedPhanCongById'],
        ];
    }

    /**
     * @return array<string, ?string> ma_hv => Y-m-d
     */
    public static function ngaySinhTheoMaHocVien(array $maHocViens): array
    {
        if ($maHocViens === []) {
            return [];
        }
        $map = [];
        foreach (array_chunk($maHocViens, 400) as $chunk) {
            $rows = NguoiLX::query()
                ->whereIn('MaDK', $chunk)
                ->get(['MaDK', 'NgaySinh']);
            foreach ($rows as $row) {
                $map[(string) $row->MaDK] = self::ngaySinhYmd($row->NgaySinh);
            }
        }

        return $map;
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  array<int, list<string>>  $violationsById
     * @return array<string, list<DatDSPhien>>
     */
    public static function nhomTheoHocVien(Collection $sessions): array
    {
        $out = [];
        foreach ($sessions as $session) {
            $ma = trim((string) ($session->MaHocVien ?? ''));
            if ($ma === '') {
                continue;
            }
            $out[$ma] ??= [];
            $out[$ma][] = $session;
        }

        return $out;
    }

    /**
     * @param  list<DatDSPhien>  $phien
     * @param  array<int, list<string>>  $violationsById
     * @return array{
     *   phien: list<array{stt: int, ma_phien: string, ngay_dao_tao: string, thoi_gian: string, quang_duong: string, ghi_chu: string, dat: bool}>,
     *   tong_thoi_gian: float,
     *   tong_quang_duong: float,
     *   ho_ten: string,
     *   ma_khoa_hoc: string,
     *   ten_khoa_hoc: string,
     *   hang_dao_tao: string,
     * }
     */
    public static function baoCaoMotHocVien(
        string $maHocVien,
        array $phien,
        array $violationsById
    ): array {
        usort($phien, static function (DatDSPhien $a, DatDSPhien $b): int {
            $ta = $a->ThoiGianBatDauPhienHoc?->getTimestamp() ?? 0;
            $tb = $b->ThoiGianBatDauPhienHoc?->getTimestamp() ?? 0;

            return $ta <=> $tb ?: ((int) $a->Id <=> (int) $b->Id);
        });

        $dong = [];
        $tongGio = 0.0;
        $tongKm = 0.0;
        $stt = 0;
        $hoTen = '';
        $maKhoa = '';
        $tenKhoa = '';
        $hang = '';

        foreach ($phien as $session) {
            $id = (int) $session->Id;
            $dat = DatDSPhienKiemTra::datPhien($violationsById, $id);
            $gio = (float) ($session->ThoiGianThucHanhGio ?? 0);
            $km = (float) ($session->QuangDuongThucHanhKm ?? 0);

            if ($dat) {
                $tongGio += $gio;
                $tongKm += $km;
            }

            $stt++;
            $hoTen = $hoTen !== '' ? $hoTen : trim((string) ($session->HoTenHocVien ?? ''));
            $maKhoa = $maKhoa !== '' ? $maKhoa : trim((string) ($session->MaKhoaHoc ?? ''));
            $tenKhoa = $tenKhoa !== '' ? $tenKhoa : trim((string) ($session->TenKhoaHoc ?? ''));
            $hang = $hang !== '' ? $hang : trim((string) ($session->LoaiKhoaHoc ?? ''));

            $ngay = $session->ThoiGianBatDauPhienHoc?->format('d/m/Y') ?? '';

            $dong[] = [
                'stt' => $stt,
                'ma_phien' => trim((string) ($session->MaPhienHoc ?? '')),
                'ngay_dao_tao' => $ngay,
                'thoi_gian' => DatHocVienTongHop::formatNumber($gio, 2),
                'quang_duong' => DatHocVienTongHop::formatNumber($km, 2),
                'ghi_chu' => $dat ? '' : 'Loại',
                'dat' => $dat,
            ];
        }

        return [
            'phien' => $dong,
            'tong_thoi_gian' => $tongGio,
            'tong_quang_duong' => $tongKm,
            'ho_ten' => $hoTen,
            'ma_khoa_hoc' => $maKhoa,
            'ten_khoa_hoc' => $tenKhoa,
            'hang_dao_tao' => $hang,
        ];
    }

    /**
     * @param  list<DatDSPhien>  $phien
     * @param  array<int, list<string>>  $violationsById
     */
    public static function ketLuanChuongTrinh(string $maHocVien, string $hang, array $phien, array $violationsById): string
    {
        $dieuKien = DatDieuKienDat::query()->where('Hang', $hang)->orderBy('ThuTu')->first();
        if ($dieuKien === null) {
            return '—';
        }

        $datPhien = collect($phien)->filter(
            fn (DatDSPhien $s): bool => DatDSPhienKiemTra::datPhien($violationsById, (int) $s->Id)
        )->values();

        $tongGio = $datPhien->sum(fn (DatDSPhien $s) => (float) ($s->ThoiGianThucHanhGio ?? 0));
        $tongKm = $datPhien->sum(fn (DatDSPhien $s) => (float) ($s->QuangDuongThucHanhKm ?? 0));

        $chiTieu = DatPhienChiTieu::forSessions($datPhien, fresh: true);
        $tongXeTuDong = 0.0;
        $tongDem = 0.0;
        foreach ($datPhien as $session) {
            $ct = $chiTieu[(int) $session->Id] ?? [];
            $tongXeTuDong += (float) ($ct['gio_tu_dong'] ?? 0);
            $tongDem += (float) ($ct['gio_dem'] ?? 0);
        }

        $row = (object) [
            'TongGioHoc' => $tongGio,
            'TongQuangDuongKm' => $tongKm,
            'TongBanDemGio' => $tongDem,
            'TongXeSoTuDongGio' => $tongXeTuDong,
            'LoaiKhoaHoc' => $hang,
            'MaHocVien' => $maHocVien,
        ];

        $pass = DatHocVienTongHop::datChuongTrinh($row, $dieuKien);

        return $pass === true ? 'Đạt' : ($pass === false ? 'Chưa đạt' : '—');
    }

    public static function filtersTuRequest(Request $request): array
    {
        return DatDSPhienBoLoc::parseFilters($request);
    }

    public static function ngaySinhHienThi(?string $ymd): string
    {
        if ($ymd === null || $ymd === '') {
            return '';
        }
        try {
            return Carbon::parse($ymd)->format('d/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }

    private static function ngaySinhYmd(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}