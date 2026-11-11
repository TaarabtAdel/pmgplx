<?php

namespace App\Support\QldtOto;

/**
 * Mẫu gốc: resources/templates/oto/quyet-dinh-doi-xe.doc
 * Xuất: quyet-dinh-doi-xe.docx — Điều 1: ${KHOA} chứa ${DONG} (+ ${DONG_TEXT}).
 */
class QuyetDinhDoiXeDocxTemplateBuilder
{
    public const DOC = 'templates/oto/quyet-dinh-doi-xe.doc';

    public const DOCX = 'templates/oto/quyet-dinh-doi-xe.docx';

    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function build(?string $docPath = null, ?string $docxPath = null): string
    {
        $docPath ??= resource_path(self::DOC);
        $docxPath ??= resource_path(self::DOCX);

        if (! is_file($docPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu .doc: '.$docPath);
        }

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'qd-doi-xe-'.uniqid('', true);
        if (! @mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException('Không tạo được thư mục tạm.');
        }

        try {
            $tmpDocx = $tmpDir.DIRECTORY_SEPARATOR.pathinfo($docPath, PATHINFO_FILENAME).'.docx';
            $this->convertDocToDocx($docPath, $tmpDir);

            $zip = new \ZipArchive();
            if ($zip->open($tmpDocx) !== true) {
                throw new \RuntimeException('Không mở được file docx tạm.');
            }
            $xml = (string) $zip->getFromName('word/document.xml');
            $xml = $this->applyTextPlaceholders($xml);
            $xml = $this->buildDieu1Blocks($xml);
            $zip->addFromString('word/document.xml', $xml);
            $zip->close();

            if (! @mkdir(dirname($docxPath), 0755, true) && ! is_dir(dirname($docxPath))) {
                throw new \RuntimeException('Không tạo được thư mục mẫu docx.');
            }
            if (! copy($tmpDocx, $docxPath)) {
                throw new \RuntimeException('Không ghi được: '.$docxPath);
            }
        } finally {
            $this->deleteDirectory($tmpDir);
        }

        return $docxPath;
    }

    private function convertDocToDocx(string $docPath, string $outDir): void
    {
        $cmd = sprintf(
            'soffice --headless --convert-to docx --outdir %s %s 2>&1',
            escapeshellarg($outDir),
            escapeshellarg($docPath)
        );
        exec($cmd, $output, $code);
        $generated = $outDir.DIRECTORY_SEPARATOR.pathinfo($docPath, PATHINFO_FILENAME).'.docx';
        if ($code !== 0 || ! is_file($generated)) {
            throw new \RuntimeException("LibreOffice không chuyển được .doc sang .docx.\n".implode("\n", $output));
        }
    }

    private function applyTextPlaceholders(string $xml): string
    {
        $xml = preg_replace('/<w:instrText[^>]*>[^<]*<\/w:instrText>/', '', $xml) ?? $xml;
        $xml = preg_replace('~<w:fldChar\b[^>]*(?:/>|>.*?</w:fldChar>)~s', '', $xml) ?? $xml;

        $replacements = [
            '842/QĐ-TTML' => '${SO_QD}',
            'Quảng Trị, ngày 10 tháng 11 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'V/v điều chỉnh xe giảng dạy khóa B01K41' => 'V/v điều chỉnh xe giảng dạy khóa ${DS_KHOA}',
            'Quyết định điều chỉnh xe giảng dạy khóa B01K41 như sau:' => 'Quyết định điều chỉnh xe giảng dạy khóa ${DS_KHOA} như sau:',
        ];
        foreach ($replacements as $from => $to) {
            $xml = str_replace($from, $to, $xml);
        }

        return $xml;
    }

    private function buildDieu1Blocks(string $xml): string
    {
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            libxml_use_internal_errors($prev);
            throw new \RuntimeException('XML mẫu không hợp lệ.');
        }
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        /** @var list<\DOMElement> $paras */
        $paras = iterator_to_array($xpath->query('/w:document/w:body/w:p') ?: []);

        $item = null;
        $dieuHieuLuc = null;
        foreach ($paras as $p) {
            $text = trim($p->textContent);
            if ($item === null && str_starts_with($text, '+ Giáo viên')) {
                $item = $p;
            } elseif ($item !== null && str_contains($text, 'Quyết định này có hiệu lực')) {
                $dieuHieuLuc = $p;
                break;
            }
        }
        if ($item === null || $dieuHieuLuc === null) {
            throw new \RuntimeException('Không tìm thấy khối Điều 1 (+ Giáo viên / Điều hiệu lực) trong mẫu.');
        }

        $node = $item->nextSibling;
        while ($node !== null && $node !== $dieuHieuLuc) {
            $next = $node->nextSibling;
            $node->parentNode?->removeChild($node);
            $node = $next;
        }

        $this->setParagraphText($dom, $xpath, $item, '+ ${DONG_TEXT}');

        $body = $item->parentNode;
        $body->insertBefore($this->markerParagraph($dom, '${KHOA}'), $item);
        $body->insertBefore($this->markerParagraph($dom, '${DONG}'), $item);
        $after = $item->nextSibling;
        $body->insertBefore($this->markerParagraph($dom, '${/DONG}'), $after);
        $body->insertBefore($this->markerParagraph($dom, '${/KHOA}'), $after);

        foreach ($xpath->query('//w:t') ?: [] as $t) {
            $old = (string) $t->textContent;
            $new = str_replace(
                ['Điều 3.', 'Điều 4.', 'Như điều 4'],
                ['Điều 2.', 'Điều 3.', 'Như điều 3'],
                $old
            );
            if ($new !== $old) {
                $t->textContent = $new;
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    private function setParagraphText(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $p, string $text): void
    {
        $runs = iterator_to_array($xpath->query('w:r', $p) ?: []);
        $rPr = null;
        foreach ($runs as $r) {
            if (trim($r->textContent) !== '') {
                $rPr = $xpath->query('w:rPr', $r)?->item(0);
                break;
            }
        }
        $rPr = $rPr?->cloneNode(true);
        foreach ($runs as $r) {
            $p->removeChild($r);
        }

        $run = $dom->createElementNS(self::W_NS, 'w:r');
        if ($rPr !== null) {
            $run->appendChild($rPr);
        }
        $t = $dom->createElementNS(self::W_NS, 'w:t');
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $t->appendChild($dom->createTextNode($text));
        $run->appendChild($t);
        $p->appendChild($run);
    }

    private function markerParagraph(\DOMDocument $dom, string $marker): \DOMElement
    {
        $p = $dom->createElementNS(self::W_NS, 'w:p');
        $r = $dom->createElementNS(self::W_NS, 'w:r');
        $t = $dom->createElementNS(self::W_NS, 'w:t');
        $t->appendChild($dom->createTextNode($marker));
        $r->appendChild($t);
        $p->appendChild($r);

        return $p;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$entry;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
