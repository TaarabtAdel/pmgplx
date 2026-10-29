<?php

namespace App\Support\QldtOto;

class QuyetDinhThanhLapLopHocDocxTemplateBuilder
{
    public const DOC = 'templates/oto/quyet-dinh-thanh-lap-lop-hoc.doc';

    public const DOCX = 'templates/oto/quyet-dinh-thanh-lap-lop-hoc.docx';

    public function build(?string $docPath = null, ?string $docxPath = null): string
    {
        $docPath ??= resource_path(self::DOC);
        $docxPath ??= resource_path(self::DOCX);

        if (! is_file($docPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu .doc: '.$docPath);
        }

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'qd-thanh-lap-'.uniqid('', true);
        if (! @mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException('Không tạo được thư mục tạm.');
        }

        $tmpDocx = $tmpDir.DIRECTORY_SEPARATOR.'out.docx';
        $this->convertDocToDocx($docPath, $tmpDocx);

        $zip = new \ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            throw new \RuntimeException('Không mở được file docx tạm.');
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $xml = $this->applyPlaceholders($xml);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        if (! @mkdir(dirname($docxPath), 0755, true) && ! is_dir(dirname($docxPath))) {
            throw new \RuntimeException('Không tạo được thư mục mẫu docx.');
        }
        if (! copy($tmpDocx, $docxPath)) {
            throw new \RuntimeException('Không ghi được: '.$docxPath);
        }

        $this->deleteDirectory($tmpDir);

        return $docxPath;
    }

    private function convertDocToDocx(string $docPath, string $docxPath): void
    {
        $outDir = dirname($docxPath);
        $cmd = sprintf(
            'soffice --headless --convert-to docx --outdir %s %s 2>&1',
            escapeshellarg($outDir),
            escapeshellarg($docPath)
        );
        exec($cmd, $output, $code);
        $base = pathinfo($docPath, PATHINFO_FILENAME);
        $generated = $outDir.DIRECTORY_SEPARATOR.$base.'.docx';
        if ($code !== 0 || ! is_file($generated)) {
            throw new \RuntimeException(
                'LibreOffice không chuyển được .doc sang .docx.'
                ."\n".implode("\n", $output)
            );
        }
        if ($generated !== $docxPath && ! rename($generated, $docxPath)) {
            copy($generated, $docxPath);
        }
    }

    private function applyPlaceholders(string $xml): string
    {
        $xml = preg_replace('/<w:instrText[^>]*>[^<]*<\/w:instrText>/', '', $xml) ?? $xml;
        $xml = preg_replace('~<w:fldChar\b[^>]*(?:/>|>.*?</w:fldChar>)~s', '', $xml) ?? $xml;

        $replacements = [
            '638/QĐ-TTML' => '${SO_QD_FULL}',
            'Quảng Trị, ngày 16 tháng 09 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'Hạng B (Số sàn) Khóa 56' => 'Hạng ${HANG_GPLX} Khóa ${KHOA_SO}',
            'Lớp Lái xe B (Số sàn): 100 học viên' => 'Lớp Lái xe ${HANG_GPLX}: ${SO_LUONG_HV} học viên',
            '17/09/2026' => '${NGAY_KG}',
            '21/11/2026' => '${NGAY_BG}',
            'Cô Hoàng Ngọc Hà' => '${GIAO_VIEN_HD}',
        ];
        foreach ($replacements as $from => $to) {
            $xml = str_replace($from, $to, $xml);
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            $msg = 'XML mẫu không hợp lệ sau khi gắn placeholder.';
            foreach (libxml_get_errors() as $err) {
                $msg .= ' '.trim($err->message);
            }
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            throw new \RuntimeException($msg);
        }
        libxml_use_internal_errors($prev);

        return $xml;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$entry;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
