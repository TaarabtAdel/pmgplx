<?php

namespace App\Support\SatHach;

use XMLReader;

class XmlSatHachParser
{
    /**
     * @return array{
     *     header: array<string, string>,
     *     ky_sh: array<string, string>,
     *     thi_sinh: list<array<string, mixed>>
     * }
     */
    public function parse(string $xmlFilePath): array
    {
        if (! is_file($xmlFilePath) || ! is_readable($xmlFilePath)) {
            throw new \InvalidArgumentException('Không đọc được file XML.');
        }

        $reader = new XMLReader();
        if (! @$reader->open($xmlFilePath, 'UTF-8', LIBXML_NONET)) {
            throw new \InvalidArgumentException('File XML không hợp lệ hoặc không mở được.');
        }

        $header = [];
        $kySh = [];
        $thiSinh = [];
        $rootOk = false;

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    continue;
                }

                $name = $reader->localName;
                if ($name === 'SAT_HACH') {
                    $rootOk = true;
                    continue;
                }

                if (! $rootOk) {
                    throw new \InvalidArgumentException('File XML không đúng định dạng SAT_HACH.');
                }

                if ($name === 'HEADER') {
                    $header = $this->simpleXmlToFlat($this->outerSimpleXml($reader));
                    continue;
                }

                if ($name === 'KY_SH') {
                    $kySh = $this->simpleXmlToFlat($this->outerSimpleXml($reader));
                    continue;
                }

