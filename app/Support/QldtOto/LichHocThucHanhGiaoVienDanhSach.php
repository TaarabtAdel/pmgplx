<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use App\Support\PMGPLX\LichExcelDiaDiem;
use App\Support\PMGPLX\LichGvMonHoc;
use App\Support\PMGPLX\LichXeLoaiGhiChu;
use Carbon\Carbon;

/** Nhóm lịch thực hành theo giáo viên (PMGPLX KhoaHoc_GiaoVien). */
final class LichHocThucHanhGiaoVienDanhSach
{
    /**
     * @return list<array{
     *     ma_gv: string,
     *     ten_gv: string,
     *     bien_so: string,
     *     so_hv: int,
     *     buoi: list<array{
     *         ngay: Carbon,
     *         so_gio: float,
     *         noi_dung: string,
     *         dia_diem: string,
     *         ghi_chu: string,
     *     }>
     * }>
     */
    public static function nhomTheoGiaoVien(string $maKh): array
    {
        $maKh = trim($maKh);
        if ($maKh === '') {
            return [];
        }

        $monMap = DmMonHoc::query()->pluck('TenMH', 'MaMH');
        $bienTheoMaGv = self::bienSoTuLichXeTap($maKh);

        $rows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->whereNotNull('NgayBD')
            ->orderBy('MaGV')
            ->orderBy('NgayBD')
            ->get();

        /** @var array<string, array{ma_gv: string, ten_gv: string, bien_so: string, so_hv: int, buoi: list<array<string, mixed>>}> $nhom */
        $nhom = [];

        foreach ($rows as $row) {
            if (self::laMonLyThuyet($row, $monMap)) {
                continue;
            }

            $maGv = trim((string) $row->MaGV);
            if ($maGv === '') {
                continue;
            }

            $bd = $row->NgayBD ? Carbon::parse($row->NgayBD) : null;
            if ($bd === null) {
                continue;
            }
            $kt = $row->NgayKT ? Carbon::parse($row->NgayKT) : $bd->copy();

            $ghiChu = trim((string) ($row->GhiChu ?? ''));
            $parts = preg_split('/\s*-\s*/u', $ghiChu, 2) ?: [];
            $noiDungRaw = trim((string) ($parts[0] ?? $ghiChu));
            $chiTiet = trim((string) ($parts[1] ?? ''));

            if (! isset($nhom[$maGv])) {
                $tenGv = trim((string) ($row->TenGV ?? ''));
                $bien = trim((string) ($row->BienSoXe ?? ''));
                if ($bien === '') {
                    $bien = $bienTheoMaGv[$maGv] ?? '';
                }
                $nhom[$maGv] = [
                    'ma_gv' => $maGv,
                    'ten_gv' => $tenGv !== '' ? $tenGv : $maGv,
                    'bien_so' => self::formatBienSoTrenTieuDe($bien),
                    'so_hv' => max(0, (int) ($row->SoHV ?? 0)),
                    'buoi' => [],
                ];
            }

            if ($nhom[$maGv]['so_hv'] === 0 && (int) ($row->SoHV ?? 0) > 0) {
                $nhom[$maGv]['so_hv'] = (int) $row->SoHV;
            }

            $soGio = self::tinhSoGio($bd, $kt);
            $noiDung = self::noiDungHienThi($noiDungRaw, $chiTiet, $ghiChu);

            $loai = LichXeLoaiGhiChu::loaiLabelFromGhiChu($ghiChu);

            $nhom[$maGv]['buoi'][] = [
                'ngay' => $bd->copy()->startOfDay(),
                'so_gio' => $soGio,
                'noi_dung' => $noiDung,
                'dia_diem' => LichExcelDiaDiem::resolve($noiDungRaw, $chiTiet),
                'ghi_chu' => $loai,
                'ghi_chu_loai' => $loai,
                'ghi_chu_raw' => $ghiChu,
            ];
        }

        $out = array_values($nhom);
        usort($out, static fn (array $a, array $b): int => strcmp($a['ten_gv'], $b['ten_gv']));

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $buoi
     */
    public static function ghiBuoiLenSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $buoi,
        string $hang = LichHocLyThuyetThucHanhLichNgay::HANG_C
    ): void {
        $dong = LichHocThucHanhGiaoVienLayout::dongRaFile($buoi, $hang);
        LichHocThucHanhGiaoVienLayout::ghiLenSheet($sheet, $dong, $hang);
    }

