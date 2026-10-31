<?php

namespace App\Support\Docx;

/**
 * Dựng mẫu PhpWord từ file .doc của phòng: LibreOffice chuyển sang .docx rồi thay chữ mẫu bằng ${BIEN}.
 * Mỗi chữ mẫu phải tìm thấy ít nhất một lần — sai chữ là báo lỗi ngay khi build, không sinh mẫu thiếu biến.
 */
final class DocMauBuilder
{
    /**
     * @param  array<string, string>  $map  chữ trong mẫu => chữ thay (thường là ${BIEN})
     * @param  (callable(\DOMXPath): void)|null  $sua  chỉnh thêm DOM sau khi thay chữ
     */
    public static function tuDoc(string $docPath, string $docxPath, array $map, ?callable $sua = null): string
    {
        if (! is_file($docPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu: '.$docPath);
        }

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'doc-mau-'.uniqid('', true);
        if (! @mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException('Không tạo được thư mục tạm.');
        }

        try {
            $tmpDocx = $tmpDir.DIRECTORY_SEPARATOR.pathinfo($docPath, PATHINFO_FILENAME).'.docx';
            if (strtolower(pathinfo($docPath, PATHINFO_EXTENSION)) === 'docx') {
                // Mẫu đã là .docx: giữ nguyên định dạng gốc, không qua LibreOffice.
                if (! copy($docPath, $tmpDocx)) {
                    throw new \RuntimeException('Không sao chép được mẫu .docx.');
                }
            } else {
                exec(sprintf(
                    'soffice --headless --convert-to docx --outdir %s %s 2>&1',
                    escapeshellarg($tmpDir),
                    escapeshellarg($docPath)
                ), $output, $code);
                if ($code !== 0 || ! is_file($tmpDocx)) {
                    throw new \RuntimeException("LibreOffice không chuyển được .doc sang .docx.\n".implode("\n", $output));
                }
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmpDocx) !== true) {
                throw new \RuntimeException('Không mở được file docx tạm.');
            }
            $xml = (string) $zip->getFromName('word/document.xml');
            $zip->addFromString('word/document.xml', self::thayChu($xml, $map, $sua));
            $zip->close();

            if (! @mkdir(dirname($docxPath), 0755, true) && ! is_dir(dirname($docxPath))) {
                throw new \RuntimeException('Không tạo được thư mục mẫu docx.');
            }
            if (! copy($tmpDocx, $docxPath)) {
                throw new \RuntimeException('Không ghi được: '.$docxPath);
            }
        } finally {
            foreach (glob($tmpDir.DIRECTORY_SEPARATOR.'*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }

        return $docxPath;
    }

    /** Đoạn chỉ chứa dấu khối PhpWord (vd. ${KHOI} / ${/KHOI}) — cloneBlock bỏ đi khi xuất. */
    public static function doanDanhDau(\DOMDocument $dom, string $marker): \DOMElement
    {
        $p = $dom->createElementNS(DocxTextReplacer::W_NS, 'w:p');
        $r = $dom->createElementNS(DocxTextReplacer::W_NS, 'w:r');
        $t = $dom->createElementNS(DocxTextReplacer::W_NS, 'w:t');
        $t->appendChild($dom->createTextNode($marker));
        $r->appendChild($t);
        $p->appendChild($r);

        return $p;
    }

    /** Bọc các phần tử liền nhau [$dau … $cuoi] của body trong khối ${$ten} … ${/$ten}. */
    public static function bocKhoi(\DOMElement $dau, \DOMElement $cuoi, string $ten): void
    {
        $body = $dau->parentNode;
        $body->insertBefore(self::doanDanhDau($dau->ownerDocument, '${'.$ten.'}'), $dau);
        $body->insertBefore(self::doanDanhDau($dau->ownerDocument, '${/'.$ten.'}'), $cuoi->nextSibling);
    }

    /**
     * Bảng rỗng ở cuối file (thừa khi soạn) đẩy đoạn cuối bắt buộc sang trang trắng.
     */
    public static function boBangRongCuoi(\DOMXPath $xpath): void
    {
        $body = $xpath->query('/w:document/w:body')->item(0);
        $node = $body?->lastChild;
        while ($node !== null) {
            $prev = $node->previousSibling;
            if ($node instanceof \DOMElement && $node->localName === 'tbl') {
                $rong = trim($node->textContent) === ''
                    && $xpath->query('.//w:drawing|.//w:pict', $node)->length === 0;
                if (! $rong) {
                    break;
                }
                $body->removeChild($node);
            } elseif ($node instanceof \DOMElement && $node->localName === 'p' && trim($node->textContent) !== '') {
                break;
            }
            $node = $prev;
        }
    }

    /**
     * Dòng trống thừa sau khối chữ ký đẩy sang trang trắng: bỏ hết, chỉ giữ một đoạn 1pt
     * (Word bắt buộc body kết thúc bằng một đoạn).
     */
    public static function boDoanTrongCuoi(\DOMXPath $xpath): void
    {
        $body = $xpath->query('/w:document/w:body')->item(0);
        $sectPr = $xpath->query('w:sectPr', $body)->item(0);

        $node = $sectPr?->previousSibling ?? $body?->lastChild;
        while ($node !== null) {
            $prev = $node->previousSibling;
            if (! $node instanceof \DOMElement) {
                $node = $prev;

                continue;
            }
            $trong = $node->localName === 'p'
                && trim($node->textContent) === ''
                && $xpath->query('.//w:drawing|.//w:pict|.//w:br[@w:type="page"]', $node)->length === 0;
            if (! $trong) {
                break;
            }
            $body->removeChild($node);
            $node = $prev;
        }

        $doc = $body->ownerDocument;
        $p = $doc->createElementNS(DocxTextReplacer::W_NS, 'w:p');
        $pPr = $doc->createElementNS(DocxTextReplacer::W_NS, 'w:pPr');
        $spacing = $doc->createElementNS(DocxTextReplacer::W_NS, 'w:spacing');
        foreach (['w:before' => '0', 'w:after' => '0', 'w:line' => '20', 'w:lineRule' => 'exact'] as $attr => $val) {
            $spacing->setAttributeNS(DocxTextReplacer::W_NS, $attr, $val);
        }
        $pPr->appendChild($spacing);
        $rPr = $doc->createElementNS(DocxTextReplacer::W_NS, 'w:rPr');
        $sz = $doc->createElementNS(DocxTextReplacer::W_NS, 'w:sz');
        $sz->setAttributeNS(DocxTextReplacer::W_NS, 'w:val', '2');
        $rPr->appendChild($sz);
        $pPr->appendChild($rPr);
        $p->appendChild($pPr);
        $body->insertBefore($p, $sectPr);
    }

    /** @param  array<string, string>  $map */
    private static function thayChu(string $xml, array $map, ?callable $sua = null): string
    {
        // Trường (field) Word cũ để lại instrText / fldChar làm hỏng biến nằm giữa.
        $xml = preg_replace('/<w:instrText[^>]*>[^<]*<\/w:instrText>/', '', $xml) ?? $xml;
        $xml = preg_replace('~<w:fldChar\b[^>]*(?:/>|>.*?</w:fldChar>)~s', '', $xml) ?? $xml;

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            libxml_use_internal_errors($prev);
            throw new \RuntimeException('XML mẫu không hợp lệ.');
        }
        libxml_use_internal_errors($prev);

        $xpath = DocxTextReplacer::xpath($dom);
        $body = $xpath->query('/w:document/w:body')->item(0);

        $thieu = [];
        foreach ($map as $from => $to) {
            if (DocxTextReplacer::replace($xpath, $body, $from, $to) === 0) {
                $thieu[] = '«'.$from.'»';
            }
        }
        if ($thieu !== []) {
            throw new \RuntimeException('Không tìm thấy trong mẫu: '.implode(', ', $thieu));
        }

        if ($sua !== null) {
            $sua($xpath);
        }

        return $dom->saveXML() ?: $xml;
    }
}
