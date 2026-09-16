<?php

namespace App\Support\SatHach;

use App\Support\DaoTao\Jp2PhotoConverter;

class ImageConverterService
{
    public function portraitPath(?string $base64, string $workDir): string
    {
        $fallback = resource_path('templates/no-photo.png');
        $decoded = $this->decodePortrait($base64);
        if ($decoded === null) {
            return $fallback;
        }

        if (! is_dir($workDir)) {
            @mkdir($workDir, 0755, true);
        }

        $target = $workDir.DIRECTORY_SEPARATOR.hash('sha256', $decoded['binary']).$decoded['extension'];
        if (@file_put_contents($target, $decoded['binary']) === false) {
            return $fallback;
        }

        if (@getimagesize($target) === false) {
            @unlink($target);

            return $fallback;
        }

        return $target;
    }

    /**
     * Cùng kỹ thuật với in bảng tên: JPEG/PNG/GIF giữ nguyên, JP2 → PNG qua opj_decompress.
     *
     * @return array{binary: string, extension: string, mime: string}|null
     */
    public function decodePortrait(?string $base64): ?array
    {
        $base64 = preg_replace('/\s+/u', '', (string) $base64) ?? '';
        if ($base64 === '') {
            return null;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return ['binary' => $binary, 'extension' => '.png', 'mime' => 'image/png'];
        }

        if (str_starts_with($binary, 'GIF8')) {
            return ['binary' => $binary, 'extension' => '.gif', 'mime' => 'image/gif'];
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return ['binary' => $binary, 'extension' => '.jpg', 'mime' => 'image/jpeg'];
        }

        if (Jp2PhotoConverter::isJp2($binary)) {
            $png = Jp2PhotoConverter::toPngBinary($binary);
            if ($png === null || $png === '') {
                return null;
            }

            return ['binary' => $png, 'extension' => '.png', 'mime' => 'image/png'];
        }

        return null;
    }
}
