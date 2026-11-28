<?php

namespace App\Support\PMGPLX;

class DuongDanAnhPmGplx
{
    public static function encodePath(string $duongDanAnh): string
    {
        return rtrim(strtr(base64_encode($duongDanAnh), '+/', '-_'), '=');
    }

    public static function decodePath(string $token): ?string
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $b64 = strtr($token, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }

    public static function previewUrl(?string $duongDanAnh): ?string
    {
        $duongDanAnh = trim((string) $duongDanAnh);
        if ($duongDanAnh === '') {
            return null;
        }

        return route('pmgplx.dm.hoc-vien.anh', [
            'p' => self::encodePath($duongDanAnh),
        ]);
    }

    /**
     * @deprecated Không dùng — đồng bộ giữ nguyên DuongDanAnh từ PM mới.
     */
    public static function remapForDich(
        string $duongDanAnh,
        string $sourceMaDk,
        string $targetMaDk,
        string $maKhoaHocNguon,
        string $maKhoaHocDich
    ): string {
        if ($duongDanAnh === '') {
            return '';
        }

        return str_replace(
            [
                $sourceMaDk,
                $maKhoaHocNguon,
                MaDkBanCu::prefixFromMaKhoaHoc($maKhoaHocNguon),
            ],
            [
                $targetMaDk,
                $maKhoaHocDich,
                MaDkBanCu::prefixFromMaKhoaHoc($maKhoaHocDich),
            ],
            $duongDanAnh
        );
    }

    public static function resolveReadablePath(string $duongDanAnh): ?string
    {
        $duongDanAnh = trim($duongDanAnh);
        if ($duongDanAnh === '') {
            return null;
        }

        $candidates = [];

        $normalized = str_replace('/', '\\', $duongDanAnh);
        $forward = str_replace('\\', '/', $duongDanAnh);
        $candidates[] = $duongDanAnh;
        $candidates[] = $normalized;
        $candidates[] = $forward;

        $uncPrefix = trim((string) config('pmgplx.anh.unc_prefix', ''));
        $windowsPrefix = trim((string) config('pmgplx.anh.windows_prefix', 'C:\\GPLX'));
        $localRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim((string) config('pmgplx.anh.local_root', ''))), DIRECTORY_SEPARATOR);

        foreach ([$uncPrefix, $windowsPrefix] as $prefix) {
            if ($prefix === '' || $localRoot === '') {
                continue;
            }
            $prefixNorm = rtrim(str_replace('/', '\\', $prefix), '\\');
            $prefixFwd = rtrim(str_replace('\\', '/', $prefix), '/');
            foreach ([$normalized, $forward] as $path) {
                $useNorm = str_contains($path, '\\');
                $pfx = $useNorm ? $prefixNorm : $prefixFwd;
                if ($pfx !== '' && str_starts_with(strtoupper($path), strtoupper($pfx))) {
                    $rest = substr($path, strlen($pfx));
                    $rest = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rest), DIRECTORY_SEPARATOR);
                    $candidates[] = $localRoot.DIRECTORY_SEPARATOR.$rest;
                }
            }
        }

        if ($localRoot !== '') {
            $base = basename($forward);
            if ($base !== '') {
                $candidates[] = $localRoot.DIRECTORY_SEPARATOR.$base;
            }
        }

        foreach (array_unique($candidates) as $path) {
            if ($path !== '' && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Đường dẫn ảnh sẵn sàng nhúng Word (jp2 → png tạm). Không có file → null.
     */
    public static function prepareDocxImagePath(string $duongDanAnh, string $workDir): ?string
    {
        $src = self::resolveReadablePath($duongDanAnh);
        if ($src === null) {
            return null;
        }

        $binary = @file_get_contents($src);
        if ($binary === false || $binary === '') {
            return null;
        }

        if (Jp2PhotoConverter::isJp2($binary)) {
            $png = Jp2PhotoConverter::toPngBinary($binary);
            if ($png === null || $png === '') {
                return null;
            }
            @mkdir($workDir, 0755, true);
            $out = $workDir.DIRECTORY_SEPARATOR.hash('sha256', $binary).'.png';
            if (@file_put_contents($out, $png) === false) {
                return null;
            }

            return $out;
        }

        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            return null;
        }

        return $src;
    }
}
