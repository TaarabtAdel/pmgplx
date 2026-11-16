<?php

namespace App\Support\QldtOto\KtmBaoCao;

use Carbon\Carbon;

/** Giá trị mặc định form tham số (3 báo cáo) sau khi đọc file nhập. */
final class KtmBaoCaoFormMacDinh
{
    /** dd/mm/yyyy → Y-m-d cho &lt;input type="date"&gt; */
    public static function ngaySangInput(?string $vn): string
    {
        $vn = trim((string) $vn);
        if ($vn === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $vn)) {
            return $vn;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $vn)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                return Carbon::parse($vn)->format('Y-m-d');
            } catch (\Throwable) {
                return '';
            }
        }
    }

    /** Y-m-d hoặc dd/mm/yyyy → dd/mm/yyyy lưu / xuất Excel */
    public static function ngayTuInput(?string $val): string
    {
        $val = trim((string) $val);
        if ($val === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $val)->format('d/m/Y');
            } catch (\Throwable) {
                return $val;
            }
        }

        return $val;
    }

    /** 7h30, 07:30 → HH:MM cho &lt;input type="time"&gt; */
    public static function gioSangInput(?string $gio): string
    {
        $gio = trim((string) $gio);
        if ($gio === '') {
            return '';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $gio, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        if (preg_match('/^(\d{1,2})h(\d{2})$/iu', $gio, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return '';
    }

    /** HH:MM → 7h30 (giờ không zero-pad) */
    public static function gioTuInput(?string $hhmm): string
    {
        $hhmm = trim((string) $hhmm);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) {
            return (int) $m[1].'h'.$m[2];
        }

        return $hhmm;
    }
    /**
     * @return array{bbth: array<string, mixed>, chi_tiet: array<string, mixed>, bc_chung: array<string, mixed>}
     */
    public static function tao(KtmKyThiDuLieu $duLieu, ?array $cfg = null): array
    {
        $cfg = $cfg ?? config('qldt_oto_ktm_bao_cao', []);
        $ngay = $duLieu->ngayThi;
        $diaDiem = (string) ($cfg['dia_diem_so'] ?? 'Quảng Trị');
        $satHach = (string) ($cfg['sat_hach'] ?? '');

        $thanhVien = $cfg['bbth']['thanh_vien_dong'] ?? [
            '1.Bà: Nguyễn Thị Vinh - Giám đốc Trung tâm - Chủ tịch',
            '2.Ông: Trương Hoàng Linh - Phó Giám đốc Trung tâm - Phó Chủ tịch',
            '3.Ông: Nguyễn Cửu Phúc - Phó tổ trưởng tổ GVTH - Ủy viên',
            '4.Ông: Hoàng Hải - Nhân viên phòng đào tạo - Thư ký',
        ];

        $kyBbth = $cfg['bbth']['ky_bbth'] ?? [];

        return [
            'bbth' => [
                'ten_khoa' => $duLieu->tenKhoa,
                'dia_diem_so' => $diaDiem,
                'ngay_lap' => $ngay,
                'ngay_hop' => $ngay,
                'gio_hop' => (string) ($cfg['bbth']['gio_hop'] ?? '7h30'),
                'dia_diem_hop' => (string) ($cfg['bbth']['dia_diem_hop'] ?? ''),
                'so_qd' => (string) ($cfg['bbth']['so_qd'] ?? ''),
                'ngay_qd' => (string) ($cfg['bbth']['ngay_qd'] ?? ''),
                'ty_le_mau_so' => (string) ($cfg['ty_le_mau_so'] ?? 'du_thi'),
                'thanh_vien' => array_values($thanhVien),
                'nhan_xet' => array_values($cfg['bbth']['nhan_xet'] ?? []),
                'ky_uy_vien' => (string) ($kyBbth['uy_vien'] ?? ''),
                'ky_pho_chu_tich' => (string) ($kyBbth['pho_chu_tich'] ?? ''),
                'ky_chu_tich' => (string) ($kyBbth['chu_tich'] ?? ''),
                'ky_thu_ky' => (string) ($kyBbth['thu_ky'] ?? ''),
            ],
            'chi_tiet' => [
                'dia_diem_so' => $diaDiem,
                'ngay_lap' => $ngay,
                'sat_hach' => $satHach,
                'ngay_thi' => $ngay,
                'ky_giam_doc' => (string) ($cfg['ky_bc']['giam_doc'] ?? ''),
                'ky_pho_giam_doc' => (string) ($cfg['ky_bc']['pho_giam_doc'] ?? ''),
            ],
            'bc_chung' => [
                'dia_diem_so' => $diaDiem,
                'ngay_lap' => $ngay,
                'sat_hach' => $satHach,
                'ngay_thi' => $ngay,
                'ky_giam_doc' => (string) ($cfg['ky_bc']['giam_doc'] ?? ''),
                'ky_pho_giam_doc' => (string) ($cfg['ky_bc']['pho_giam_doc'] ?? ''),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $saved
     * @param  array<string, mixed>  $posted
     * @return array{bbth: array<string, mixed>, chi_tiet: array<string, mixed>, bc_chung: array<string, mixed>}
     */
    public static function hopNhat(array $saved, array $posted): array
    {
        $out = $saved;
        foreach (['bbth', 'chi_tiet', 'bc_chung'] as $k) {
            if (! isset($posted[$k]) || ! is_array($posted[$k])) {
                continue;
            }
            $out[$k] = array_merge($out[$k] ?? [], $posted[$k]);
        }
        if (isset($posted['bbth']['thanh_vien']) && is_array($posted['bbth']['thanh_vien'])) {
            $out['bbth']['thanh_vien'] = array_values(array_map('strval', $posted['bbth']['thanh_vien']));
        }
        if (isset($posted['bbth']['nhan_xet']) && is_array($posted['bbth']['nhan_xet'])) {
            $out['bbth']['nhan_xet'] = array_values(array_map('strval', $posted['bbth']['nhan_xet']));
        }

        return self::chuanHoaNgayGio($out);
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function chuanHoaNgayGio(array $form): array
    {
        foreach (['ngay_lap', 'ngay_hop', 'ngay_qd'] as $k) {
            if (isset($form['bbth'][$k])) {
                $form['bbth'][$k] = self::ngayTuInput((string) $form['bbth'][$k]);
            }
        }
        if (isset($form['bbth']['gio_hop'])) {
            $form['bbth']['gio_hop'] = self::gioTuInput((string) $form['bbth']['gio_hop']);
        }
        foreach (['chi_tiet', 'bc_chung'] as $nhom) {
            foreach (['ngay_lap', 'ngay_thi'] as $k) {
                if (isset($form[$nhom][$k])) {
                    $form[$nhom][$k] = self::ngayTuInput((string) $form[$nhom][$k]);
                }
            }
        }

        return $form;
    }
}