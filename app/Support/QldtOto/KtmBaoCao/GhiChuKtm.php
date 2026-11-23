<?php

namespace App\Support\QldtOto\KtmBaoCao;

/** Phân tích cột Ghi chú → các nội dung phải thi. */
final class GhiChuKtm
{
    /**
     * @return array{lt: bool, mp: bool, h: bool, duong: bool, cc: bool}
     */
    /**
     * Danh sách sát hạch: cột Ghi chú thường trống → coi là thi đủ các môn.
     *
     * @return array{lt: bool, mp: bool, h: bool, duong: bool, cc: bool}
     */
    public static function phaiThiChoSatHach(?string $ghiChu): array
    {
        if (trim((string) $ghiChu) === '') {
            return [
                'lt' => true,
                'mp' => true,
                'h' => true,
                'duong' => true,
                'cc' => true,
            ];
        }

        return self::phaiThi($ghiChu);
    }

    public static function phaiThi(?string $ghiChu): array
    {
        $tokens = self::maTuGhiChu($ghiChu);

        return [
            'lt' => in_array('LT', $tokens, true),
            'mp' => in_array('MP', $tokens, true),
            'h' => in_array('H', $tokens, true),
            'duong' => in_array('Đ', $tokens, true) || in_array('D', $tokens, true),
            'cc' => in_array('CC', $tokens, true),
        ];
    }

    /**
     * @return list<string>
     */
    public static function maTuGhiChu(?string $ghiChu): array
    {
        $raw = mb_strtoupper(trim((string) $ghiChu));
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if ($p === 'D' || $p === 'Đ') {
                $out[] = 'Đ';
            } elseif (in_array($p, ['LT', 'MP', 'H', 'CC'], true)) {
                $out[] = $p;
            } elseif (preg_match('/^V(LT|MP|H|Đ|CC|D)$/u', $p, $m)) {
                continue;
            } elseif (preg_match('/^(LT|MP|H|Đ|CC)$/u', $p)) {
                $out[] = $p === 'Đ' ? 'Đ' : $p;
            }
        }

        return array_values(array_unique($out));
    }
}