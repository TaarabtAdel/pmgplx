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
        $pdfs = $this->convertDocxBatch([$docxPath], $workDir);
        if ($pdfs === []) {
            throw new RuntimeException('Không tìm thấy file Word: '.$docxPath);
        }

        return $pdfs[0];
    }

    /**
     * @param  list<string>  $docxPaths
     * @return list<string>
     */
    public function convertDocxBatch(array $docxPaths, string $workDir): array
    {
        self::assertLibreOfficeAvailable();

        return (new BienBanLibreOfficePdfConverter())->convertMany(
            $docxPaths,
            $this->ensurePdfPagesDir($workDir)
        );
    }

    public static function pdfBatchSize(): int
    {
        return max(1, (int) config('services.bien_ban_tong.pdf_batch_size', 25));
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

        $converter = new BienBanLibreOfficePdfConverter();
        $batchSize = self::pdfBatchSize();
        $pdfs = [];
        $valid = array_values(array_filter($docxPaths, static fn ($p) => is_string($p) && is_file($p)));
        for ($i = 0; $i < count($valid); $i += $batchSize) {
            $chunk = array_slice($valid, $i, $batchSize);
            foreach ($converter->convertMany($chunk, $pdfDir) as $pdf) {
                $pdfs[] = $pdf;
            }
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
