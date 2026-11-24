<?php

namespace App\Support\QldtOto;

use App\Support\DaoTao\PhuLuc1DatBaoCaoHoTro;
use Carbon\Carbon;
use ZipArchive;

final class PhuLuc1DatCabinZip
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $maHocViens
     */
    public static function taiZip(
        array $filters,
        array $maHocViens,
        string $ngayBaoCao,
        string $outputZipPath
    ): int {
        $ctx = PhuLuc1DatBaoCaoHoTro::taiPhienVaKiemTra($filters);
        $theoHv = PhuLuc1DatBaoCaoHoTro::nhomTheoHocVien($ctx['sessions']);
        $ngaySinh = PhuLuc1DatBaoCaoHoTro::ngaySinhTheoMaHocVien($maHocViens);

        @mkdir(dirname($outputZipPath), 0755, true);
        if (is_file($outputZipPath)) {
            unlink($outputZipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($outputZipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Không tạo được file ZIP.');
        }

        $tempFiles = [];
        $count = 0;

        foreach ($maHocViens as $maHv) {
            $phien = $theoHv[$maHv] ?? [];
            if ($phien === []) {
                continue;
            }

            $tomTat = PhuLuc1DatBaoCaoHoTro::baoCaoMotHocVien(
                $maHv,
                $phien,
                $ctx['violationsById'],
            );

            $payload = [
                'ngay_bao_cao' => $ngayBaoCao,
                'ho_ten' => $tomTat['ho_ten'],
                'ma_hoc_vien' => $maHv,
                'ngay_sinh' => PhuLuc1DatBaoCaoHoTro::ngaySinhHienThi($ngaySinh[$maHv] ?? null),
                'ma_khoa_hoc' => $tomTat['ma_khoa_hoc'],
                'ten_khoa_hoc' => $tomTat['ten_khoa_hoc'],
                'hang_dao_tao' => $tomTat['hang_dao_tao'],
                'phien' => $tomTat['phien'],
                'tong_thoi_gian' => $tomTat['tong_thoi_gian'],
                'tong_quang_duong' => $tomTat['tong_quang_duong'],
                'ket_luan' => PhuLuc1DatBaoCaoHoTro::ketLuanChuongTrinh(
                    $maHv,
                    $tomTat['hang_dao_tao'],
                    $phien,
                    $ctx['violationsById'],
                ),
            ];

            $tmp = sys_get_temp_dir().'/'.uniqid('pl1-', true).'.docx';
            PhuLuc1DatCabinDocx::generate($payload, $tmp);
            $tempFiles[] = $tmp;
            $zip->addFile($tmp, PhuLuc1DatCabinDocx::tenFile($maHv));
            $count++;
        }

        $zip->close();

        foreach ($tempFiles as $f) {
            @unlink($f);
        }

        if ($count === 0) {
            throw new \InvalidArgumentException('Không có học viên nào có phiên DAT để xuất.');
        }

        return $count;
    }

    public static function tenFileZip(string $maKhoa): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $maKhoa) ?: 'khoa';

        return 'phu-luc-1-dat-'.$safe.'-'.now()->format('Ymd-His').'.zip';
    }
}