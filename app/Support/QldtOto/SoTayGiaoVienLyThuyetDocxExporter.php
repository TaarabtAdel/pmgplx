<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

final class SoTayGiaoVienLyThuyetDocxExporter
{
    public const TEMPLATE = 'templates/oto/so-tay-giao-vien-ly-thuyet.docx';

    /**
     * @param  list<array{stt: int|string, ho_ten: string}>  $hocVien
     */
    public function generate(array $fields, array $hocVien, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx placeholder. Chạy: php artisan qldtoto:build-so-tay-gv-ly-thuyet-template'
            );
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        foreach ([
            'TIEU_DE_MON', 'HANG_GPLX', 'TEN_GV', 'KHOA',
            'QD_THANH_LAP', 'SI_SO', 'GIAO_VIEN_LOP', 'PHUONG_THUC',
        ] as $key) {
            $processor->setValue($key, $this->esc((string) ($fields[$key] ?? '')));
        }

        $esc = fn (string $v): string => $this->esc($v);
        SoTayGiaoVienBangDocx::dienBang($processor, $hocVien, true, $esc);

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file sổ tay giáo viên lý thuyết.');
        }

        return $outputPath;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
