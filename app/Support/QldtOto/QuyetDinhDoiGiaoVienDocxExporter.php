<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

class QuyetDinhDoiGiaoVienDocxExporter
{
    public const TEMPLATE = 'templates/oto/quyet-dinh-doi-giao-vien.docx';

    /**
     * @param  array{so_qd: string, ngay_quyet_dinh: string, khoa_list: list<array{ten_ngan: string, rows: list<array<string, string>>}>}  $preview
     */
    public function generate(array $preview, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-doi-giao-vien-template'
            );
        }

        $khoaList = array_values($preview['khoa_list'] ?? []);
        if ($khoaList === []) {
            throw new \InvalidArgumentException('Chưa có khoá nào để xuất.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $processor->setValue('SO_QD', $this->esc($preview['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD));
        $processor->setValue('NGAY_DONG', $this->esc(
            BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($preview['ngay_quyet_dinh']))
        ));
        $processor->setValue('DS_KHOA', $this->esc(implode(', ', array_column($khoaList, 'ten_ngan'))));

        if ($processor->cloneBlock('KHOA', count($khoaList), true, true) === null) {
            throw new \RuntimeException('Mẫu thiếu khối ${KHOA} … ${/KHOA}. Chạy lại lệnh build template.');
        }

        foreach ($khoaList as $k => $khoa) {
            $ki = $k + 1;
            $processor->setValue('TEN_KHOA#'.$ki, $this->esc($khoa['ten_ngan']));
            $rows = array_values($khoa['rows']);
            $processor->cloneBlock('DONG#'.$ki, count($rows), true, true);
            foreach ($rows as $r => $row) {
                $processor->setValue(
                    'DONG_TEXT#'.$ki.'#'.($r + 1),
                    $this->esc(QuyetDinhDoiGiaoVienSupport::cauDieuChinh($khoa['ten_ngan'], $row))
                );
            }
        }

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định đổi giáo viên.');
        }

        return $outputPath;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
