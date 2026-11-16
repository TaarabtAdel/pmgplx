<?php

namespace App\Support\QldtOto\KtmBaoCao;

use Carbon\Carbon;

/** Tham số người dùng nhập trước khi xuất (3 khối: BBTH, BC chi tiết, BC chung). */
final class KtmBaoCaoThamSo
{
    /**
     * @param  array{bbth?: array<string, mixed>, chi_tiet?: array<string, mixed>, bc_chung?: array<string, mixed>}  $form
     */
    public static function tuForm(array $form, KtmKyThiDuLieu $duLieu): self
    {
        $bbth = $form['bbth'] ?? [];
        $ct = $form['chi_tiet'] ?? [];
        $bc = $form['bc_chung'] ?? [];

        return new self(
            bbth: [
                'ten_khoa' => trim((string) ($bbth['ten_khoa'] ?? $duLieu->tenKhoa)),
                'dia_diem_so' => trim((string) ($bbth['dia_diem_so'] ?? 'Quảng Trị')),
                'ngay_lap' => trim((string) ($bbth['ngay_lap'] ?? $duLieu->ngayThi)),
                'ngay_hop' => trim((string) ($bbth['ngay_hop'] ?? $duLieu->ngayThi)),
                'gio_hop' => trim((string) ($bbth['gio_hop'] ?? '7h30')),
                'dia_diem_hop' => trim((string) ($bbth['dia_diem_hop'] ?? '')),
                'so_qd' => trim((string) ($bbth['so_qd'] ?? '')),
                'ngay_qd' => trim((string) ($bbth['ngay_qd'] ?? '')),
                'ty_le_mau_so' => (string) ($bbth['ty_le_mau_so'] ?? 'du_thi'),
                'thanh_vien' => array_values((array) ($bbth['thanh_vien'] ?? [])),
                'nhan_xet' => array_values((array) ($bbth['nhan_xet'] ?? [])),
                'ky_uy_vien' => trim((string) ($bbth['ky_uy_vien'] ?? '')),
                'ky_pho_chu_tich' => trim((string) ($bbth['ky_pho_chu_tich'] ?? '')),
                'ky_chu_tich' => trim((string) ($bbth['ky_chu_tich'] ?? '')),
                'ky_thu_ky' => trim((string) ($bbth['ky_thu_ky'] ?? '')),
            ],
            chiTiet: [
                'dia_diem_so' => trim((string) ($ct['dia_diem_so'] ?? 'Quảng Trị')),
                'ngay_lap' => trim((string) ($ct['ngay_lap'] ?? $duLieu->ngayThi)),
                'sat_hach' => trim((string) ($ct['sat_hach'] ?? '')),
                'ngay_thi' => trim((string) ($ct['ngay_thi'] ?? $duLieu->ngayThi)),
                'ky_giam_doc' => trim((string) ($ct['ky_giam_doc'] ?? '')),
                'ky_pho_giam_doc' => trim((string) ($ct['ky_pho_giam_doc'] ?? '')),
            ],
            bcChung: [
                'dia_diem_so' => trim((string) ($bc['dia_diem_so'] ?? 'Quảng Trị')),
                'ngay_lap' => trim((string) ($bc['ngay_lap'] ?? $duLieu->ngayThi)),
                'sat_hach' => trim((string) ($bc['sat_hach'] ?? '')),
                'ngay_thi' => trim((string) ($bc['ngay_thi'] ?? $duLieu->ngayThi)),
                'ky_giam_doc' => trim((string) ($bc['ky_giam_doc'] ?? '')),
                'ky_pho_giam_doc' => trim((string) ($bc['ky_pho_giam_doc'] ?? '')),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $bbth
     * @param  array<string, mixed>  $chiTiet
     * @param  array<string, mixed>  $bcChung
     */
    public function __construct(
        public array $bbth,
        public array $chiTiet,
        public array $bcChung,
    ) {}

    public function tyLeMauSo(): string
    {
        return (string) ($this->bbth['ty_le_mau_so'] ?? 'du_thi');
    }

    public function dongNgayBbth(): string
    {
        return self::dongNgay($this->bbth['dia_diem_so'] ?? '', $this->bbth['ngay_lap'] ?? '');
    }

    public function dongNgayChiTiet(): string
    {
        return self::dongNgay($this->chiTiet['dia_diem_so'] ?? '', $this->chiTiet['ngay_lap'] ?? '');
    }

    public function dongNgayBcChung(): string
    {
        return self::dongNgay($this->bcChung['dia_diem_so'] ?? '', $this->bcChung['ngay_lap'] ?? '');
    }

    public function doanVanBbthA8(int $soThiSinh): string
    {
        $soQd = trim((string) ($this->bbth['so_qd'] ?? ''));
        $qd = $soQd !== '' ? 'Số: '.$soQd.'/QĐ-TTML' : 'Số:         /QĐ-TTML';
        $ngayQd = trim((string) ($this->bbth['ngay_qd'] ?? ''));
        if ($ngayQd !== '') {
            $qd .= ' ngày '.$ngayQd;
        }

        return 'Hôm nay, vào lúc '.($this->bbth['gio_hop'] ?? '')
            .' ngày '.($this->bbth['ngay_hop'] ?? '')
            .', tại '.($this->bbth['dia_diem_hop'] ?? '')
            .', Hội đồng xét kiểm tra kết thúc môn học '
            .($this->bbth['ten_khoa'] ?? '')
            .' gồm '.$soThiSinh.' thí sinh học, căn cứ '.$qd
            .' về việc thành lập Hội đồng xét kiểm tra kết thúc môn học.';
    }

    public static function dongNgay(string $diaDiem, string $ngay): string
    {
        try {
            $c = Carbon::createFromFormat('d/m/Y', $ngay);
        } catch (\Throwable) {
            try {
                $c = Carbon::parse($ngay);
            } catch (\Throwable) {
                return trim($diaDiem.', ngày '.$ngay);
            }
        }

        return $diaDiem.', ngày '.$c->format('d').' tháng '.$c->format('m').' năm '.$c->format('Y');
    }
}