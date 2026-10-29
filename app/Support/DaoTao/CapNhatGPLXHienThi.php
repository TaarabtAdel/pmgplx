<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\CapNhatGPLXHocVien;
use App\Models\DaoTao\CapNhatGPLXHocVienBoSung;
use App\Support\PMGPLX\DmDvhcTenDayDu;
use App\Support\PMGPLX\NgayVn;

/** Ghép PMGPLX + MANHLINH cho bảng báo cáo cập nhật GPLX (giống mẫu Excel). */
final class CapNhatGPLXHienThi
{
    /**
     * @return array{
     *     ho_ten: string,
     *     ma_hoc_vien: string,
     *     ho_ten_xuat: string,
     *     ngay_sinh: string,
     *     so_cmnd: string,
     *     noi_dk_ho_khau: string,
     *     noi_dk_ho_khau_pm: string,
     *     dia_chi_thuong_tru: string,
     *     noi_cu_tru: string,
     *     da_co_gplx: string,
     *     so_gplx: string,
     *     ngay_cap_gplx: string,
     *     ngay_trung_tuyen: string,
     *     pm: array<string, bool>,
     *     ml: array<string, bool>
     * }
     */
    /**
     * @param  array<string, string>  $dvhcMap
     */
    public static function dong(
        CapNhatGPLXHocVien $row,
        ?object $hv,
        ?CapNhatGPLXHocVienBoSung $bs,
        array $dvhcMap = [],
    ): array {
        $hoTen = $hv ? ($hv->HoVaTen ?: trim(($hv->HoDemNLX ?? '').' '.($hv->TenNLX ?? ''))) : '';
        $maHocVien = trim((string) ($hv->MaDK ?? $row->MaDK ?? ''));

        $diaChi = DmDvhcTenDayDu::diaChiTuNguoiLx($hv, $dvhcMap);
        $noiPmThuongTru = $diaChi['thuong_tru'];
        $noiPmCuTru = $diaChi['cu_tru'];
        $noiBs = trim((string) ($bs?->NoiDKHoKhauThuongTru ?? ''));

        $daCoPm = trim((string) ($hv->HangGPLXDaCo ?? ''));
        $daCoBs = trim((string) ($bs?->DaCoGPLX ?? ''));

        $soPm = trim((string) ($hv->SoGPLXDaCo ?? ''));
        $soMl = trim((string) ($row->SoGPLX ?? ''));
        $soTmp = trim((string) ($hv->SoGPLXTmp ?? ''));

        $ngayCapPm = self::fmtNgay($hv->NgayCapGPLXDaCo ?? null);
        $ngayCapMl = $row->NgayCapGPLX?->format('d/m/Y') ?? '';

        $ngayTtPm = self::fmtNgay($hv->NgayTTGPLXDaCo ?? null);
        $ngayTtBs = $bs?->NgayTrungTuyen?->format('d/m/Y') ?? '';

        return [
            'ho_ten' => $hoTen,
            'ma_hoc_vien' => $maHocVien,
            'ho_ten_xuat' => self::hoTenKemMaHocVien($hoTen, $maHocVien),
            'ngay_sinh' => $hv ? NgayVn::format($hv->NgaySinh, 'yyyymmdd') : '',
            'so_cmnd' => (string) ($hv->SoCMT ?? ''),
            'noi_dk_ho_khau' => $noiBs !== '' ? $noiBs : $noiPmThuongTru,
            'noi_dk_ho_khau_pm' => $noiPmThuongTru,
            'dia_chi_thuong_tru' => $noiBs !== '' ? $noiBs : $noiPmThuongTru,
            'noi_cu_tru' => $noiPmCuTru,
            'da_co_gplx' => $daCoBs !== '' ? $daCoBs : $daCoPm,
            'so_gplx' => $soMl !== '' ? $soMl : ($soPm !== '' ? $soPm : $soTmp),
            'ngay_cap_gplx' => $ngayCapMl !== '' ? $ngayCapMl : $ngayCapPm,
            'ngay_trung_tuyen' => $ngayTtBs !== '' ? $ngayTtBs : $ngayTtPm,
            'pm' => [
                'noi_dk_ho_khau' => $noiPmThuongTru !== '',
                'dia_chi_thuong_tru' => $noiPmThuongTru !== '',
                'noi_cu_tru' => $noiPmCuTru !== '',
                'da_co_gplx' => $daCoPm !== '',
                'so_gplx' => $soPm !== '' || $soTmp !== '',
                'ngay_cap_gplx' => $ngayCapPm !== '',
                'ngay_trung_tuyen' => $ngayTtPm !== '',
            ],
            'ml' => [
                'noi_dk_ho_khau' => $noiBs !== '',
                'da_co_gplx' => $daCoBs !== '',
                'ngay_trung_tuyen' => $ngayTtBs !== '',
            ],
        ];
    }

