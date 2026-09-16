<?php

namespace App\Support\SatHach;

use Illuminate\Support\Str;

class BienBanImportStore
{
    public function create(string $xmlStoredPath, string $fileName, int $expectedCount): string
    {
        $id = (string) Str::uuid();
        $dir = $this->dir($id);
        @mkdir($dir, 0755, true);

        $this->write($id, [
            'id' => $id,
            'status' => 'pending',
            'file_name' => $fileName,
            'xml_path' => $xmlStoredPath,
            'expected_count' => $expectedCount,
            'thi_sinh_count' => 0,
            'output_name' => null,
            'error_message' => null,
            'created_at' => now()->toIso8601String(),
        ]);

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        $path = $this->metaPath($id);
        if (! is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        $data = is_string($json) ? json_decode($json, true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    public function update(string $id, array $patch): void
    {
        $current = $this->get($id) ?? ['id' => $id];
        $this->write($id, array_merge($current, $patch));
    }

    public function xmlPath(string $id): string
    {
        $meta = $this->get($id);
        $stored = is_array($meta) ? (string) ($meta['xml_path'] ?? '') : '';
        if ($stored !== '') {
            return \Storage::disk('local')->path($stored);
        }

        return $this->dir($id).DIRECTORY_SEPARATOR.'input.xml';
    }

    public function outputPath(string $id): string
    {
        return $this->dir($id).DIRECTORY_SEPARATOR.'bien_ban_tong_hop.docx';
    }

    public function deleteXml(string $id): void
    {
        $meta = $this->get($id);
        $stored = is_array($meta) ? (string) ($meta['xml_path'] ?? '') : '';
        if ($stored !== '') {
            \Storage::disk('local')->delete($stored);
        }
    }

    public function forget(string $id): void
    {
        $this->deleteXml($id);
        $dir = $this->dir($id);
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function write(string $id, array $meta): void
    {
        @mkdir($this->dir($id), 0755, true);
        file_put_contents($this->metaPath($id), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function metaPath(string $id): string
    {
        return $this->dir($id).DIRECTORY_SEPARATOR.'meta.json';
    }

    private function dir(string $id): string
    {
        return storage_path('app/bien-ban/'.$id);
    }
}
