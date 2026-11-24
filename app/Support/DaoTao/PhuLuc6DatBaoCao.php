<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Ghép danh sách CCCD → số liệu DAT (tổng hợp học viên). */
final class PhuLuc6DatBaoCao
{
    /**
     * @param  list<array{stt: int, cccd: string, ho_ten: string}>  $danhSach
     * @return list<array{
     *   stt: int,
     *   cccd: string,
     *   ma_hoc_vien: string,
     *   ho_ten: string,
     *   ngay_sinh: ?string,
     *   hang: string,
     *   ma_khoa_hoc: string,
     *   tong_gio: ?float,
     *   tong_km: ?float,
     *   ghi_chu: string,
     *   loi: string,
     * }>
     */
    public static function dongDuLieu(
        array $danhSach,
        string $maKhoaHoc,
        ?string $tuNgay,
        ?string $denNgay
    ): array {
        $filters = DatDSPhienBoLoc::parseFilters(new Request([
            'ma_khoa_hoc' => $maKhoaHoc,
            'tu_ngay' => $tuNgay ?? '',
            'den_ngay' => $denNgay ?? '',
        ]));

        $dieuKienByHang = DatDieuKienDat::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get()
            ->keyBy('Hang');

        $aggregated = DatHocVienTongHop::aggregatedHocVien($filters, $dieuKienByHang);

        /** @var array<string, object> $theoMaHv */
        $theoMaHv = [];
        foreach ($aggregated as $row) {
            $ma = trim((string) ($row->MaHocVien ?? ''));
            if ($ma === '') {
                continue;
            }
            if (! isset($theoMaHv[$ma])) {
                $theoMaHv[$ma] = $row;

                continue;
            }
            $existing = $theoMaHv[$ma];
            $existing->TongGioHoc = (float) ($existing->TongGioHoc ?? 0) + (float) ($row->TongGioHoc ?? 0);
            $existing->TongQuangDuongKm = (float) ($existing->TongQuangDuongKm ?? 0) + (float) ($row->TongQuangDuongKm ?? 0);
        }

        $cccids = array_map(static fn (array $d) => $d['cccd'], $danhSach);
        $resolve = PhuLuc6CccdMaHocVien::resolve($cccids, $maKhoaHoc);

        $out = [];
        foreach ($danhSach as $item) {
            $cccd = $item['cccd'];
            $pm = $resolve[$cccd] ?? [
                'ma_hoc_vien' => '',
                'ho_ten' => '',
                'ngay_sinh' => null,
                'hang' => '',
                'loi' => 'Không tra cứu được.',
            ];
            $maHv = $pm['ma_hoc_vien'];
            $dat = $maHv !== '' ? ($theoMaHv[$maHv] ?? null) : null;

            $loi = $pm['loi'];
            if ($loi === '' && $maHv === '') {
                $loi = 'Không có mã học viên.';
            } elseif ($loi === '' && $dat === null) {
                $loi = 'Không có phiên DAT tổng hợp (kiểm tra ngày / khóa).';
            }

            $hoTen = $pm['ho_ten'] !== '' ? $pm['ho_ten'] : ($item['ho_ten'] ?? '');
            if ($hoTen === '' && $dat !== null) {
                $hoTen = trim((string) ($dat->HoTenHocVien ?? ''));
            }

            $hang = $pm['hang'];
            if ($hang === '' && $dat !== null) {
                $hang = trim((string) ($dat->LoaiKhoaHoc ?? ''));
            }

            $out[] = [
                'stt' => (int) $item['stt'],
                'cccd' => $cccd,
                'ma_hoc_vien' => $maHv,
                'ho_ten' => $hoTen,
                'ngay_sinh' => $pm['ngay_sinh'],
                'hang' => $hang,
                'ma_khoa_hoc' => $maKhoaHoc,
                'tong_gio' => $dat !== null ? (float) ($dat->TongGioHoc ?? 0) : null,
                'tong_km' => $dat !== null ? (float) ($dat->TongQuangDuongKm ?? 0) : null,
                'ghi_chu' => $loi === '' ? 'HT' : '',
                'loi' => $loi,
            ];
        }

        return $out;
    }

    public static function chuoiGio(?float $gio): string
    {
        if ($gio === null) {
            return '';
        }

        return DatHocVienTongHop::formatNumber($gio, 4).' giờ';
    }

    public static function chuoiKm(?float $km): string
    {
        if ($km === null) {
            return '';
        }

        return DatHocVienTongHop::formatNumber($km, 2).' km';
    }

    public static function excelNgaySinh(?string $ymd): mixed
    {
        if ($ymd === null || $ymd === '') {
            return null;
        }

        try {
            return Carbon::parse($ymd);
        } catch (\Throwable) {
            return null;
        }
    }
}