    public static function formatBienSoTrenTieuDe(string $bien): string
    {
        $bien = trim($bien);
        if ($bien === '') {
            return '';
        }
        if (preg_match('/^\d{2}[A-Z]/', $bien) === 1) {
            return substr($bien, 0, 2).'-'.substr($bien, 2);
        }

        return $bien;
    }

    /**
     * @return array<string, string> ma_gv → biển số (ưu tiên lịch xe tập)
     */
    private static function bienSoTuLichXeTap(string $maKh): array
    {
        $q = new PhanCongTuLichPmgplxQuery;
        $agg = $q->aggregateByKhoaXe(['ma_kh' => [$maKh]]);

        /** @var array<string, array<string, int>> $dem */
        $dem = [];
        foreach ($agg as $block) {
            $bien = trim((string) ($block['bien_so'] ?? ''));
            foreach ($block['giao_viens'] ?? [] as $gv) {
                $ma = trim((string) ($gv['ma_gv'] ?? ''));
                if ($ma === '') {
                    continue;
                }
                $dem[$ma][$bien] = ($dem[$ma][$bien] ?? 0) + 1;
            }
        }

        $out = [];
        foreach ($dem as $ma => $counts) {
            arsort($counts);
            $out[$ma] = (string) array_key_first($counts);
        }

        return $out;
    }

    private static function laMonLyThuyet(KhoaHocGiaoVien $row, \Illuminate\Support\Collection $monMap): bool
    {
        $label = mb_strtolower(LichGvMonHoc::displayLabel($row->TenMonHoc, $row->MaMonHoc, $monMap));

        return str_contains($label, 'lý thuyết') || str_contains($label, 'ly thuyet');
    }

    private static function tinhSoGio(Carbon $bd, Carbon $kt): float
    {
        $minutes = max(0, $kt->diffInMinutes($bd));
        $hours = $minutes / 60;
        $rounded = round($hours * 2) / 2;

        return $rounded > 0 ? $rounded : 8.0;
    }

    private static function noiDungHienThi(string $noiDungRaw, string $chiTiet, string $ghiChuDayDu): string
    {
        $combined = trim($noiDungRaw.($chiTiet !== '' ? ' — '.$chiTiet : ''));
        if (mb_strlen($combined) >= 24 || str_contains(mb_strtolower($combined), 'tập lái')) {
            return $combined;
        }

        $key = mb_strtoupper(preg_replace('/\s+/u', ' ', $noiDungRaw) ?? $noiDungRaw);

        return match (true) {
            str_contains($key, 'HÌNH') || str_contains($key, 'HINH') => 'Tập lái xe trong bãi phẳng',
            str_contains($key, 'PHỨC TẠP') || str_contains($key, 'PHUC TAP') => 'Tập lái xe trên đường phức tạp',
            str_contains($key, 'BAN ĐÊM') || str_contains($key, 'BAN DEM') => 'Tập lái xe ban đêm',
            str_contains($key, 'CABIN') => 'Tập lái trên cabin học lái xe lái xe ô tô',
            str_contains($key, 'CÓ TẢI') || str_contains($key, 'CO TAI') => 'Tập lái xe có tải',
            str_contains($key, 'CAO TỐC') || str_contains($key, 'CAO TOC') => 'Bài tập lái trên đường cao tốc và đường cao tốc phân kỳ đầu tư',
            str_contains($key, 'TỰ ĐỘNG') || str_contains($key, 'TU DONG') => 'Tập lái xe trên đường với xe ô tô số tự động',
            str_contains($key, 'ÔN') || str_contains($key, 'ON') => 'Ôn luyện theo các nội dung SH',
            str_contains($key, 'KIỂM TRA') || str_contains($key, 'KIEM TRA') => 'Kiểm tra kết thúc môn học lý thuyết, thực hành lái xe',
            str_contains($key, 'CHỮ Z') || str_contains($key, 'CHU Z') => 'Tập lái xe tiến, lùi theo hình chữ chi',
            str_contains($key, 'TỔNG HỢP') || str_contains($key, 'TONG HOP') => 'Bài tập lái tổng hợp',
            $ghiChuDayDu !== '' => $ghiChuDayDu,
            default => '',
        };
    }
}