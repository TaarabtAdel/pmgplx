<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocxTextReplacer;

/**
 * Mẫu gốc: resources/templates/oto/bao-cao-de-nghi-to-chuc-sat-hach.goc.docx
 * Xuất: bao-cao-de-nghi-to-chuc-sat-hach.docx (placeholder PhpWord).
 */
class BaoCaoDeNghiSatHachDocxTemplateBuilder
{
    public const GOC = 'templates/oto/bao-cao-de-nghi-to-chuc-sat-hach.goc.docx';

    public const DOCX = 'templates/oto/bao-cao-de-nghi-to-chuc-sat-hach.docx';

    public function build(?string $gocPath = null, ?string $docxPath = null): string
    {
        $gocPath ??= resource_path(self::GOC);
        $docxPath ??= resource_path(self::DOCX);

        if (! is_file($gocPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu gốc: '.$gocPath);
        }
        if (! copy($gocPath, $docxPath)) {
            throw new \RuntimeException('Không ghi được: '.$docxPath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Không mở được docx.');
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->addFromString('word/document.xml', $this->applyPlaceholders($xml));
        $zip->close();

        return $docxPath;
    }

    private function applyPlaceholders(string $xml): string
    {
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            libxml_use_internal_errors($prev);
            throw new \RuntimeException('XML mẫu không hợp lệ.');
        }
        libxml_use_internal_errors($prev);

        $xpath = DocxTextReplacer::xpath($dom);
        $body = $xpath->query('/w:document/w:body')->item(0);

        $map = [
            '707/TTML-2026' => '${SO_BC}',
            'Quảng Trị, ngày 11 tháng 10 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'Phòng Cảnh sát giao thông Công an tỉnh Quảng Trị' => '${KINH_GUI}',
            'hạng B (Số sàn) Khóa 44007K261005 cho 99 học viên' => 'hạng ${HANG} Khóa ${KHOA_LOP} cho ${SO_DU_SH} học viên',
        ];
        foreach ($map as $from => $to) {
            if (DocxTextReplacer::replace($xpath, $body, $from, $to) === 0) {
                throw new \RuntimeException('Không tìm thấy «'.$from.'» trong mẫu.');
            }
        }

        $dataRow = null;
        $totalRow = null;
        foreach ($xpath->query('//w:tbl/w:tr') ?: [] as $tr) {
            $text = preg_replace('/\s+/u', ' ', $tr->textContent) ?? '';
            if ($dataRow === null && str_contains($text, '44007K261005')) {
                $dataRow = $tr;
            } elseif ($totalRow === null && str_contains($text, 'TỔNG CỘNG')) {
                $totalRow = $tr;
            }
        }
        if ($dataRow === null || $totalRow === null) {
            throw new \RuntimeException('Không tìm thấy dòng dữ liệu / tổng cộng trong bảng mẫu.');
        }

        $cells = iterator_to_array($xpath->query('w:tc', $dataRow) ?: []);
        $placeholders = ['1', '${KHOA_LOP}', '${HANG}', '${SO_VAO_HOC}', '${SO_DU_SH}', '${NGAY_SH}', '${GHI_CHU}'];
        if (count($cells) < count($placeholders)) {
            throw new \RuntimeException('Dòng dữ liệu bảng mẫu không đủ 7 cột.');
        }
        foreach ($placeholders as $i => $ph) {
            DocxTextReplacer::setCellText($xpath, $cells[$i], $ph);
        }

        $totalCells = iterator_to_array($xpath->query('w:tc', $totalRow) ?: []);
        DocxTextReplacer::setCellText($xpath, $totalCells[1] ?? $totalCells[array_key_last($totalCells)], '${SO_DU_SH}');

        return $dom->saveXML() ?: $xml;
    }
}
