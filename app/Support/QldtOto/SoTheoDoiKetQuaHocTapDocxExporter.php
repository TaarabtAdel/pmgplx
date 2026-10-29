<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

class SoTheoDoiKetQuaHocTapDocxExporter
{
    public const TEMPLATE = 'templates/oto/so-theo-doi-ket-qua-hoc-tap.docx';

    /**
     * @param  list<array{
     *   stt: int|string,
     *   ho_ten: string,
     *   khoa_hoc: string,
     *   ly_thuyet: string,
     *   thuc_hanh: string,
     *   ket_qua_khoa: string,
     *   ngay_hoan_thanh: string,
     *   xac_nhan: string,
     * }>  $rows
     */
    public function generate(string $tenKhoa, string $ngayLap, array $rows, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx placeholder. Chạy: php artisan qldtoto:build-so-theo-doi-kq-hoc-tap-template'
            );
        }

        if ($rows === []) {
            throw new \InvalidArgumentException('Không có học viên để xuất.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $processor->setValue('TEN_KHOA', $this->esc($tenKhoa));
        $processor->setValue('NGAY_LAP', $this->esc($ngayLap));

        $processor->cloneRow('stt', count($rows));

        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $processor->setValue('stt#'.$n, $this->esc((string) $row['stt']));
            $processor->setValue('ho_ten#'.$n, $this->esc($row['ho_ten']));
            $processor->setValue('khoa_hoc#'.$n, $this->esc($row['khoa_hoc']));
            $processor->setValue('ly_thuyet#'.$n, $this->esc($row['ly_thuyet']));
            $processor->setValue('thuc_hanh#'.$n, $this->esc($row['thuc_hanh']));
            $processor->setValue('ket_qua_khoa#'.$n, $this->esc($row['ket_qua_khoa']));
            $processor->setValue('ngay_hoan_thanh#'.$n, $this->esc($row['ngay_hoan_thanh']));
            $processor->setValue('xac_nhan#'.$n, $this->esc($row['xac_nhan']));
        }

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file sổ theo dõi kết quả học tập.');
        }

        return $outputPath;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
