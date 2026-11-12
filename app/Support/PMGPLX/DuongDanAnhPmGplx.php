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
        $candidates[] = $duongDanAnh;
        $candidates[] = $normalized;

        $uncPrefix = trim((string) config('pmgplx.anh.unc_prefix', ''));
        $localRoot = rtrim(str_replace('/', DIRECTORY_SEPARATOR, trim((string) config('pmgplx.anh.local_root', ''))), DIRECTORY_SEPARATOR);

        if ($uncPrefix !== '' && $localRoot !== '') {
            $prefixNorm = str_replace('/', '\\', $uncPrefix);
            foreach ([$duongDanAnh, $normalized] as $path) {
                if (str_starts_with($path, $prefixNorm)) {
                    $rest = substr($path, strlen($prefixNorm));
                    $rest = ltrim(str_replace('\\', DIRECTORY_SEPARATOR, $rest), DIRECTORY_SEPARATOR);
                    $candidates[] = $localRoot.DIRECTORY_SEPARATOR.$rest;
                }
            }
        }

        if ($localRoot !== '') {
            $base = basename(str_replace('\\', '/', $duongDanAnh));
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
}
