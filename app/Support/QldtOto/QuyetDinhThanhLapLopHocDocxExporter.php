<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

class QuyetDinhThanhLapLopHocDocxExporter
{
    public const TEMPLATE = 'templates/oto/quyet-dinh-thanh-lap-lop-hoc.docx';

    /**
     * @param  array{
     *   so_qd_full: string,
     *   ngay_dong: string,
     *   hang_gplx: string,
     *   khoa_so: string,
     *   so_luong_hv: string,
     *   ngay_kg: string,
     *   ngay_bg: string,
     *   giao_vien_hd: string,
     * }  $data
     */
    public function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có file mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-thanh-lap-template'
            );
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $map = [
            'SO_QD_FULL' => $data['so_qd_full'],
            'NGAY_DONG' => $data['ngay_dong'],
            'HANG_GPLX' => $data['hang_gplx'],
            'KHOA_SO' => $data['khoa_so'],
            'SO_LUONG_HV' => $data['so_luong_hv'],
            'NGAY_KG' => $data['ngay_kg'],
            'NGAY_BG' => $data['ngay_bg'],
            'GIAO_VIEN_HD' => $data['giao_vien_hd'],
        ];
        foreach ($map as $key => $value) {
            $processor->setValue($key, htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định.');
        }

        return $outputPath;
    }
}
