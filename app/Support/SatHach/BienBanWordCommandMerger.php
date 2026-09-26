<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * Gộp DOCX bằng Microsoft Word trên Windows (PowerShell COM InsertFile).
 * Mac/Docker không gọi Word — dùng gộp XML.
 */
class BienBanWordCommandMerger
{
    public function isAvailable(): bool
    {
        return $this->driver() !== null;
    }

    /**
     * @param  list<string>  $pageDocxPaths
     */
    public function merge(array $pageDocxPaths, string $outputPath): void
    {
        $driver = $this->driver();
        if ($driver === null) {
            throw new RuntimeException('Máy này không có Microsoft Word để gộp file.');
        }

        @mkdir(dirname($outputPath), 0755, true);
        @unlink($outputPath);

        if ($driver !== 'windows') {
            throw new RuntimeException('Máy này không có Microsoft Word để gộp file.');
        }

        $this->mergeWindows($pageDocxPaths, $outputPath);
    }

    private function driver(): ?string
    {
        if (filter_var(config('services.word_merge.disable', false), FILTER_VALIDATE_BOOL)) {
            return null;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->powershellPath() !== null ? 'windows' : null;
        }

        return null;
    }

    /**
     * @param  list<string>  $files
     */
    private function mergeWindows(array $files, string $outputPath): void
    {
        $workDir = dirname($outputPath);
        $ps1 = $this->materializeMergeScript($workDir);

        $resolved = [];
        foreach ($files as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $absolute = realpath($path) ?: $path;
            if (! is_file($absolute)) {
                throw new RuntimeException('Không tìm thấy file Word: '.$path);
            }
            $resolved[] = str_replace('/', '\\', $absolute);
        }

        if (count($resolved) < 2) {
            throw new RuntimeException(
                'Chỉ có '.count($resolved).' file Word để gộp (cần ít nhất 2).'
            );
        }

        $list = dirname($outputPath).DIRECTORY_SEPARATOR.'word-merge-files.txt';
        $listBody = implode("\r\n", $resolved)."\r\n";
        if (file_put_contents($list, $listBody) === false) {
            throw new RuntimeException('Không ghi được danh sách file Word.');
        }

        $outDir = realpath(dirname($outputPath));
        if ($outDir === false) {
            throw new RuntimeException('Không tạo được thư mục file Word tổng.');
        }
        $outForWord = str_replace('/', '\\', $outDir.DIRECTORY_SEPARATOR.basename($outputPath));

        $cmd = escapeshellarg((string) $this->powershellPath())
            .' -NoProfile -ExecutionPolicy Bypass -File '.escapeshellarg($ps1)
            .' '.escapeshellarg($list)
            .' '.escapeshellarg($outForWord);

        $output = [];
        $code = 0;
        exec($cmd.' 2>&1', $output, $code);

        if ($code !== 0 || ! is_file($outputPath)) {
            $detail = self::formatPsError($output);
            @unlink($list);
            @unlink($ps1);
            throw new RuntimeException(
                'Gộp bằng Microsoft Word thất bại'.($detail !== '' ? ': '.$detail : '.')
            );
        }

        @unlink($list);
        @unlink($ps1);
    }

    private function materializeMergeScript(string $workDir): string
    {
        $body = self::mergeScriptBody();
        $path = $workDir.DIRECTORY_SEPARATOR.'merge-docx-run.ps1';
        if (file_put_contents($path, $body) === false) {
            throw new RuntimeException('Không tạo được script gộp Word tạm.');
        }

        return $path;
    }

