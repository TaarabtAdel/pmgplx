<?php

namespace App\Support\QldtOto;

/**
 * Mẫu gốc: resources/templates/oto/quyet-dinh-doi-giao-vien.doc
 * Xuất: quyet-dinh-doi-giao-vien.docx — Điều 1 lặp 2 tầng: ${KHOA} (- Khóa …:) chứa ${DONG} (+ …).
 */
class QuyetDinhDoiGiaoVienDocxTemplateBuilder
{
    public const DOC = 'templates/oto/quyet-dinh-doi-giao-vien.doc';

    public const DOCX = 'templates/oto/quyet-dinh-doi-giao-vien.docx';

    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function build(?string $docPath = null, ?string $docxPath = null): string
    {
        $docPath ??= resource_path(self::DOC);
        $docxPath ??= resource_path(self::DOCX);

        if (! is_file($docPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu .doc: '.$docPath);
        }

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'qd-doi-gv-'.uniqid('', true);
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
            '753/QĐ-TTML' => '${SO_QD}',
            'Quảng Trị, ngày 22 tháng 10 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'khóa BK56, B01K39, BK57' => 'khóa ${DS_KHOA}',
        ];
        foreach ($replacements as $from => $to) {
            $xml = str_replace($from, $to, $xml);
        }

        return $xml;
    }

    /**
     * Giữ đoạn «- Khóa …:» và «+ Giáo viên …» đầu tiên làm mẫu, bỏ phần còn lại của Điều 1 (cả dòng trống),
     * bọc bằng khối ${KHOA} / ${DONG}. Mẫu gốc bỏ trống Điều 2 — đánh số lại Điều 3, 4 → 2, 3.
     */
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

        $header = null;
        $item = null;
        $dieuHieuLuc = null;
        foreach ($paras as $p) {
            $text = trim($p->textContent);
            if ($header === null && str_starts_with($text, '- Khóa')) {
                $header = $p;
            } elseif ($header !== null && $item === null && str_starts_with($text, '+ Giáo viên')) {
                $item = $p;
            } elseif ($item !== null && str_contains($text, 'Quyết định này có hiệu lực')) {
                $dieuHieuLuc = $p;
                break;
            }
        }
        if ($header === null || $item === null || $dieuHieuLuc === null) {
            throw new \RuntimeException('Không tìm thấy khối Điều 1 (- Khóa / + Giáo viên / Điều hiệu lực) trong mẫu.');
        }

        $node = $item->nextSibling;
        while ($node !== null && $node !== $dieuHieuLuc) {
            $next = $node->nextSibling;
            $node->parentNode?->removeChild($node);
            $node = $next;
        }

        $this->setParagraphText($dom, $xpath, $header, '- Khóa ${TEN_KHOA}:');
        $this->setParagraphText($dom, $xpath, $item, '+ ${DONG_TEXT}');

        $body = $header->parentNode;
        $body->insertBefore($this->markerParagraph($dom, '${KHOA}'), $header);
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

    /** Thay toàn bộ run của đoạn bằng 1 run, giữ định dạng run có chữ đầu tiên. */
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
