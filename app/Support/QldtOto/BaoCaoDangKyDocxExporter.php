<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

class BaoCaoDangKyDocxExporter
{
    public const TEMPLATE = 'templates/oto/bao-cao-dang-ky.docx';

    /**
     * @param  array{
     *   so_cv_full: string,
     *   ngay_dong: string,
     *   hang_dt: string,
     *   khoa_so: string,
     *   khoa_lop: string,
     *   hang_gplx: string,
     *   so_luong_hv: string,
     *   ngay_kg: string,
     *   ngay_bg: string,
     *   ghi_chu: string,
     * }  $data
     */
    public function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có file mẫu docx. Chạy: php artisan qldtoto:build-bao-cao-dang-ky-template'
            );
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $map = [
            'SO_CV_FULL' => $data['so_cv_full'],
            'NGAY_DONG' => $data['ngay_dong'],
            'HANG_DT' => $data['hang_dt'],
            'KHOA_SO' => $data['khoa_so'],
            'KHOA_LOP' => $data['khoa_lop'],
            'HANG_GPLX' => $data['hang_gplx'],
            'SO_LUONG_HV' => $data['so_luong_hv'],
            'NGAY_KG' => $data['ngay_kg'],
            'NGAY_BG' => $data['ngay_bg'],
            'GHI_CHU' => $data['ghi_chu'],
        ];
        foreach ($map as $key => $value) {
            $processor->setValue($key, $this->escape($value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file báo cáo đăng ký.');
        }

        return $outputPath;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
