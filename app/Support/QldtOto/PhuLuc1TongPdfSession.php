<?php

namespace App\Support\QldtOto;

use Illuminate\Support\Str;
use RuntimeException;

/** Phiên xuất PDF tổng Phụ lục 1 DAT (từng Word → PDF → gộp). */
final class PhuLuc1TongPdfSession
{
    /**
     * @param  list<array{ma: string, ten: string}>  $items
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function start(string $maKhoaHoc, string $ngayBaoCao, array $filters, array $items): array
    {
        self::cleanupOld();

        $id = (string) Str::uuid();
        $dir = self::dir($id);
        if (! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục tạm.');
        }
        @mkdir($dir.DIRECTORY_SEPARATOR.'pages', 0755, true);

        $job = [
            'id' => $id,
            'ma_khoa_hoc' => $maKhoaHoc,
            'ngay_bao_cao' => $ngayBaoCao,
            'filters' => $filters,
            'items' => array_values($items),
            'done' => 0,
            'files' => [],
            'combined_pdf' => $dir.DIRECTORY_SEPARATOR.'phu-luc-1-tong.pdf',
            'created_at' => time(),
        ];
        self::save($job);

        return $job;
    }

    /** @return array<string, mixed> */
    public static function load(string $id): array
    {
        $path = self::dir($id).DIRECTORY_SEPARATOR.'job.json';
        if (! is_file($path)) {
            throw new RuntimeException('Phiên xuất PDF không tồn tại hoặc đã hết hạn.');
        }

        $job = json_decode((string) file_get_contents($path), true);
        if (! is_array($job) || ($job['id'] ?? '') !== $id) {
            throw new RuntimeException('Phiên xuất PDF không hợp lệ.');
        }

        return $job;
    }

    /** @param  array<string, mixed>  $job */
    public static function save(array $job): void
    {
        $path = self::dir((string) $job['id']).DIRECTORY_SEPARATOR.'job.json';
        $json = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        if (! is_string($json) || $json === '') {
            throw new RuntimeException('Không ghi được phiên xuất PDF.');
        }
        file_put_contents($path, $json, LOCK_EX);
    }

    public static function dir(string $id): string
    {
        return storage_path('app/temp/phu-luc-1-pdf'.DIRECTORY_SEPARATOR.$id);
    }

    public static function pagesDir(string $dir): string
    {
        return $dir.DIRECTORY_SEPARATOR.'pages';
    }

    public static function destroy(string $id): void
    {
        self::deleteDirectory(self::dir($id));
    }

    public static function cleanupOld(int $maxAgeSeconds = 86400): void
    {
        $root = storage_path('app/temp/phu-luc-1-pdf');
        if (! is_dir($root)) {
            return;
        }

        $now = time();
        foreach (scandir($root) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $root.DIRECTORY_SEPARATOR.$name;
            if (! is_dir($path)) {
                continue;
            }
            $mtime = (int) filemtime($path);
            if ($mtime > 0 && ($now - $mtime) > $maxAgeSeconds) {
                self::deleteDirectory($path);
            }
        }
    }

    private static function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
