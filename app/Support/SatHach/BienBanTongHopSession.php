<?php

namespace App\Support\SatHach;

use App\Models\DaoTao\SatHachBienBan;
use Illuminate\Support\Str;
use RuntimeException;

class BienBanTongHopSession
{
    /**
     * @param  list<SatHachBienBan>  $rows
     * @return array<string, mixed>
     */
    public static function start(string $maKySh, $rows): array
    {
        self::cleanupOld();

        $id = (string) Str::uuid();
        $dir = self::dir($id);
        if (! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục tạm.');
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->Id,
                'sbd' => (string) ($row->SoBaoDanh ?: ''),
                'ten' => (string) ($row->HoVaTen ?: ''),
            ];
        }

        $job = [
            'id' => $id,
            'ma_ky_sh' => $maKySh,
            'items' => $items,
            'done' => 0,
            'files' => [],
            'combined_docx' => $dir.DIRECTORY_SEPARATOR.'bien-ban-tong.docx',
            'created_at' => time(),
        ];
        self::save($job);

        return $job;
    }

    /**
     * @return array<string, mixed>
     */
    public static function load(string $id): array
    {
        $path = self::dir($id).DIRECTORY_SEPARATOR.'job.json';
        if (! is_file($path)) {
            throw new RuntimeException('Phiên xuất tổng không tồn tại hoặc đã hết hạn.');
        }

        $job = json_decode((string) file_get_contents($path), true);
        if (! is_array($job) || ($job['id'] ?? '') !== $id) {
            throw new RuntimeException('Phiên xuất tổng không hợp lệ.');
        }

        return $job;
    }

    /**
     * @param  array<string, mixed>  $job
     */
    public static function save(array $job): void
    {
        $path = self::dir((string) $job['id']).DIRECTORY_SEPARATOR.'job.json';
        file_put_contents($path, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    public static function dir(string $id): string
    {
        return storage_path('app/temp/bien-ban-tong'.DIRECTORY_SEPARATOR.$id);
    }

    public static function destroy(string $id): void
    {
        self::deleteDirectory(self::dir($id));
    }

    public static function cleanupOld(int $maxAgeSeconds = 86400): void
    {
        $root = storage_path('app/temp/bien-ban-tong');
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

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
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
