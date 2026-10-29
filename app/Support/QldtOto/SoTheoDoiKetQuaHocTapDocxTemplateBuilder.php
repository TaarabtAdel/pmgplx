<?php

namespace App\Support\QldtOto;

/**
 * Gắn placeholder PhpWord vào so-theo-doi-ket-qua-hoc-tap.docx (một dòng bảng + cloneRow).
 */
class SoTheoDoiKetQuaHocTapDocxTemplateBuilder
{
    public const DOCX = 'templates/oto/so-theo-doi-ket-qua-hoc-tap.docx';

    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function build(?string $docxPath = null): string
    {
        $docxPath ??= resource_path(self::DOCX);
        if (! is_file($docxPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu: '.$docxPath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Không mở được docx.');
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $xml = $this->applyPlaceholders($xml);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $docxPath;
    }

    private function applyPlaceholders(string $xml): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $prev = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            throw new \RuntimeException('XML mẫu không hợp lệ.');
        }
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        $this->replaceExactTextNode($xpath, 'B01K39', '${TEN_KHOA}');
        $this->replaceExactTextNode($xpath, 'lập:', 'lập: ${NGAY_LAP}');

        $tbls = $xpath->query('//w:tbl');
        if ($tbls === false || $tbls->length < 2) {
            throw new \RuntimeException('Không tìm thấy bảng dữ liệu trong mẫu.');
        }

        $tbl = $tbls->item(1);
        $rows = $xpath->query('w:tr', $tbl);
        if ($rows === false || $rows->length < 3) {
            throw new \RuntimeException('Bảng mẫu thiếu dòng tiêu đề / dữ liệu.');
        }

        $templateRow = $rows->item(2);
        if ($templateRow === null) {
            throw new \RuntimeException('Không tìm thấy dòng dữ liệu mẫu.');
        }

        $placeholders = [
            '${stt}',
            '${ho_ten}',
            '${khoa_hoc}',
            '${ly_thuyet}',
            '${thuc_hanh}',
            '${ket_qua_khoa}',
            '${ngay_hoan_thanh}',
            '${xac_nhan}',
        ];
        $cells = $xpath->query('w:tc', $templateRow);
        if ($cells === false || $cells->length < count($placeholders)) {
            throw new \RuntimeException('Dòng dữ liệu mẫu không đủ cột.');
        }

        for ($c = 0; $c < count($placeholders); $c++) {
            $this->setCellPlainText($dom, $cells->item($c), $placeholders[$c]);
        }

        for ($i = $rows->length - 1; $i > 2; $i--) {
            $node = $rows->item($i);
            $node?->parentNode?->removeChild($node);
        }

        return $dom->saveXML() ?: $xml;
    }

    private function replaceExactTextNode(\DOMXPath $xpath, string $from, string $to): void
    {
        foreach ($xpath->query('//w:t') ?: [] as $node) {
            if (trim((string) $node->textContent) === trim($from)) {
                $node->nodeValue = $to;

                return;
            }
        }

        foreach ($xpath->query('//w:t') ?: [] as $node) {
            $text = (string) $node->textContent;
            if (str_contains($text, $from)) {
                $node->nodeValue = str_replace($from, $to, $text);

                return;
            }
        }
    }

    private function setCellPlainText(\DOMDocument $dom, ?\DOMNode $cell, string $text): void
    {
        if ($cell === null) {
            return;
        }
        while ($cell->firstChild) {
            $cell->removeChild($cell->firstChild);
        }

        $p = $dom->createElementNS(self::W_NS, 'w:p');
        $r = $dom->createElementNS(self::W_NS, 'w:r');
        $t = $dom->createElementNS(self::W_NS, 'w:t');
        $t->appendChild($dom->createTextNode($text));
        $r->appendChild($t);
        $p->appendChild($r);
        $cell->appendChild($p);
    }
}
