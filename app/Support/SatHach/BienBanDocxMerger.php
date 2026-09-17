<?php

namespace App\Support\SatHach;

use RuntimeException;
use ZipArchive;

/**
 * Gộp nhiều file DOCX (mỗi biên bản 1 file) thành 1 Word nhiều trang (altChunk).
 * Mở bằng Microsoft Word sẽ bung đủ trang, ảnh, định dạng.
 */
class BienBanDocxMerger
{
    /**
     * @param  list<string>  $pageDocxPaths
     */
    public function merge(array $pageDocxPaths, string $outputPath): void
    {
        $files = [];
        foreach ($pageDocxPaths as $path) {
            if (is_string($path) && is_file($path)) {
                $files[] = $path;
            }
        }

        if ($files === []) {
            throw new RuntimeException('Không có file Word để gộp.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        if (count($files) === 1) {
            if (! @copy($files[0], $outputPath)) {
                throw new RuntimeException('Không ghi được file Word tổng.');
            }

            return;
        }

        $tmp = $outputPath.'.building.docx';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file Word tổng.');
        }

        $rels = [];
        $chunks = [];
        $breaks = [];

        foreach ($files as $i => $file) {
            $n = $i + 1;
            $rid = 'rId'.$n;
            $name = sprintf('chunks/c%03d.docx', $n);
            $zip->addFile($file, 'word/'.$name);
            $rels[] = '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/aFChunk" Target="'.$name.'"/>';
            if ($i > 0) {
                $breaks[] = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            }
            $chunks[] = '<w:altChunk r:id="'.$rid.'"/>';
        }

        $body = '';
        foreach ($chunks as $i => $chunk) {
            if ($i > 0) {
                $body .= $breaks[$i - 1];
            }
            $body .= $chunk;
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->packageRels());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRels(implode('', $rels)));
        $zip->addFromString('word/document.xml', $this->documentXml($body));
        $zip->close();

        @unlink($outputPath);
        if (! @rename($tmp, $outputPath) && ! @copy($tmp, $outputPath)) {
            @unlink($tmp);
            throw new RuntimeException('Không lưu được file Word tổng.');
        }
        @unlink($tmp);
    }

    private function contentTypes(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Default Extension="docx" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML;
    }

    private function packageRels(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;
    }

    private function documentRels(string $inner): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$inner
            .'</Relationships>';
    }

    private function documentXml(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>'
            .'</w:body></w:document>';
    }
}
