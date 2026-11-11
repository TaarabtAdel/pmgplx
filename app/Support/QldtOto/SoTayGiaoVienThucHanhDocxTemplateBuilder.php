<?php

namespace App\Support\QldtOto;

/** Gắn placeholder PhpWord vào mẫu sổ tay GV thực hành (cloneRow bảng II). */
final class SoTayGiaoVienThucHanhDocxTemplateBuilder
{
    public const GOC = 'templates/oto/so-tay-giao-vien-thuc-hanh.goc.docx';

    public const DOCX = 'templates/oto/so-tay-giao-vien-thuc-hanh.docx';

    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function build(?string $docxPath = null): string
    {
        $goc = resource_path(self::GOC);
        if (! is_file($goc)) {
            throw new \RuntimeException('Không tìm thấy mẫu gốc: '.$goc);
        }

        $docxPath ??= resource_path(self::DOCX);
        if (! copy($goc, $docxPath)) {
            throw new \RuntimeException('Không sao chép được mẫu docx.');
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
        $body = $xpath->query('/w:document/w:body')->item(0);
        if ($body === null) {
            throw new \RuntimeException('Thiếu body trong mẫu.');
        }

        $paragraphMap = [
            26 => 'Môn:${MON}    Lớp:${LOP}',
            27 => 'Hạng GPLX : ${HANG_GPLX}',
            28 => 'Họ và tên giáo viên: ${TEN_GV}',
            29 => 'Khoá: ${KHOA}',
            112 => 'Hạng giấy phép lái xe đào tạo: ${HANG_GPLX}',
            113 => 'Quyết định thành lập lớp học: ${QD_THANH_LAP}',
            114 => '${QD_THANH_LAP_2}',
            116 => 'Sĩ số lớp học: ${SI_SO}',
            117 => 'Giáo viên: ${GIAO_VIEN_LOP}',
            118 => 'Phương thức tổ chức đào tạo:',
            119 => '${PHUONG_THUC}',
        ];

        $paragraphs = $this->directParagraphs($body);
        foreach ($paragraphMap as $index => $text) {
            $this->setParagraphPlainText($dom, $paragraphs[$index] ?? null, $text);
        }

        $tbls = $xpath->query('w:tbl', $body);
        if ($tbls === false || $tbls->length < 2) {
            throw new \RuntimeException('Không tìm thấy bảng «Tổng hợp số giờ nghỉ học».');
        }

        $tbl = $tbls->item(1);
        $rows = $xpath->query('w:tr', $tbl);
        if ($rows === false || $rows->length < 3) {
            throw new \RuntimeException('Bảng học viên thiếu dòng dữ liệu mẫu.');
        }

        $templateRow = $rows->item(2);
        $cells = $xpath->query('w:tc', $templateRow);
        if ($cells === false || $cells->length < 2) {
            throw new \RuntimeException('Dòng mẫu bảng không đủ cột.');
        }

        $this->setCellPlainText($dom, $cells->item(0), '${stt}');
        $this->setCellPlainText($dom, $cells->item(1), '${ho_ten}');

        for ($i = $rows->length - 1; $i > 2; $i--) {
            $node = $rows->item($i);
            $node?->parentNode?->removeChild($node);
        }

        return $dom->saveXML() ?: $xml;
    }

    /** @return list<\DOMElement> */
    private function directParagraphs(\DOMNode $body): array
    {
        $out = [];
        foreach ($body->childNodes as $child) {
            if ($child instanceof \DOMElement
                && $child->namespaceURI === self::W_NS
                && $child->localName === 'p') {
                $out[] = $child;
            }
        }

        return $out;
    }

    private function setParagraphPlainText(\DOMDocument $dom, ?\DOMNode $paragraph, string $text): void
    {
        if ($paragraph === null) {
            throw new \RuntimeException('Mẫu thiếu đoạn văn cần gắn biến.');
        }
        while ($paragraph->firstChild) {
            $paragraph->removeChild($paragraph->firstChild);
        }

        $pPr = $dom->createElementNS(self::W_NS, 'w:pPr');
        $paragraph->appendChild($pPr);

        $r = $dom->createElementNS(self::W_NS, 'w:r');
        $t = $dom->createElementNS(self::W_NS, 'w:t');
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $t->appendChild($dom->createTextNode($text));
        $r->appendChild($t);
        $paragraph->appendChild($r);
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
