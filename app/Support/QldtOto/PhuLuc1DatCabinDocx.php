<?php

namespace App\Support\QldtOto;

use App\Support\DaoTao\PhuLuc1DatBaoCaoHoTro;
use App\Support\Docx\DocxNamespaceFixer;
use App\Support\PMGPLX\DuongDanAnhPmGplx;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

final class PhuLuc1DatCabinDocx
{
    private const TEMPLATE = 'templates/oto/bao-cao-hoc-vien-dat.goc.docx';

    /** Ô ảnh mẫu ~3000 DXA (~2.08"); chân dung 3×4. */
    private const ANH_WIDTH_IN = 1.7;

    private const ANH_HEIGHT_IN = 2.2;

    /**
     * @param  array{
     *   ngay_bao_cao: string,
     *   ho_ten: string,
     *   ma_hoc_vien: string,
     *   ngay_sinh: string,
     *   ma_khoa_hoc: string,
     *   ten_khoa_hoc: string,
     *   hang_dao_tao: string,
     *   phien: list<array{stt: int, ma_phien: string, ngay_dao_tao: string, thoi_gian: string, quang_duong: string, ghi_chu: string}>,
     *   tong_thoi_gian: float,
     *   tong_quang_duong: float,
     *   ket_luan: string,
     *   duong_dan_anh?: ?string,
     * }  $data
     */
    public static function generate(array $data, string $outputPath): void
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu: '.self::TEMPLATE);
        }

        $phien = array_values($data['phien'] ?? []);
        if ($phien === []) {
            throw new \InvalidArgumentException('Học viên không có phiên DAT trong bộ lọc.');
        }

        @mkdir(dirname($outputPath), 0755, true);
        $workDir = dirname($outputPath).DIRECTORY_SEPARATOR.'pl1-anh-'.uniqid('', true);
        @mkdir($workDir, 0755, true);

        $processor = new TemplateProcessor($template);

        try {
            $ngay = Carbon::parse($data['ngay_bao_cao']);
            $processor->setValue('dia_diem', self::esc('Quảng Trị'));
            $processor->setValue('ngay', (string) $ngay->day);
            $processor->setValue('thang', (string) $ngay->month);
            $processor->setValue('nam', (string) $ngay->year);
            $processor->setValue('ho_ten', self::esc($data['ho_ten'] ?? ''));
            $processor->setValue('ma_hoc_vien', self::esc($data['ma_hoc_vien'] ?? ''));
            $processor->setValue('ngay_sinh', self::esc($data['ngay_sinh'] ?? ''));
            $processor->setValue('ma_khoa_hoc', self::esc($data['ma_khoa_hoc'] ?? ''));
            $processor->setValue('ten_khoa_hoc', self::esc($data['ten_khoa_hoc'] ?? ''));
            $processor->setValue('hang_dao_tao', self::esc($data['hang_dao_tao'] ?? ''));
            $processor->setValue('co_so_dao_tao', self::esc(PhuLuc1DatBaoCaoHoTro::CO_SO));
            self::ganAnhHocVien($processor, $data, $workDir);
            $processor->setValue('stt', '1');

            $processor->cloneRow('ma_phien', count($phien));
            foreach ($phien as $i => $row) {
                $n = $i + 1;
                $processor->setValue('stt#'.$n, (string) ($row['stt'] ?? $n));
                $processor->setValue('ma_phien#'.$n, self::esc($row['ma_phien'] ?? ''));
                $processor->setValue('ngay_dao_tao#'.$n, self::esc($row['ngay_dao_tao'] ?? ''));
                $processor->setValue('thoi_gian#'.$n, self::esc($row['thoi_gian'] ?? '0'));
                $processor->setValue('quang_duong#'.$n, self::esc($row['quang_duong'] ?? '0'));
                $processor->setValue('ghi_chu#'.$n, self::esc($row['ghi_chu'] ?? ''));
            }

            $processor->setValue('tong_thoi_gian', self::esc(
                \App\Support\DaoTao\DatHocVienTongHop::formatNumber($data['tong_thoi_gian'] ?? 0, 2)
            ));
            $processor->setValue('tong_quang_duong', self::esc(
                \App\Support\DaoTao\DatHocVienTongHop::formatNumber($data['tong_quang_duong'] ?? 0, 2)
            ));
            $processor->setValue('ket_luan', self::esc($data['ket_luan'] ?? ''));

            $processor->saveAs($outputPath);
            DocxNamespaceFixer::repairFile($outputPath);
        } finally {
            self::xoaThuMucTam($workDir);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function ganAnhHocVien(TemplateProcessor $processor, array $data, string $workDir): void
    {
        $duongDan = trim((string) ($data['duong_dan_anh'] ?? ''));
        if ($duongDan === '') {
            $maHv = trim((string) ($data['ma_hoc_vien'] ?? ''));
            if ($maHv !== '') {
                $duongDan = PhuLuc1DatBaoCaoHoTro::duongDanAnhTheoMaHocVien([$maHv])[$maHv] ?? '';
            }
        }

        $img = $duongDan !== ''
            ? DuongDanAnhPmGplx::prepareDocxImagePath($duongDan, $workDir)
            : null;

        if ($img === null) {
            $processor->setValue('anh_hoc_vien', '');

            return;
        }

        try {
            $processor->setImageValue('anh_hoc_vien', [
                'path' => $img,
                'width' => self::ANH_WIDTH_IN.'in',
                'height' => self::ANH_HEIGHT_IN.'in',
                'ratio' => true,
            ]);
        } catch (\Throwable) {
            $processor->setValue('anh_hoc_vien', '');
        }
    }

    private static function xoaThuMucTam(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function tenFile(string $maHocVien): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $maHocVien) ?: 'hv';

        return 'bao-cao-dat-'.$safe.'.docx';
    }
}