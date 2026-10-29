<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

class BaoCaoDeNghiSatHachDocxExporter
{
    public const TEMPLATE = 'templates/oto/bao-cao-de-nghi-to-chuc-sat-hach.docx';

    /**
     * @param  array<string, mixed>  $form
     */
    public function generate(array $form, int $soDuSatHach, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx. Chạy: php artisan qldtoto:build-bao-cao-de-nghi-sat-hach-template'
            );
        }

        @mkdir(dirname($outputPath), 0755, true);

        $ngay = Carbon::parse((string) $form['ngay_bao_cao']);
        $values = [
            'SO_BC' => $form['so_bc'].BaoCaoDeNghiSatHachData::HAU_TO_SO_BC.$ngay->format('Y'),
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay),
            'KINH_GUI' => (string) $form['kinh_gui'],
            'HANG' => (string) $form['hang'],
            'KHOA_LOP' => (string) $form['khoa_lop'],
            'SO_VAO_HOC' => (string) $form['so_vao_hoc'],
            'SO_DU_SH' => (string) $soDuSatHach,
            'NGAY_SH' => BaoCaoDeNghiSatHachData::ngay((string) ($form['ngay_sh'] ?? '')),
            'GHI_CHU' => (string) ($form['ghi_chu'] ?? ''),
        ];

        $processor = new TemplateProcessor($template);
        foreach ($values as $key => $value) {
            $processor->setValue($key, htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file báo cáo.');
        }

        return $outputPath;
    }
}
