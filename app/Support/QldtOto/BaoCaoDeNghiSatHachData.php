<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Support\PMGPLX\DmDvhcTenDayDu;
use App\Support\PMGPLX\NgayVn;
use Carbon\Carbon;

/** Báo cáo đề nghị tổ chức sát hạch + danh sách thí sinh (Báo cáo 2) theo khoá PMGPLX. */
final class BaoCaoDeNghiSatHachData
{
    public const HAU_TO_SO_BC = '/TTML-';

    public const KINH_GUI_MAC_DINH = 'Phòng Cảnh sát giao thông Công an tỉnh Quảng Trị';

    /**
     * Toàn bộ học viên của khoá theo thứ tự tên — `thu_tu` dùng để đánh số chứng chỉ nghề
     * (mẫu đánh số trên cả khoá, học viên không đạt vẫn giữ chỗ số).
     *
     * @return list<array{
     *     ma_dk: string, thu_tu: int, ho_ten: string, ngay_sinh: string, so_cmnd: string,
     *     noi_dk_ho_khau: string, noi_cu_tru: string, giay_cnsk: bool, da_co_gplx: string, so_nam_lx: string,
     *     so_km: string, so_ccn: string, ghi_chu: string, dat: bool,
     * }>
     */
    public static function hocVien(string $maKh): array
    {
        $rows = NguoiLX::query()
            ->from('NguoiLX as n')
            ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('h.MaKhoaHoc', $maKh)
            // Khớp thứ tự BC2 của PMGPLX: CHI < CHIẾN < CÔNG (Vietnamese_CI coi «CH» là chữ cái riêng sau C), Đ xếp như D.
            ->orderByRaw('n.TenNLX COLLATE Latin1_General_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Latin1_General_CI_AS ASC')
            ->orderBy('n.MaDK')
            ->get([
                'n.MaDK', 'n.HoVaTen', 'n.HoDemNLX', 'n.TenNLX', 'n.NgaySinh', 'n.SoCMT',
                'n.NoiTT', 'n.NoiTT_MaDVHC', 'n.NoiCT', 'n.NoiCT_MaDVHC',
                'h.GiayCNSK', 'h.HangGPLXDaCo', 'h.SoNamLX', 'h.SoKmLXAnToan', 'h.SoCCN',
                'h.LanSH', 'h.KetLuanCSDT',
            ]);

        $dvhcMap = DmDvhcTenDayDu::mapForHocVien($rows);

        $out = [];
        foreach ($rows->values() as $i => $n) {
            $hoTen = trim((string) ($n->HoVaTen ?? ''));
            if ($hoTen === '') {
                $hoTen = trim(($n->HoDemNLX ?? '').' '.($n->TenNLX ?? ''));
            }
            $lanSh = (int) ($n->LanSH ?? 1);
            $diaChi = DmDvhcTenDayDu::diaChiTuNguoiLx($n, $dvhcMap);

            $out[] = [
                'ma_dk' => (string) $n->MaDK,
                'thu_tu' => $i + 1,
                'ho_ten' => mb_strtoupper($hoTen),
                'ngay_sinh' => NgayVn::format($n->NgaySinh ?? null, 'yyyymmdd'),
                'so_cmnd' => trim((string) ($n->SoCMT ?? '')),
                'noi_dk_ho_khau' => $diaChi['thuong_tru'],
                'noi_cu_tru' => $diaChi['cu_tru'] !== '' ? $diaChi['cu_tru'] : $diaChi['thuong_tru'],
                'giay_cnsk' => (string) ($n->GiayCNSK ?? '') === '1',
                'da_co_gplx' => trim((string) ($n->HangGPLXDaCo ?? '')),
                'so_nam_lx' => (string) (int) ($n->SoNamLX ?? 0),
                'so_km' => (string) (int) ($n->SoKmLXAnToan ?? 0),
                'so_ccn' => trim((string) ($n->SoCCN ?? '')),
                'ghi_chu' => $lanSh > 1 ? 'SH lại lần '.$lanSh : 'SH lần đầu',
                'dat' => (string) ($n->KetLuanCSDT ?? '') === '1',
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string|int>
     */
    public static function macDinh(KhoaHoc $khoa, int $tongHocVien): array
    {
        $hangBc2 = DanhSachHocSinhLaiXeHocVien::hangTieuDe($khoa->HangGPLX ?? null, $khoa->HangDT ?? null);
        $soNgay = (int) ($khoa->ThoiGianDT ?? 0);

        return [
            'ma_kh' => (string) $khoa->MaKH,
            'so_bc' => '',
            'ngay_bao_cao' => now()->format('Y-m-d'),
            'kinh_gui' => self::KINH_GUI_MAC_DINH,
            'hang' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
            'khoa_lop' => trim((string) ($khoa->TenKH ?? '')) ?: (string) $khoa->MaKH,
            'so_vao_hoc' => max($tongHocVien, (int) ($khoa->TongSoHV ?? 0)),
            'ngay_sh' => $khoa->NgaySH ? Carbon::parse($khoa->NgaySH)->format('Y-m-d') : '',
            'ghi_chu' => 'SH lần đầu',
            'hang_bc2' => $hangBc2,
            'khoa_bc2' => trim((string) ($khoa->TenKH ?? '')) ?: (string) $khoa->MaKH,
            'thoi_gian_dt' => $soNgay > 0 ? rtrim(rtrim(number_format($soNgay / 30, 1, '.', ''), '0'), '.') : '',
            'ngay_kg' => $khoa->NgayKG ? Carbon::parse($khoa->NgayKG)->format('Y-m-d') : '',
            'ngay_bg' => $khoa->NgayBG ? Carbon::parse($khoa->NgayBG)->format('Y-m-d') : '',
            'ccn_tien_to' => $hangBc2.'-',
            'ccn_bat_dau' => '',
        ];
    }

    /** Số CCN: có sẵn trên PMGPLX thì dùng, không thì tiền tố + (số bắt đầu + thứ tự trong khoá − 1). */
    public static function soCcn(array $hv, string $tienTo, ?int $batDau): string
    {
        if ($hv['so_ccn'] !== '') {
            return $hv['so_ccn'];
        }

        return $batDau !== null ? $tienTo.($batDau + $hv['thu_tu'] - 1) : '';
    }

    public static function ngay(?string $ymd): string
    {
        $ymd = trim((string) $ymd);
        if ($ymd === '') {
            return '';
        }
        try {
            return Carbon::parse($ymd)->format('d/m/Y');
        } catch (\Throwable) {
            return $ymd;
        }
    }
}