    /**
     * Giá trị mặc định form bổ sung: MANHLINH đã lưu, không có thì gợi ý từ PM (chỉ hiển thị, lưu vẫn vào MANHLINH).
     *
     * @param  array<string, string>  $dvhcMap
     */
    public static function giaTriFormBoSung(?CapNhatGPLXHocVienBoSung $bs, ?object $hv, array $dvhcMap = []): array
    {
        $noiPm = DmDvhcTenDayDu::diaChiTuNguoiLx($hv, $dvhcMap)['thuong_tru'];
        $noiBs = trim((string) ($bs?->NoiDKHoKhauThuongTru ?? ''));
        $daCoBs = trim((string) ($bs?->DaCoGPLX ?? ''));
        $daCoPm = trim((string) ($hv?->HangGPLXDaCo ?? ''));

        return [
            'noi_dk_ho_khau' => $noiBs !== '' ? $noiBs : $noiPm,
            'da_co_gplx' => $daCoBs !== '' ? $daCoBs : $daCoPm,
            'ngay_trung_tuyen' => $bs?->NgayTrungTuyen?->format('Y-m-d')
                ?? self::ngayPmSangInput($hv?->NgayTTGPLXDaCo ?? null),
        ];
    }

    /**
     * Số GPLX / ngày cấp trên form: MANHLINH đã lưu, không có thì gợi ý PMGPLX (giống cột báo cáo).
     *
     * @return array{so_gplx: string, ngay_cap_gplx: string}
     */
    public static function giaTriFormGplx(CapNhatGPLXHocVien $row, ?object $hv): array
    {
        $soMl = trim((string) ($row->SoGPLX ?? ''));
        $soPm = trim((string) ($hv?->SoGPLXDaCo ?? ''));
        $soTmp = trim((string) ($hv?->SoGPLXTmp ?? ''));
        $so = $soMl !== '' ? $soMl : ($soPm !== '' ? $soPm : $soTmp);

        $ngayMl = $row->NgayCapGPLX?->format('Y-m-d') ?? '';
        $ngayPm = self::ngayPmSangInput($hv?->NgayCapGPLXDaCo ?? null);

        return [
            'so_gplx' => $so,
            'ngay_cap_gplx' => $ngayMl !== '' ? $ngayMl : $ngayPm,
        ];
    }

    /**
     * Ghi / cập nhật bảng bổ sung MANHLINH (không ghi PMGPLX).
     *
     * @param  array<string, mixed>  $input
     */
    public static function luuBoSungManhlinh(CapNhatGPLXHocVien $row, array $input): void
    {
        $payload = [
            'NoiDKHoKhauThuongTru' => trim((string) ($input['noi_dk_ho_khau'] ?? '')) ?: null,
            'DaCoGPLX' => trim((string) ($input['da_co_gplx'] ?? '')) ?: null,
            'NgayTrungTuyen' => ! empty($input['ngay_trung_tuyen']) ? $input['ngay_trung_tuyen'] : null,
        ];

        if ($payload['NoiDKHoKhauThuongTru'] === null
            && $payload['DaCoGPLX'] === null
            && $payload['NgayTrungTuyen'] === null) {
            CapNhatGPLXHocVienBoSung::query()->where('CapNhatGPLXHocVienId', $row->Id)->delete();

            return;
        }

        $payload['MaDK'] = $row->MaDK;
        $payload['NgayCapNhat'] = now();

        CapNhatGPLXHocVienBoSung::query()->updateOrCreate(
            ['CapNhatGPLXHocVienId' => $row->Id],
            $payload
        );
    }

    private static function ngayPmSangInput(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (strlen($s) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            return substr($s, 0, 10);
        }
        if (strlen($s) >= 8 && ctype_digit($s)) {
            return substr($s, 0, 4).'-'.substr($s, 4, 2).'-'.substr($s, 6, 2);
        }

        return '';
    }

    /** Họ tên + xuống dòng + mã học viên (Excel / mẫu báo cáo). */
    public static function hoTenKemMaHocVien(string $hoTen, string $maHocVien): string
    {
        $hoTen = trim($hoTen);
        $maHocVien = trim($maHocVien);
        if ($hoTen === '') {
            return $maHocVien;
        }
        if ($maHocVien === '') {
            return $hoTen;
        }

        return $hoTen."\n".$maHocVien;
    }

    private static function fmtNgay(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (strlen($s) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            return substr($s, 8, 2).'/'.substr($s, 5, 2).'/'.substr($s, 0, 4);
        }

        return NgayVn::format($s, 'yyyymmdd');
    }
}
