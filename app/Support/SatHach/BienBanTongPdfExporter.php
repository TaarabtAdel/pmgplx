<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * DOCX từng trang (generateOne) → PDF → 1 file in nhiều trang.
 */
class BienBanTongPdfExporter
{
    public static function assertLibreOfficeAvailable(): void
    {
        if (! BienBanLibreOfficePdfConverter::isAvailable()) {
            throw new RuntimeException(
                'Chưa cài LibreOffice để in PDF. Cài LibreOffice hoặc đặt LIBREOFFICE_BIN trong .env '
                .'(ví dụ LIBREOFFICE_BIN="C:/Program Files/LibreOffice/program/soffice.exe").'
            );
        }
    }

    public function ensurePdfPagesDir(string $workDir): string
    {
        $pdfDir = $workDir.DIRECTORY_SEPARATOR.'pdf-pages';
        @mkdir($pdfDir, 0755, true);

        return $pdfDir;
    }

    public function convertOneDocx(string $docxPath, string $workDir): string
    {
        self::assertLibreOfficeAvailable();
        if (! is_file($docxPath)) {
            throw new RuntimeException('Không tìm thấy file Word: '.$docxPath);
        }

        return (new BienBanLibreOfficePdfConverter())->convert($docxPath, $this->ensurePdfPagesDir($workDir));
    }

    /**
     * @param  list<string>  $pdfPaths
     */
    public function mergePdfs(array $pdfPaths, string $outputPdfPath): void
    {
        (new BienBanPdfMerger())->merge($pdfPaths, $outputPdfPath);
        if (! is_file($outputPdfPath)) {
            throw new RuntimeException('Không tạo được file PDF tổng.');
        }
    }

    /**
     * @param  list<string>  $docxPaths
     */
    public function build(array $docxPaths, string $outputPdfPath, string $workDir): void
    {
        self::assertLibreOfficeAvailable();
        $pdfDir = $this->ensurePdfPagesDir($workDir);
        if (is_dir($pdfDir)) {
            $this->wipeDir($pdfDir);
        }
        @mkdir($pdfDir, 0755, true);

        $pdfs = [];
        foreach ($docxPaths as $docx) {
            if (! is_string($docx) || ! is_file($docx)) {
                continue;
            }
            $pdfs[] = (new BienBanLibreOfficePdfConverter())->convert($docx, $pdfDir);
        }

        if (count($pdfs) < 1) {
            throw new RuntimeException('Không chuyển được file Word sang PDF.');
        }

        $this->mergePdfs($pdfs, $outputPdfPath);
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
