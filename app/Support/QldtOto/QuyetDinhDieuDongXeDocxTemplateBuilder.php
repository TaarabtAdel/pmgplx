<?php

namespace App\Support\QldtOto;

/**
 * Mẫu gốc: resources/templates/oto/quyet-dinh-dieu-dong-xe-gvth.doc
 * Xuất: quyet-dinh-dieu-dong-xe-gvth.docx (1 trang / xe — cloneBlock XE_TRANG).
 */
class QuyetDinhDieuDongXeDocxTemplateBuilder
{
    public const DOC = 'templates/oto/quyet-dinh-dieu-dong-xe-gvth.doc';

    public const DOCX = 'templates/oto/quyet-dinh-dieu-dong-xe-gvth.docx';

    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** Đoạn cao 1pt — bảng chữ ký sát đáy trang, đoạn thường sẽ tràn sang trang trắng. */
    public const PARA_NGAT_TRANG = '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/><w:rPr><w:sz w:val="2"/><w:szCs w:val="2"/></w:rPr></w:pPr><w:r><w:rPr><w:sz w:val="2"/><w:szCs w:val="2"/></w:rPr><w:br w:type="page"/></w:r></w:p>';

    public const PARA_CUOI = '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/><w:rPr><w:sz w:val="2"/><w:szCs w:val="2"/></w:rPr></w:pPr></w:p>';

    public function build(?string $docPath = null, ?string $docxPath = null): string
    {
        $docPath ??= resource_path(self::DOC);
        $docxPath ??= resource_path(self::DOCX);

        if (! is_file($docPath)) {
            throw new \RuntimeException('Không tìm thấy mẫu .doc: '.$docPath);
        }

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'qd-dieu-dong-'.uniqid('', true);
        if (! @mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException('Không tạo được thư mục tạm.');
        }

        $tmpDocx = $tmpDir.DIRECTORY_SEPARATOR.'converted.docx';
        $this->convertDocToDocx($docPath, $tmpDocx);

        $zip = new \ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            throw new \RuntimeException('Không mở được file docx tạm.');
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $xml = $this->keepFirstQuyetDinhPage($xml);
        $xml = $this->applyPlaceholders($xml);
        $xml = $this->wrapCloneBlock($xml);
        $this->assertValidXml($xml);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        if (! @mkdir(dirname($docxPath), 0755, true) && ! is_dir(dirname($docxPath))) {
            throw new \RuntimeException('Không tạo được thư mục mẫu docx.');
        }
        if (! copy($tmpDocx, $docxPath)) {
            throw new \RuntimeException('Không ghi được: '.$docxPath);
        }

        $this->deleteDirectory($tmpDir);

        return $docxPath;
    }

    private function convertDocToDocx(string $docPath, string $docxPath): void
    {
        $outDir = dirname($docxPath);
        $cmd = sprintf(
            'soffice --headless --convert-to docx --outdir %s %s 2>&1',
            escapeshellarg($outDir),
            escapeshellarg($docPath)
        );
        exec($cmd, $output, $code);
        $base = pathinfo($docPath, PATHINFO_FILENAME);
        $generated = $outDir.DIRECTORY_SEPARATOR.$base.'.docx';

        if ($code !== 0 || ! is_file($generated)) {
            $cmdMac = sprintf(
                'textutil -convert docx -output %s %s 2>&1',
                escapeshellarg($docxPath),
                escapeshellarg($docPath)
            );
            exec($cmdMac, $outMac, $codeMac);
            if ($codeMac !== 0 || ! is_file($docxPath)) {
                throw new \RuntimeException(
                    'Không chuyển được .doc sang .docx (cần LibreOffice hoặc textutil trên macOS).'
                    ."\n".implode("\n", array_merge($output, $outMac ?? []))
                );
            }

            return;
        }

        if ($generated !== $docxPath && ! rename($generated, $docxPath)) {
            copy($generated, $docxPath);
        }
    }

    /**
     * Mẫu gốc chứa nhiều quyết định liên tiếp — chỉ giữ các phần tử cấp cao nhất của w:body
     * trước phần tử chứa quyết định thứ hai (bảng chữ ký không bị cắt ngang).
     */
    private function keepFirstQuyetDinhPage(string $xml): string
    {
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            libxml_use_internal_errors($prev);
            throw new \RuntimeException('XML mẫu gốc không hợp lệ.');
        }
        libxml_use_internal_errors($prev);

        $body = $dom->getElementsByTagNameNS(self::W_NS, 'body')->item(0);
        if ($body === null) {
            throw new \RuntimeException('Không tìm thấy w:body trong mẫu.');
        }

