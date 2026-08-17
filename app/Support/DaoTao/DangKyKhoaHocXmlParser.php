<?php

namespace App\Support\DaoTao;

class DangKyKhoaHocXmlParser
{
    /**
     * @return array{
     *     file_name: string,
     *     khoa_hoc: array<string, string|null>,
     *     hoc_vien: list<array<string, mixed>>,
     *     meta: array{hoc_vien_count: int}
     * }
     */
    public function parse(string $path, string $fileName): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new \InvalidArgumentException('File XML không hợp lệ hoặc không đọc được.');
        }

        if ($xml->getName() !== 'DANG_KY_KHOA_HOC') {
            throw new \InvalidArgumentException('File XML không đúng định dạng DANG_KY_KHOA_HOC.');
        }

        $khoaNode = $xml->DATA->KHOA_HOC ?? null;
        if ($khoaNode === null) {
            throw new \InvalidArgumentException('Không tìm thấy thông tin khoá học trong file XML.');
        }

        $khoaHang = trim((string) ($khoaNode->HANG_GPLX ?? $khoaNode->MA_HANG_DAO_TAO ?? ''));
        $khoaHoc = [
            'ma_dang_ky' => trim((string) ($khoaNode->MA_DANG_KY_KHOA_HOC ?? '')),
            'ma_khoa_hoc' => trim((string) ($khoaNode->MA_KHOA_HOC ?? '')),
            'ten_khoa_hoc' => trim((string) ($khoaNode->TEN_KHOA_HOC ?? '')),
            'hang_gplx' => $khoaHang,
            'so_hoc_sinh' => trim((string) ($khoaNode->SO_HOC_SINH ?? '')),
        ];

        $hocVien = [];
        $nodes = $xml->DATA->NGUOI_LXS->NGUOI_LX ?? [];
        foreach ($nodes as $node) {
            $hoTen = trim((string) ($node->HO_VA_TEN_IN ?? $node->HO_VA_TEN ?? ''));
            if ($hoTen === '') {
                continue;
            }

            $hoSo = $node->HO_SO ?? null;
            $hang = trim((string) ($hoSo->HANG_GPLX ?? $hoSo->HANG_DAOTAO ?? $khoaHang));
            $anhRaw = trim((string) ($hoSo->ANH_CHAN_DUNG ?? ''));

            $hocVien[] = [
                'so_tt' => is_numeric((string) ($node->SO_TT ?? '')) ? (int) $node->SO_TT : null,
                'ho_ten' => $hoTen,
                'hang_gplx' => $hang !== '' ? $hang : '—',
                'anh_src' => $this->photoDataUri($anhRaw),
            ];
        }

        if ($hocVien === []) {
            throw new \InvalidArgumentException('Không tìm thấy học viên nào trong file XML.');
        }

        usort($hocVien, fn (array $a, array $b): int => ($a['so_tt'] ?? PHP_INT_MAX) <=> ($b['so_tt'] ?? PHP_INT_MAX));

        return [
            'file_name' => $fileName,
            'khoa_hoc' => $khoaHoc,
            'hoc_vien' => $hocVien,
            'meta' => [
                'hoc_vien_count' => count($hocVien),
            ],
        ];
    }

    private function photoDataUri(string $base64): ?string
    {
        $base64 = preg_replace('/\s+/u', '', $base64) ?? $base64;
        if ($base64 === '') {
            return null;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = 'image/jpeg';
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            $mime = 'image/png';
        } elseif (str_starts_with($binary, 'GIF8')) {
            $mime = 'image/gif';
        } elseif (str_starts_with($binary, "\xFF\xD8\xFF")) {
            $mime = 'image/jpeg';
        } elseif (Jp2PhotoConverter::isJp2($binary)) {
            return Jp2PhotoConverter::toDataUri($binary);
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
