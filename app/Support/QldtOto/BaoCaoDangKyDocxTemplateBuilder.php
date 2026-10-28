<?php

namespace App\Support\QldtOto;

/**
 * Một lần: chuyển bao-cao-dang-ky.doc → docx có placeholder PhpWord (${...}).
 * Chạy artisan qldtoto:build-bao-cao-dang-ky-template khi cập nhật file .doc gốc.
 */
class BaoCaoDangKyDocxTemplateBuilder
{
    public const DOC = 'templates/oto/bao-cao-dang-ky.doc';

    public const DOCX = 'templates/oto/bao-cao-dang-ky.docx';

    public function build(?string $docPath = null, ?string $docxPath = null): string
    {
        $docPath ??= resource_path(self::DOC);
        $docxPath ??= resource_path(self::DOCX);

        if (! is_file($docPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu .doc: '.$docPath);
        }

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bao-cao-dang-ky-'.uniqid('', true);
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
                'LibreOffice không chuyển được .doc sang .docx. Cần cài soffice trong môi trường chạy.'
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
            '637/TTML-2026' => '${SO_CV_FULL}',
            'hạng B khóa 56' => 'hạng ${HANG_DT} khóa ${KHOA_SO}',
            '44007K261010' => '${KHOA_LOP}',
            'B (số sàn)' => '${HANG_GPLX}',
            '>100<' => '>${SO_LUONG_HV}<',
            '17/09/2026' => '${NGAY_KG}',
            '21/11/2026' => '${NGAY_BG}',
            'Đào tạo mới' => '${GHI_CHU}',
            'hạng B (số sàn) khóa 56' => 'hạng ${HANG_GPLX} khóa ${KHOA_SO}',
            'khóa 56 như lịch' => 'khóa ${KHOA_SO} như lịch',
        ];
        foreach ($replacements as $from => $to) {
            $xml = str_replace($from, $to, $xml);
        }

        $needle = 'Quảng Trị, ngày 16 tháng 09 năm ';
        if (! str_contains($xml, $needle)) {
            throw new \RuntimeException('Không tìm thấy dòng ngày tháng trong mẫu — cần cập nhật builder.');
        }
        $xml = str_replace($needle, 'Quảng Trị, ngày ${NGAY_DONG}', $xml);
        $xml = $this->catRunsSauNgayDong($xml);

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

    /** Xoá các run XML thừa (MERGEFIELD năm) sau placeholder ngày. */
    private function catRunsSauNgayDong(string $xml): string
    {
        $marker = 'ngày ${NGAY_DONG}';
        $pos = strpos($xml, $marker);
        if ($pos === false) {
            return $xml;
        }

        $runEnd = '</w:t></w:r>';
        $posRunEnd = strpos($xml, $runEnd, $pos);
        if ($posRunEnd === false) {
            return $xml;
        }
        $posRunEnd += strlen($runEnd);

        $posParaEnd = strpos($xml, '</w:p>', $posRunEnd);
        if ($posParaEnd === false) {
            return $xml;
        }

        return substr($xml, 0, $posRunEnd).substr($xml, $posParaEnd);
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
