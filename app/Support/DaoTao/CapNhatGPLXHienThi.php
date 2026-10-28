<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\CapNhatGPLXHocVien;
use App\Models\DaoTao\CapNhatGPLXHocVienBoSung;
use App\Support\PMGPLX\NgayVn;

/** Ghép PMGPLX + MANHLINH cho bảng báo cáo cập nhật GPLX (giống mẫu Excel). */
final class CapNhatGPLXHienThi
{
    /**
     * @return array{
     *     ho_ten: string,
     *     ngay_sinh: string,
     *     so_cmnd: string,
     *     noi_dk_ho_khau: string,
     *     da_co_gplx: string,
     *     so_gplx: string,
     *     ngay_cap_gplx: string,
     *     ngay_trung_tuyen: string,
     *     pm: array<string, bool>
     * }
     */
    public static function dong(
        CapNhatGPLXHocVien $row,
        ?object $hv,
        ?CapNhatGPLXHocVienBoSung $bs,
    ): array {
        $hoTen = $hv ? ($hv->HoVaTen ?: trim(($hv->HoDemNLX ?? '').' '.($hv->TenNLX ?? ''))) : '';

        $noiPm = trim((string) ($hv->NoiTT ?? ''));
        if ($noiPm === '') {
            $noiPm = trim((string) ($hv->NoiCT ?? ''));
        }
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
            'ngay_sinh' => $hv ? NgayVn::format($hv->NgaySinh, 'yyyymmdd') : '',
            'so_cmnd' => (string) ($hv->SoCMT ?? ''),
            'noi_dk_ho_khau' => $noiPm !== '' ? $noiPm : $noiBs,
            'da_co_gplx' => $daCoPm !== '' ? $daCoPm : $daCoBs,
            'so_gplx' => $soMl !== '' ? $soMl : ($soPm !== '' ? $soPm : $soTmp),
            'ngay_cap_gplx' => $ngayCapMl !== '' ? $ngayCapMl : $ngayCapPm,
            'ngay_trung_tuyen' => $ngayTtPm !== '' ? $ngayTtPm : $ngayTtBs,
            'pm' => [
                'noi_dk_ho_khau' => $noiPm !== '',
                'da_co_gplx' => $daCoPm !== '',
                'so_gplx' => $soPm !== '' || $soTmp !== '',
                'ngay_cap_gplx' => $ngayCapPm !== '',
                'ngay_trung_tuyen' => $ngayTtPm !== '',
            ],
        ];
    }

    /**
     * Lưu bổ sung: chỉ ghi MANHLINH khi PMGPLX không có giá trị tương ứng.
     *
     * @param  array<string, mixed>  $input
     */
    public static function luuBoSungThieuPm(
        CapNhatGPLXHocVien $row,
        ?object $hv,
        array $input,
    ): void {
        $noiPm = trim((string) ($hv?->NoiTT ?? ''));
        if ($noiPm === '') {
            $noiPm = trim((string) ($hv?->NoiCT ?? ''));
        }
        $daCoPm = trim((string) ($hv?->HangGPLXDaCo ?? ''));
        $ngayTtPm = self::fmtNgay($hv?->NgayTTGPLXDaCo ?? null);

        $payload = [];
        if ($noiPm === '') {
            $v = trim((string) ($input['noi_dk_ho_khau'] ?? ''));
            $payload['NoiDKHoKhauThuongTru'] = $v !== '' ? $v : null;
        }
        if ($daCoPm === '') {
            $v = trim((string) ($input['da_co_gplx'] ?? ''));
            $payload['DaCoGPLX'] = $v !== '' ? $v : null;
        }
        if ($ngayTtPm === '') {
            $payload['NgayTrungTuyen'] = ! empty($input['ngay_trung_tuyen']) ? $input['ngay_trung_tuyen'] : null;
        }

        if ($payload === []) {
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
