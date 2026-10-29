<?php

namespace App\Support\QldtOto;

/**
 * Gắn placeholder PhpWord vào phan-cong-giao-vien-xe-tap-lai.docx (một dòng bảng + cloneRow).
 */
class PhanCongGvXeTapLaiDocxTemplateBuilder
{
    public const DOCX = 'templates/oto/phan-cong-giao-vien-xe-tap-lai.docx';

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

        $this->replaceFirstText($xpath, 'Năm 2026', 'Năm ${NAM}');
        $this->replaceFirstText($xpath, 'C1K04', '${TEN_KHOA}', afterContains: 'KHOÁ');

        $rows = $xpath->query('//w:tbl//w:tr');
        if ($rows === false || $rows->length === 0) {
            throw new \RuntimeException('Không tìm thấy bảng trong mẫu.');
        }

        $headerIdx = null;
        for ($i = 0; $i < $rows->length; $i++) {
            $text = $this->rowText($rows->item($i));
            if (str_contains($text, 'Giáo viên') || str_contains($text, 'Giáo')) {
                $headerIdx = $i;
                break;
            }
        }
        if ($headerIdx === null) {
            throw new \RuntimeException('Không tìm thấy dòng tiêu đề bảng.');
        }

        $templateRow = $rows->item($headerIdx + 1);
        if ($templateRow === null) {
            throw new \RuntimeException('Thiếu dòng dữ liệu mẫu trong bảng.');
        }

        $placeholders = ['${stt}', '${giao_vien}', '${thoi_gian}', '${khoa_dt}', '${bien_so}', '${noi_dung}'];
        $cells = $xpath->query('w:tc', $templateRow);
        if ($cells === false || $cells->length < count($placeholders)) {
            throw new \RuntimeException('Bảng mẫu không đủ cột.');
        }

        for ($c = 0; $c < count($placeholders); $c++) {
            $this->setCellPlainText($dom, $cells->item($c), $placeholders[$c]);
        }

        $tbl = $xpath->query('ancestor::w:tbl', $templateRow)->item(0);
        if ($tbl === null) {
            throw new \RuntimeException('Không xác định được bảng cha.');
        }

        $tblRows = $xpath->query('w:tr', $tbl);
        if ($tblRows !== false) {
            for ($i = $tblRows->length - 1; $i > $headerIdx + 1; $i--) {
                $node = $tblRows->item($i);
                $node?->parentNode?->removeChild($node);
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    private function replaceFirstText(\DOMXPath $xpath, string $from, string $to, ?string $afterContains = null): void
    {
        $nodes = $xpath->query('//w:t');
        if ($nodes === false) {
            return;
        }

        $seenAnchor = $afterContains === null;
        foreach ($nodes as $node) {
            if (! $seenAnchor && $afterContains !== null) {
                if (str_contains((string) $node->textContent, $afterContains)) {
                    $seenAnchor = true;
                }
                continue;
            }
            if ((string) $node->textContent === $from) {
                $node->nodeValue = $to;

                return;
            }
        }

        if ($afterContains === null) {
            foreach ($nodes as $node) {
                if (str_contains((string) $node->textContent, $from)) {
                    $node->nodeValue = str_replace($from, $to, (string) $node->textContent);

                    return;
                }
            }
        }
    }

    private function rowText(?\DOMNode $row): string
    {
        if ($row === null) {
            return '';
        }
        $doc = $row->ownerDocument;
        if ($doc === null) {
            return '';
        }
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('w', self::W_NS);
        $parts = [];
        foreach ($xpath->query('.//w:t', $row) ?: [] as $t) {
            $parts[] = (string) $t->textContent;
        }

        return implode('', $parts);
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
