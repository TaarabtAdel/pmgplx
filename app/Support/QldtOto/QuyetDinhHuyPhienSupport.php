<?php

namespace App\Support\QldtOto;

use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienKiemTra;
use Illuminate\Support\Collection;

final class QuyetDinhHuyPhienSupport
{
    /**
     * Phiên không đạt kiểm tra DAT của khoá (theo MaKhoaHoc = MaKH PMGPLX).
     *
     * @return list<array{ten_khoa: string, ho_ten_gv: string, ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>
     */
    public static function phienLoiTuDat(string $maKhoa): array
    {
        $maKhoa = trim($maKhoa);
        if ($maKhoa === '') {
            return [];
        }

        /** @var Collection<int, DatDSPhien> $sessions */
        $sessions = DatDSPhien::query()
            ->where('MaKhoaHoc', $maKhoa)
            ->orderBy('ThoiGianBatDauPhienHoc')
            ->orderBy('Id')
            ->get();

        if ($sessions->isEmpty()) {
            return [];
        }

        $violationsById = DatDSPhienKiemTra::analyze($sessions);

        $rows = [];
        foreach ($sessions as $session) {
            $id = (int) $session->Id;
            if (DatDSPhienKiemTra::datPhien($violationsById, $id)) {
                continue;
            }

            $codes = $violationsById[$id] ?? [];
            $labels = array_map(
                fn (string $code): string => DatDSPhienKiemTra::violationLabel($code),
                $codes
            );
            if (DatDSPhienKiemTra::khongDatDoPhanLoai($id)) {
                $labels[] = 'Chưa đạt phân loại phiên (DAT)';
            }
            $labels = array_values(array_unique(array_filter($labels)));

            $rows[] = [
                'ho_ten' => trim((string) ($session->HoTenHocVien ?? '')),
                'ma_hv' => trim((string) ($session->MaHocVien ?? '')),
                'ma_phien' => trim((string) ($session->MaPhienHoc ?? '')),
                'ten_khoa' => trim((string) ($session->TenKhoaHoc ?? '')),
                'ho_ten_gv' => trim((string) ($session->HoTenGiaoVien ?? '')),
                'phuong_an' => BaoCaoPhienHocLoiDocx::PHUONG_AN_MAC_DINH,
                'ly_do' => $labels !== [] ? implode('; ', $labels) : 'Phiên không đạt điều kiện',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{ngay_bao_cao: string, de_xuat: string, phien: list<array{ten_khoa: string, ho_ten_gv: string, ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>}
     */
    public static function normalizePayload(array $validated): array
    {
        $phien = [];
        foreach ($validated['phien'] as $row) {
            $tenKhoa = trim((string) ($row['ten_khoa'] ?? ''));
            $hoTenGv = trim((string) ($row['ho_ten_gv'] ?? ''));
            $hoTen = trim((string) ($row['ho_ten'] ?? ''));
            $maHv = trim((string) ($row['ma_hv'] ?? ''));
            $maPhien = trim((string) ($row['ma_phien'] ?? ''));
            $lyDo = trim((string) ($row['ly_do'] ?? ''));
            if ($tenKhoa === '' && $hoTenGv === '' && $hoTen === '' && $maHv === '' && $maPhien === '' && $lyDo === '') {
                continue;
            }
            $phien[] = [
                'ten_khoa' => $tenKhoa,
                'ho_ten_gv' => $hoTenGv,
                'ho_ten' => $hoTen,
                'ma_hv' => $maHv,
                'ma_phien' => $maPhien,
                'phuong_an' => trim((string) ($row['phuong_an'] ?? '')) ?: BaoCaoPhienHocLoiDocx::PHUONG_AN_MAC_DINH,
                'ly_do' => $lyDo,
            ];
        }

        return [
            'ngay_bao_cao' => (string) $validated['ngay_bao_cao'],
            'de_xuat' => trim((string) ($validated['de_xuat'] ?? '')) ?: BaoCaoPhienHocLoiDocx::DE_XUAT_MAC_DINH,
            'phien' => $phien,
        ];
    }
}
