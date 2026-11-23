<?php

namespace App\Support\QldtOto\KtmChiTiet;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class LichSuUploadKtmChiTiet
{
    /** @var array<string, string> */
    public const LOAI = [
        'file_danh_sach' => '1. Danh sách thí sinh',
        'file_lt' => '2. Lý thuyết — Kết quả',
        'file_mp' => '3. Mô phỏng — Kết quả',
        'file_cc' => '4. CC (chữ chi) — Kết quả',
        'file_h_dat' => '5. Hình — Đạt',
        'file_h_truot' => '6. Hình — Trượt',
        'file_dt_dat' => '7. Đường trường — Đạt',
        'file_dt_truot' => '8. Đường trường — Trượt',
    ];

    /**
     * @param  list<array<string, mixed>>  $thiSinhGhep
     * @param  array<string, string>  $lt
     * @param  array<string, string>  $mp
     * @param  array<string, string>  $cc
     * @param  array<string, string>  $hDat
     * @param  array<string, string>  $hTruot
     * @param  array<string, string>  $dtDat
     * @param  array<string, string>  $dtTruot
     * @return list<array{loai: string, ten_file: string, thoi_gian: string, so_hv: int}>
     */
    public static function tuLanUpload(
        Request $request,
        array $thiSinhGhep,
        array $lt,
        array $mp,
        array $cc,
        array $hDat,
        array $hTruot,
        array $dtDat,
        array $dtTruot,
    ): array {
        $gio = now()->format('d/m/Y H:i');
        $out = [];

        if ($request->hasFile('file_danh_sach')) {
            $out[] = self::dong($request->file('file_danh_sach'), 'file_danh_sach', $gio, count($thiSinhGhep));
        }
        if ($request->hasFile('file_lt')) {
            $out[] = self::dong($request->file('file_lt'), 'file_lt', $gio, self::dem($thiSinhGhep, $lt));
        }
        if ($request->hasFile('file_mp')) {
            $out[] = self::dong($request->file('file_mp'), 'file_mp', $gio, self::dem($thiSinhGhep, $mp));
        }
        if ($request->hasFile('file_cc')) {
            $out[] = self::dong($request->file('file_cc'), 'file_cc', $gio, self::dem($thiSinhGhep, $cc));
        }
        if ($request->hasFile('file_h_dat')) {
            $out[] = self::dong($request->file('file_h_dat'), 'file_h_dat', $gio, self::dem($thiSinhGhep, $hDat));
        }
        if ($request->hasFile('file_h_truot')) {
            $out[] = self::dong($request->file('file_h_truot'), 'file_h_truot', $gio, self::dem($thiSinhGhep, $hTruot));
        }
        if ($request->hasFile('file_dt_dat')) {
            $out[] = self::dong($request->file('file_dt_dat'), 'file_dt_dat', $gio, self::dem($thiSinhGhep, $dtDat));
        }
        if ($request->hasFile('file_dt_truot')) {
            $out[] = self::dong($request->file('file_dt_truot'), 'file_dt_truot', $gio, self::dem($thiSinhGhep, $dtTruot));
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $thiSinhGhep
     * @param  array<string, string>  $bang
     */
    private static function dem(array $thiSinhGhep, array $bang): int
    {
        return GhepBaoCaoChiTiet::demThiSinhKhopBangTuGhep($thiSinhGhep, $bang);
    }

    /**
     * @return array{loai: string, ten_file: string, thoi_gian: string, so_hv: int}
     */
    private static function dong(UploadedFile $file, string $key, string $thoiGian, int $soHv): array
    {
        return [
            'loai' => self::LOAI[$key] ?? $key,
            'ten_file' => $file->getClientOriginalName(),
            'thoi_gian' => $thoiGian,
            'so_hv' => $soHv,
        ];
    }
}