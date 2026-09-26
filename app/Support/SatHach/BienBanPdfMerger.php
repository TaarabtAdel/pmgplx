<?php

namespace App\Support\SatHach;

use App\Support\EnvPath;
use App\Support\ShellProcess;
use RuntimeException;

class BienBanPdfMerger
{
    /**
     * @param  list<string>  $pdfPaths
     */
    public function merge(array $pdfPaths, string $outputPath): void
    {
        $files = [];
        foreach ($pdfPaths as $path) {
            if (is_string($path) && is_file($path)) {
                $files[] = $path;
            }
        }

        if ($files === []) {
            throw new RuntimeException('Không có file PDF để gộp.');
        }

        @mkdir(dirname($outputPath), 0755, true);
        @unlink($outputPath);

        if (count($files) === 1) {
            if (! @copy($files[0], $outputPath)) {
                throw new RuntimeException('Không ghi được file PDF tổng.');
            }

            return;
        }

        if ($this->mergeWithQpdf($files, $outputPath)) {
            return;
        }

        if ($this->mergeWithGhostscript($files, $outputPath)) {
            return;
        }

        throw new RuntimeException(
            'Không gộp được PDF (cần qpdf.exe trong laravel/bin hoặc QPDF_BIN trong .env).'
        );
    }

    /**
     * @param  list<string>  $files
     */
    private function mergeWithQpdf(array $files, string $outputPath): bool
    {
        $qpdf = $this->resolveQpdf();
        if ($qpdf === null) {
            return false;
        }

        $chunks = array_chunk($files, 20);
        $partials = [];
        foreach ($chunks as $i => $chunk) {
            $part = dirname($outputPath).DIRECTORY_SEPARATOR.'pdf-part-'.$i.'.pdf';
            if (! $this->qpdfConcat($qpdf, $chunk, $part)) {
                foreach ($partials as $p) {
                    @unlink($p);
                }

                return false;
            }
            $partials[] = $part;
        }

        if (count($partials) === 1) {
            @rename($partials[0], $outputPath);

            return is_file($outputPath);
        }

        $ok = $this->qpdfConcat($qpdf, $partials, $outputPath);
        foreach ($partials as $p) {
            @unlink($p);
        }

        return $ok && is_file($outputPath);
    }

    /**
     * @param  list<string>  $files
     */
    private function qpdfConcat(string $qpdf, array $files, string $outputPath): bool
    {
        @unlink($outputPath);
        $command = array_merge(
            [$qpdf, '--empty', '--pages'],
            $this->qpdfPageArgs($files),
            ['--', $outputPath]
        );
        $result = ShellProcess::run($command, dirname($qpdf));

        return $result['code'] === 0 && is_file($outputPath);
    }

    /**
     * @param  list<string>  $files
     */
    private function mergeWithGhostscript(array $files, string $outputPath): bool
    {
        $gs = $this->resolveGhostscript();
        if ($gs === null) {
            return false;
        }

        $args = escapeshellarg($gs)
            .' -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress'
            .' -sOutputFile='.escapeshellarg($outputPath);
        foreach ($files as $file) {
            $args .= ' '.escapeshellarg($file);
        }

        $out = [];
        $code = 0;
        exec($args.' 2>&1', $out, $code);

        return $code === 0 && is_file($outputPath);
    }

    private function resolveQpdf(): ?string
    {
        $configured = EnvPath::fromEnv(config('services.pdf_tools.qpdf'));
        if ($configured !== null && is_file($configured)) {
            return $configured;
        }

        $null = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where qpdf' : 'command -v qpdf';
        $out = [];
        $code = 1;
        exec($cmd.' '.$null, $out, $code);
        if ($code === 0 && isset($out[0]) && trim($out[0]) !== '') {
            return trim($out[0]);
        }

        $local = base_path('bin/qpdf'.(PHP_OS_FAMILY === 'Windows' ? '.exe' : ''));

        return is_file($local) ? $local : null;
    }

    /**
     * @param  list<string>  $files
     * @return list<string>
     */
    private function qpdfPageArgs(array $files): array
    {
        $args = [];
        foreach ($files as $file) {
            $args[] = $file;
            $args[] = '1-z';
        }

        return $args;
    }

    private function resolveGhostscript(): ?string
    {
        $configured = EnvPath::fromEnv(config('services.pdf_tools.ghostscript'));
        if ($configured !== null && is_file($configured)) {
            return $configured;
        }

        foreach (PHP_OS_FAMILY === 'Windows'
            ? ['gswin64c.exe', 'gswin32c.exe']
            : ['gs', 'ghostscript'] as $name) {
            $null = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
            $cmd = PHP_OS_FAMILY === 'Windows' ? 'where '.$name : 'command -v '.$name;
            $out = [];
            $code = 1;
            exec($cmd.' '.$null, $out, $code);
            if ($code === 0 && isset($out[0]) && trim($out[0]) !== '') {
                return trim($out[0]);
            }
        }

        return null;
    }
}
