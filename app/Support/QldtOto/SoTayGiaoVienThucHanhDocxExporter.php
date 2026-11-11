<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

final class SoTayGiaoVienThucHanhDocxExporter
{
    public const TEMPLATE = 'templates/oto/so-tay-giao-vien-thuc-hanh.docx';

    /**
     * @param  list<array{stt: int|string, ho_ten: string}>  $hocVien
     */
    public function generate(array $fields, array $hocVien, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx placeholder. Chạy: php artisan qldtoto:build-so-tay-gv-thuc-hanh-template'
            );
        }

        if ($hocVien === []) {
            throw new \InvalidArgumentException('Khoá không có học viên để in bảng nghỉ học.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        foreach ([
            'MON', 'LOP', 'HANG_GPLX', 'TEN_GV', 'KHOA',
            'QD_THANH_LAP', 'QD_THANH_LAP_2', 'SI_SO', 'GIAO_VIEN_LOP', 'PHUONG_THUC',
        ] as $key) {
            $processor->setValue($key, $this->esc((string) ($fields[$key] ?? '')));
        }

        $processor->cloneRow('stt', count($hocVien));
        foreach ($hocVien as $i => $row) {
            $n = $i + 1;
            $processor->setValue('stt#'.$n, $this->esc((string) $row['stt']));
            $processor->setValue('ho_ten#'.$n, $this->esc($row['ho_ten']));
        }

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file sổ tay giáo viên.');
        }

        return $outputPath;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
