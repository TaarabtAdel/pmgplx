<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * DOCX từng trang (generateOne) → PDF → 1 file in nhiều trang.
 */
class BienBanTongPdfExporter
{
    /**
     * @param  list<string>  $docxPaths
     */
    public function build(array $docxPaths, string $outputPdfPath, string $workDir): void
    {
        if (! BienBanLibreOfficePdfConverter::isAvailable()) {
            throw new RuntimeException(
                'Chưa cài LibreOffice để in PDF. Cài LibreOffice hoặc đặt LIBREOFFICE_BIN trong .env '
                .'(ví dụ LIBREOFFICE_BIN="C:/Program Files/LibreOffice/program/soffice.exe").'
            );
        }

        $pdfDir = $workDir.DIRECTORY_SEPARATOR.'pdf-pages';
        if (is_dir($pdfDir)) {
            $this->wipeDir($pdfDir);
        }
        @mkdir($pdfDir, 0755, true);

        $converter = new BienBanLibreOfficePdfConverter();
        $pdfs = [];
        foreach ($docxPaths as $docx) {
            if (! is_string($docx) || ! is_file($docx)) {
                continue;
            }
            $pdfs[] = $converter->convert($docx, $pdfDir);
        }

        if (count($pdfs) < 1) {
            throw new RuntimeException('Không chuyển được file Word sang PDF.');
        }

        (new BienBanPdfMerger())->merge($pdfs, $outputPdfPath);

        if (! is_file($outputPdfPath)) {
            throw new RuntimeException('Không tạo được file PDF tổng.');
        }
    }

    private function wipeDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->wipeDir($path) : @unlink($path);
        }
    }
}