                if ($name === 'NGUOI_LX') {
                    $thiSinh[] = $this->mapNguoiLx($this->outerSimpleXml($reader), $kySh);
                }
            }
        } finally {
            $reader->close();
        }

        if (! $rootOk) {
            throw new \InvalidArgumentException('File XML không đúng định dạng SAT_HACH.');
        }

        if ($thiSinh === []) {
            throw new \InvalidArgumentException('Không tìm thấy thí sinh (NGUOI_LX) trong file XML.');
        }

        usort($thiSinh, static fn (array $a, array $b): int => ($a['so_tt'] ?? PHP_INT_MAX) <=> ($b['so_tt'] ?? PHP_INT_MAX));

        return [
            'header' => $header,
            'ky_sh' => $kySh,
            'thi_sinh' => $thiSinh,
        ];
    }

    public function countFromHeader(string $xmlFilePath): ?int
    {
        $reader = new XMLReader();
        if (! @$reader->open($xmlFilePath, 'UTF-8', LIBXML_NONET)) {
            return null;
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'TONG_SO_BAN_GHI') {
                    $value = trim($reader->readString());

                    return is_numeric($value) ? (int) $value : null;
                }
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'NGUOI_LXS') {
                    return null;
                }
            }
        } finally {
            $reader->close();
        }

        return null;
    }

    private function outerSimpleXml(XMLReader $reader): \SimpleXMLElement
    {
        $xml = $reader->readOuterXml();
        $node = @simplexml_load_string($xml);
        if ($node === false) {
            throw new \InvalidArgumentException('Không parse được một khối XML trong file.');
        }

        return $node;
    }

    /**
     * @return array<string, string>
     */
    private function simpleXmlToFlat(\SimpleXMLElement $node): array
    {
        $out = [];
        foreach ($node->children() as $child) {
            $out[$child->getName()] = trim((string) $child);
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $kySh
     * @return array<string, mixed>
     */
    private function mapNguoiLx(\SimpleXMLElement $node, array $kySh): array
    {
        $hoSo = $node->HO_SO ?? null;
        $hoVaTen = $this->text($node, 'HO_VA_TEN');
        if ($hoVaTen === '') {
            $hoVaTen = trim($this->text($node, 'HO_TEN_DEM').' '.$this->text($node, 'TEN'));
        }

        $ngayKyNguon = $this->hoSoText($hoSo, 'NGAY_QD_SH');
        if ($ngayKyNguon === '') {
            $ngayKyNguon = $kySh['NGAYSH'] ?? $kySh['NGAYQD'] ?? '';
        }
        [$ngayKy, $thangKy, $namKy] = $this->splitDateParts($ngayKyNguon);

        $hang = $this->hoSoText($hoSo, 'HANG_GPLX');
        $ketQuaSh = $this->hoSoText($hoSo, 'KET_QUA_SH');

        return [
            'so_tt' => $this->intOrNull($this->text($node, 'SO_TT')),
            'ma_dk' => $this->text($node, 'MA_DK'),
            'ho_va_ten' => $hoVaTen,
            'ngay_sinh' => $this->formatDisplayDate($this->text($node, 'NGAY_SINH')),
            'so_cmt' => $this->text($node, 'SO_CMT'),
            'so_ho_chieu' => $this->text($node, 'SO_HO_CHIEU'),
            'ngay_cap_hc' => $this->formatDisplayDate($this->text($node, 'NGAY_CAP_HC') ?: $this->text($node, 'NGAY_CAP_CMT')),
            'noi_cap_hc' => $this->text($node, 'NOI_CAP_HC') ?: $this->text($node, 'NOI_CAP_CMT'),
            'so_bao_danh' => $this->hoSoText($hoSo, 'SO_BAO_DANH'),
            'hang_gplx' => $hang,
            'diem_lt_toida' => self::diemLtToiDa($hang),
            'diem_lt_dat' => $this->score($this->hoSoText($hoSo, 'KQ_SH_LYTHUYET')),
            'diem_hinh_dat' => $this->score($this->hoSoText($hoSo, 'KQ_SH_HINH')),
            'diem_duong_dat' => $this->score($this->hoSoText($hoSo, 'KQ_SH_DUONG')),
            'nhan_xet_lt' => $this->hoSoText($hoSo, 'NHAN_XET_LT') ?: $this->hoSoText($hoSo, 'GHI_CHU_SH'),
            'nhan_xet_hinh' => $this->hoSoText($hoSo, 'NHAN_XET_HINH'),
            'nhan_xet_duong' => $this->hoSoText($hoSo, 'NHAN_XET_DUONG'),
            'ket_qua_text' => $this->ketQuaText($ketQuaSh),
            'ket_qua_sh' => $ketQuaSh,
            'ngay_ky' => $ngayKy,
            'thang_ky' => $thangKy,
            'nam_ky' => $namKy,
            'anh_chan_dung_b64' => $this->hoSoText($hoSo, 'ANH_CHAN_DUNG'),
        ];
    }

    private function text(\SimpleXMLElement $node, string $name): string
    {
        return trim((string) ($node->{$name} ?? ''));
    }

    private function hoSoText(?\SimpleXMLElement $hoSo, string $name): string
    {
        if ($hoSo === null) {
            return '';
        }

        return trim((string) ($hoSo->{$name} ?? ''));
    }

    private function intOrNull(string $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function score(string $value): string
    {
        return $value === '' ? '-' : $value;
    }

    public static function formatDisplayDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)) {
            return $m[3].'/'.$m[2].'/'.$m[1];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return $m[3].'/'.$m[2].'/'.$m[1];
        }

        return $value;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public static function splitDateParts(string $value): array
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)) {
            return [$m[3], $m[2], $m[1]];
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return [$m[3], $m[2], $m[1]];
        }

        return ['', '', ''];
    }

    /**
     * Điểm lý thuyết tối đa theo hạng GPLX: 30 (B, B.01), 35 (C1).
     */
    public static function diemLtToiDa(string $hang): string
    {
        $key = strtoupper(str_replace([' ', '_'], '', trim($hang)));
        $key = str_replace('-', '.', $key);

        return match ($key) {
            'B', 'B.01', 'B01' => '30',
            'C1' => '35',
            default => '',
        };
    }

    public static function ketQuaText(string $raw): string
    {
        $n = mb_strtolower(trim($raw));
        if ($n === '') {
            return 'Đạt ☐    Không đạt ☐';
        }

        $normalized = strtr($n, ['đ' => 'd']);
        if (str_contains($normalized, 'khong')) {
            return 'Đạt ☐    Không đạt ☑';
        }
        if (in_array($normalized, ['1', 'd', 'true', 'yes', 'dat'], true) || str_contains($normalized, 'dat')) {
            return 'Đạt ☑    Không đạt ☐';
        }

        return 'Đạt ☐    Không đạt ☑';
    }
}
