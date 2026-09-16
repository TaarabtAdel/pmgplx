<?php

namespace App\Support\SatHach;

use App\Models\DaoTao\SatHachBienBan;
use Illuminate\Support\Facades\DB;

class SatHachBienBanImporter
{
    /**
     * @param  array{
     *     header: array<string, string>,
     *     ky_sh: array<string, string>,
     *     thi_sinh: list<array<string, mixed>>
     * }  $parsed
     * @return array{saved: int, updated: int, ma_ky_sh: string}
     */
    public function import(array $parsed, string $fileName): array
    {
        $header = $parsed['header'] ?? [];
        $kySh = $parsed['ky_sh'] ?? [];
        $maKySh = trim((string) ($kySh['MAKYSH'] ?? $header['MA_GIAO_DICH'] ?? 'KHONG-MA'));
        if ($maKySh === '') {
            $maKySh = 'KHONG-MA';
        }
        $maGiaoDich = trim((string) ($header['MA_GIAO_DICH'] ?? ''));
        $ngaySh = trim((string) ($kySh['NGAYSH'] ?? ''));
        $soQd = trim((string) ($kySh['SOQD'] ?? ''));
        $now = now();

        $saved = 0;
        $updated = 0;

        DB::connection('sqlsrv_manhlinh')->transaction(function () use (
            $parsed,
            $fileName,
            $maKySh,
            $maGiaoDich,
            $ngaySh,
            $soQd,
            $now,
            &$saved,
            &$updated
        ): void {
            foreach ($parsed['thi_sinh'] as $row) {
                $maDk = trim((string) ($row['ma_dk'] ?? ''));
                if ($maDk === '') {
                    $maDk = trim((string) ($row['so_bao_danh'] ?? ''));
                }
                if ($maDk === '') {
                    continue;
                }

                $payload = [
                    'MaKySH' => $maKySh,
                    'MaGiaoDich' => $maGiaoDich !== '' ? $maGiaoDich : null,
                    'NgaySH' => $ngaySh !== '' ? $ngaySh : null,
                    'SoQD' => $soQd !== '' ? $soQd : null,
                    'SoTT' => $row['so_tt'] ?? null,
                    'MaDK' => $maDk,
                    'HoVaTen' => $row['ho_va_ten'] ?? null,
                    'NgaySinh' => $row['ngay_sinh'] ?? null,
                    'SoCMT' => $row['so_cmt'] ?? null,
                    'SoHoChieu' => $row['so_ho_chieu'] ?? null,
                    'NgayCapHC' => $row['ngay_cap_hc'] ?? null,
                    'NoiCapHC' => $row['noi_cap_hc'] ?? null,
                    'SoBaoDanh' => $row['so_bao_danh'] ?? null,
                    'HangGPLX' => $row['hang_gplx'] ?? null,
                    'DiemLtDat' => $row['diem_lt_dat'] ?? null,
                    'DiemHinhDat' => $row['diem_hinh_dat'] ?? null,
                    'DiemDuongDat' => $row['diem_duong_dat'] ?? null,
                    'NhanXetLt' => $row['nhan_xet_lt'] ?? null,
                    'NhanXetHinh' => $row['nhan_xet_hinh'] ?? null,
                    'NhanXetDuong' => $row['nhan_xet_duong'] ?? null,
                    'KetQuaSH' => $row['ket_qua_sh'] ?? null,
                    'NgayKy' => $row['ngay_ky'] ?? null,
                    'ThangKy' => $row['thang_ky'] ?? null,
                    'NamKy' => $row['nam_ky'] ?? null,
                    'AnhChanDung' => $row['anh_chan_dung_b64'] ?? null,
                    'FileNguon' => $fileName,
                    'NgayNhap' => $now,
                ];

                $existing = SatHachBienBan::query()
                    ->where('MaKySH', $maKySh)
                    ->where('MaDK', $maDk)
                    ->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                    $updated++;
                } else {
                    SatHachBienBan::query()->create($payload);
                    $saved++;
                }
            }
        });

        return [
            'saved' => $saved,
            'updated' => $updated,
            'ma_ky_sh' => $maKySh,
        ];
    }
}
