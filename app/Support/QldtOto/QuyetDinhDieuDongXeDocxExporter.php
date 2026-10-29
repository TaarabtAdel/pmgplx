<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

class QuyetDinhDieuDongXeDocxExporter
{
    public const TEMPLATE = 'templates/oto/quyet-dinh-dieu-dong-xe-gvth.docx';

    /** @var list<string> */
    private const PAGE_KEYS = [
        'SO_QD',
        'NGAY_DONG',
        'HANG_KHOA',
        'BIEN_SO',
        'LOAI_XE',
        'NHAN_HIEU',
        'MAU_SON',
        'GP_XE_TEXT',
        'GV_NHAN_XE',
        'TU_NGAY',
        'DEN_NGAY',
    ];

    /**
     * @param  list<array<string, string>>  $pages
     */
    public function generate(array $pages, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException(
                'Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-dieu-dong-xe-template'
            );
        }

        if ($pages === []) {
            throw new \InvalidArgumentException('Không có trang quyết định để xuất.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $cloned = $processor->cloneBlock('XE_TRANG', count($pages), true, true);
        if ($cloned === null) {
            throw new \RuntimeException('File mẫu thiếu khối ${XE_TRANG} … ${/XE_TRANG}. Chạy lại lệnh build template.');
        }

        foreach ($pages as $index => $page) {
            $i = $index + 1;
            foreach (self::PAGE_KEYS as $key) {
                $processor->setValue($key.'#'.$i, $this->esc((string) ($page[$key] ?? '')));
            }
        }

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định điều động xe.');
        }

        $this->fixClonedIds($outputPath);

        return $outputPath;
    }

    /**
     * Mỗi trang clone giữ nguyên id hình vẽ (đường kẻ dưới tiêu đề) — Word báo lỗi file nếu id trùng.
     * Đồng thời bỏ ngắt trang sau trang cuối để không sinh trang trắng.
     */
    private function fixClonedIds(string $path): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Không mở được file vừa xuất để chuẩn hoá.');
        }
        $xml = (string) $zip->getFromName('word/document.xml');

        $docPrId = 0;
        $xml = preg_replace_callback(
            '/(<wp:docPr\b[^>]*?\bid=")\d+(")/',
            function (array $m) use (&$docPrId): string {
                $docPrId++;

                return $m[1].$docPrId.$m[2];
            },
            $xml
        ) ?? $xml;

        $shapeId = 0;
        $xml = preg_replace_callback(
            '/(<v:shape\b[^>]*?\bid=")[^"]*(")/',
            function (array $m) use (&$shapeId): string {
                $shapeId++;

                return $m[1].'qd_shape_'.$shapeId.$m[2];
            },
            $xml
        ) ?? $xml;

        $pageBreak = QuyetDinhDieuDongXeDocxTemplateBuilder::PARA_NGAT_TRANG;
        $last = strrpos($xml, $pageBreak);
        if ($last !== false) {
            $xml = substr_replace($xml, QuyetDinhDieuDongXeDocxTemplateBuilder::PARA_CUOI, $last, strlen($pageBreak));
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
