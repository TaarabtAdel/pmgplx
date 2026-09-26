<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * Gộp DOCX bằng Microsoft Word trên máy chạy PHP (không phải Docker Linux).
 *
 * Windows triển khai: PowerShell + Word.Application InsertFile.
 * macOS native: AppleScript + Microsoft Word.app.
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

        if ($driver === 'windows') {
            $this->mergeWindows($pageDocxPaths, $outputPath);

            return;
        }

        $this->mergeMac($pageDocxPaths, $outputPath);
    }

    private function driver(): ?string
    {
        if (filter_var(config('services.word_merge.disable', false), FILTER_VALIDATE_BOOL)) {
            return null;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->powershellPath() !== null ? 'windows' : null;
        }

        if (PHP_OS_FAMILY === 'Darwin' && is_dir('/Applications/Microsoft Word.app')) {
            return 'mac';
        }

        return null;
    }

    /**
     * @param  list<string>  $files
     */
    private function mergeWindows(array $files, string $outputPath): void
    {
        $ps1 = base_path('bin/merge-docx.ps1');
        if (! is_file($ps1)) {
            throw new RuntimeException('Thiếu script gộp Word: '.$ps1);
        }

        $list = dirname($outputPath).DIRECTORY_SEPARATOR.'word-merge-files.txt';
        file_put_contents($list, implode("\r\n", $files)."\r\n");

        $cmd = escapeshellarg((string) $this->powershellPath())
            .' -NoProfile -ExecutionPolicy Bypass -File '.escapeshellarg($ps1)
            .' '.escapeshellarg($list)
            .' '.escapeshellarg($outputPath);

        $output = [];
        $code = 0;
        exec($cmd.' 2>&1', $output, $code);
        @unlink($list);

        if ($code !== 0 || ! is_file($outputPath)) {
            throw new RuntimeException(
                'Gộp bằng Microsoft Word thất bại'
                .($output !== [] ? ': '.implode(' ', $output) : '.')
            );
        }
    }

    /**
     * @param  list<string>  $files
     */
    private function mergeMac(array $files, string $outputPath): void
    {
        $quoted = array_map(fn (string $p): string => $this->appleQuoted($p), $files);
        $fileList = '{'.implode(', ', $quoted).'}';
        $outQuoted = $this->appleQuoted($outputPath);

        $script = <<<APPLESCRIPT
set fileList to {$fileList}
set outPath to {$outQuoted}
tell application "Microsoft Word"
  open POSIX file (item 1 of fileList)
  set theDoc to active document
  set fileCount to count of fileList
  repeat with i from 2 to fileCount
    tell theDoc
      set theRange to text object of theDoc
      collapse range theRange direction collapse end
      insert break at theRange break type page break
    end tell
    insert file name ((POSIX file (item i of fileList)) as string)
  end repeat
  save as theDoc file name ((POSIX file outPath) as string) file format format XML document
  close theDoc saving no
end tell
APPLESCRIPT;

        $tmp = dirname($outputPath).DIRECTORY_SEPARATOR.'word-merge.applescript';
        file_put_contents($tmp, $script);

        $output = [];
        $code = 0;
        exec('osascript '.escapeshellarg($tmp).' 2>&1', $output, $code);
        @unlink($tmp);

        if ($code !== 0 || ! is_file($outputPath)) {
            throw new RuntimeException(
                'Gộp bằng Microsoft Word (Mac) thất bại'
                .($output !== [] ? ': '.implode(' ', $output) : '.')
            );
        }
    }

    private function appleQuoted(string $path): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $path).'"';
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
