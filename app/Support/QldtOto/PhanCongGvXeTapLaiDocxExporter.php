<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

class PhanCongGvXeTapLaiDocxExporter
{
    public const TEMPLATE = 'templates/oto/phan-cong-giao-vien-xe-tap-lai.docx';

    /**
     * @param  list<array{
     *   stt: int|string,
     *   giao_vien: string,
     *   thoi_gian: string,
     *   khoa_dt: string,
     *   bien_so: string,
     *   noi_dung: string,
     * }>  $rows
     */
    public function generate(string $tenKhoa, int $nam, array $rows, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx placeholder. Chạy: php artisan qldtoto:build-so-phan-cong-gv-xe-template'
            );
        }

        if ($rows === []) {
            throw new \InvalidArgumentException('Không có dòng phân công để xuất.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $processor->setValue('TEN_KHOA', $this->esc($tenKhoa));
        $processor->setValue('NAM', (string) $nam);

        $processor->cloneRow('stt', count($rows));

        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $processor->setValue('stt#'.$n, $this->esc((string) $row['stt']));
            $processor->setValue('giao_vien#'.$n, $this->esc($row['giao_vien']));
            $processor->setValue('thoi_gian#'.$n, $this->esc($row['thoi_gian']));
            $processor->setValue('khoa_dt#'.$n, $this->esc($row['khoa_dt']));
            $processor->setValue('bien_so#'.$n, $this->esc($row['bien_so']));
            $processor->setValue('noi_dung#'.$n, $this->esc($row['noi_dung']));
        }

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file sổ phân công.');
        }

        return $outputPath;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