        $children = [];
        foreach ($body->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child;
            }
        }

        $soQdPattern = '/\d+\/QĐ-TTML/u';
        $soQdGap = 0;
        $removing = false;
        foreach ($children as $child) {
            if ($child->localName === 'sectPr') {
                continue;
            }
            if ($removing) {
                $body->removeChild($child);
                continue;
            }
            $count = preg_match_all($soQdPattern, $child->textContent);
            if ($soQdGap + $count < 2) {
                $soQdGap += $count;
                continue;
            }

            $removing = true;
            if ($child->localName === 'tbl' && $soQdGap >= 1) {
                // LibreOffice gộp bảng chữ ký trang 1 với bảng tiêu đề trang 2 — chỉ bỏ các dòng từ tiêu đề trang 2.
                $dropRow = false;
                foreach (iterator_to_array($child->childNodes) as $row) {
                    if (! $row instanceof \DOMElement || $row->localName !== 'tr') {
                        continue;
                    }
                    if (! $dropRow && preg_match($soQdPattern, $row->textContent) === 1) {
                        $dropRow = true;
                    }
                    if ($dropRow) {
                        $child->removeChild($row);
                    }
                }
            } else {
                $body->removeChild($child);
            }
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        foreach ($xpath->query('//w:body/w:p/w:pPr/w:sectPr') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        $this->botDongTrongChuKy($xpath);

        return $dom->saveXML() ?: $xml;
    }

    /**
     * Trang đầy sát lề dưới — bỏ 1 dòng trống giữa chức danh và tên người ký để không tràn sang trang trắng.
     */
    private function botDongTrongChuKy(\DOMXPath $xpath): void
    {
        $cell = $xpath->query('//w:tc[.//w:t[contains(., "Trương Hoàng Linh")]]')?->item(0);
        if ($cell === null) {
            return;
        }

        $empties = [];
        foreach ($xpath->query('w:p', $cell) ?: [] as $p) {
            if (str_contains($p->textContent, 'Trương Hoàng Linh')) {
                break;
            }
            if (trim($p->textContent) === '' && $xpath->query('.//w:drawing|.//w:pict', $p)?->length === 0) {
                $empties[] = $p;
            } else {
                $empties = [];
            }
        }

        if (count($empties) >= 3) {
            $empties[0]->parentNode?->removeChild($empties[0]);
        }
    }

    private function applyPlaceholders(string $xml): string
    {
        $xml = preg_replace('/<w:instrText[^>]*>[^<]*<\/w:instrText>/', '', $xml) ?? $xml;
        $xml = preg_replace('~<w:fldChar\b[^>]*(?:/>|>.*?</w:fldChar>)~s', '', $xml) ?? $xml;

        $replacements = [
            '640/QĐ-TTML' => '${SO_QD}',
            'Quảng Trị, ngày 16 tháng 09 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'V/v Điều động xe tập lái số 74A-143.24 phục vụ giảng dạy thực hành lái xe trên đường trường Hạng B (Số sàn) khóa 56' => 'V/v Điều động xe tập lái số ${BIEN_SO} phục vụ giảng dạy thực hành lái xe trên đường trường ${HANG_KHOA}',
            'Hạng B (Số sàn) khóa 56' => '${HANG_KHOA}',
            '74A-143.24' => '${BIEN_SO}',
            '0005/XTL-26-3 ngày 10/4/2026' => '${GP_XE_TEXT}',
            'TOYOTA' => '${NHAN_HIEU}',
            'Ô tô con' => '${LOAI_XE}',
            'Bạc' => '${MAU_SON}',
            'Nguyễn Kiều Hưng và Nguyễn Ngọc Hải' => '${GV_NHAN_XE}',
            'từ ngày 06/10/2026 đến 21/11/2026' => 'từ ngày ${TU_NGAY} đến ${DEN_NGAY}',
        ];

        foreach ($replacements as $from => $to) {
            $xml = str_replace($from, $to, $xml);
        }

        return $xml;
    }

    private function wrapCloneBlock(string $xml): string
    {
        if (! preg_match('/<w:body[^>]*>(.*)(<w:sectPr\b.*?<\/w:sectPr>\s*)<\/w:body>/s', $xml, $m)) {
            throw new \RuntimeException('Không tách được nội dung w:body để gắn cloneBlock.');
        }

        $inner = trim($m[1]);
        $sect = $m[2];
        $openBlock = '<w:p><w:r><w:t xml:space="preserve">${XE_TRANG}</w:t></w:r></w:p>';
        $closeBlock = '<w:p><w:r><w:t xml:space="preserve">${/XE_TRANG}</w:t></w:r></w:p>';
        $wrapped = $openBlock.$inner.self::PARA_NGAT_TRANG.$closeBlock.$sect;

        return preg_replace(
            '/<w:body[^>]*>.*<\/w:body>/s',
            '<w:body>'.$wrapped.'</w:body>',
            $xml,
            1
        ) ?? $xml;
    }

    private function assertValidXml(string $xml): void
    {
        $prev = libxml_use_internal_errors(true);
        $ok = (new \DOMDocument())->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (! $ok) {
            $msg = $errors !== [] ? trim($errors[0]->message) : 'không rõ';
            throw new \RuntimeException('XML mẫu sau khi gắn placeholder không hợp lệ: '.$msg);
        }
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