    /**
     * ASCII-only PowerShell (Windows 5.1 parses .ps1 as ANSI unless UTF-8 BOM).
     */
    private static function mergeScriptBody(): string
    {
        $disk = base_path('bin/merge-docx.ps1');
        if (is_file($disk)) {
            $fromDisk = (string) file_get_contents($disk);
            if ($fromDisk !== '' && self::isSafeMergeScript($fromDisk)) {
                return $fromDisk;
            }
        }

        return <<<'PS1'
# khgplx merge-docx v2 ASCII
$ErrorActionPreference = 'Stop'
if ($args.Count -lt 2) { throw 'Usage: merge-docx.ps1 <files.txt> <output.docx>' }
$listPath = [string]$args[0]
$outPath = [string]$args[1]
if (-not (Test-Path -LiteralPath $listPath)) { throw ('List file not found: ' + $listPath) }
$raw = [System.IO.File]::ReadAllText($listPath)
if ($raw.Length -ge 1 -and [int][char]$raw[0] -eq 0xFEFF) { $raw = $raw.Substring(1) }
$candidates = @($raw -split "`r?`n" | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
$files = @()
foreach ($candidate in $candidates) {
    if (-not (Test-Path -LiteralPath $candidate)) { throw ('DOCX not found: ' + $candidate) }
    $files += (Resolve-Path -LiteralPath $candidate).Path
}
if ($files.Count -lt 1) {
    $bytes = ([System.IO.FileInfo]$listPath).Length
    throw ('No DOCX in list (' + $bytes + ' bytes): ' + $listPath)
}
$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open([string]$files[0])
    for ($i = 1; $i -lt $files.Count; $i++) {
        $rng = $doc.Content
        $rng.Collapse(0) | Out-Null
        $rng.InsertBreak(7) | Out-Null
        $rng = $doc.Content
        $rng.Collapse(0) | Out-Null
        $rng.InsertFile([string]$files[$i])
    }
    $outDir = Split-Path -LiteralPath $outPath -Parent
    if ($outDir -and -not (Test-Path -LiteralPath $outDir)) {
        New-Item -ItemType Directory -Path $outDir -Force | Out-Null
    }
    if (Test-Path -LiteralPath $outPath) { Remove-Item -LiteralPath $outPath -Force }
    $doc.SaveAs2([string]$outPath, 16)
    $doc.Close($false) | Out-Null
    $doc = $null
} finally {
    if ($null -ne $doc) { try { $doc.Close($false) | Out-Null } catch {} }
    if ($null -ne $word) {
        try { $word.Quit() | Out-Null } catch {}
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null } catch {}
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
if (-not (Test-Path -LiteralPath $outPath)) { throw 'Word did not write the output DOCX.' }
PS1;
    }

    private static function isSafeMergeScript(string $body): bool
    {
        return mb_check_encoding($body, 'ASCII') && str_contains($body, 'merge-docx v2');
    }

    /**
     * @param  list<string>  $output
     */
    private static function formatPsError(array $output): string
    {
        $text = Utf8::sanitize(implode("\n", $output));
        if ($text === '') {
            return '';
        }

        return match (true) {
            str_contains($text, 'ParserError') || str_contains($text, 'Unexpected token') => 'Script gộp Word lỗi encoding trên server — cập nhật file app PHP mới nhất.',
            str_contains($text, 'List file not found') => 'Không tìm thấy file danh sách gộp.',
            str_contains($text, 'DOCX not found') => 'Thiếu file Word trang (đường dẫn không tồn tại trên server).',
            str_contains($text, 'No DOCX in list') => 'Danh sách gộp Word rỗng.',
            str_contains($text, 'Word did not write') => 'Word không lưu được file tổng.',
            str_contains($text, '80040154') || str_contains($text, 'Retrieving the COM class factory') => 'Chưa cài Microsoft Word hoặc COM Word bị chặn trên server.',
            default => preg_replace('/\s+/', ' ', $text) ?? $text,
        };
    }

    private function powershellPath(): ?string
    {
        $configured = trim((string) config('services.word_merge.powershell', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        foreach (['powershell.exe', 'pwsh.exe'] as $bin) {
            $out = [];
            $code = 0;
            exec('where '.$bin.' 2>nul', $out, $code);
            if ($code === 0 && isset($out[0]) && trim($out[0]) !== '') {
                return trim($out[0]);
            }
        }

        $systemRoot = getenv('SystemRoot') ?: 'C:\\Windows';
        $fallback = $systemRoot.'\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';

        return is_file($fallback) ? $fallback : null;
    }
}
