<?php

namespace Tests\Unit;

use App\Support\SatHach\BienBanDocxMerger;
use Tests\TestCase;
use ZipArchive;

class BienBanDocxMergerTest extends TestCase
{
    public function test_merges_two_docx_bodies_without_altchunk(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->assertNotFalse($png);

        $dir = sys_get_temp_dir().'/bb-merge-'.uniqid('', true);
        @mkdir($dir, 0755, true);
        $a = $dir.'/a.docx';
        $b = $dir.'/b.docx';
        $out = $dir.'/out.docx';

        $this->writeSimpleDocx($a, 'PAGE_A', null);
        $this->writeSimpleDocx($b, 'PAGE_B', $png);

        config(['services.word_merge.disable' => true]);
        (new BienBanDocxMerger())->merge([$a, $b], $out);

        $this->assertFileExists($out);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($out));
        $xml = (string) $zip->getFromName('word/document.xml');
        $rels = (string) $zip->getFromName('word/_rels/document.xml.rels');
        $this->assertStringNotContainsString('w:altChunk', $xml);
        $this->assertStringContainsString('PAGE_A', $xml);
        $this->assertStringContainsString('PAGE_B', $xml);
        $this->assertStringContainsString('w:type="page"', $xml);
        $this->assertStringContainsString('Target="media/', $rels);
        $this->assertFalse($zip->locateName('word/chunks/c001.docx'));

        $mediaCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_starts_with($name, 'word/media/')) {
                $mediaCount++;
            }
        }
        $this->assertGreaterThanOrEqual(1, $mediaCount);
        $zip->close();

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml));

        @unlink($a);
        @unlink($b);
        @unlink($out);
        @rmdir($dir);
    }

    public function test_merges_generated_bien_ban_into_well_formed_xml(): void
    {
        $dir = sys_get_temp_dir().'/bb-gen-merge-'.uniqid('', true);
        @mkdir($dir, 0755, true);
        $row = [
            'so_bao_danh' => 'A01',
            'ho_va_ten' => 'Nguyen Van A',
            'ngay_sinh' => '01/01/2000',
            'so_cmt' => '123',
            'so_ho_chieu' => '',
            'ngay_cap_hc' => '',
            'noi_cap_hc' => '',
            'hang_gplx' => 'B1',
            'diem_lt_toida' => '30',
            'diem_lt_dat' => '30',
            'nhan_xet_lt' => 'Dat',
            'diem_hinh_dat' => '-',
            'nhan_xet_hinh' => '',
            'diem_duong_dat' => '-',
            'nhan_xet_duong' => '',
            'ket_qua_text' => 'Dat',
            'ngay_ky' => '1',
            'thang_ky' => '1',
            'nam_ky' => '2026',
            'anh_chan_dung_b64' => null,
        ];
        $a = $dir.'/a.docx';
        $b = $dir.'/b.docx';
        $out = $dir.'/out.docx';
        $g = new \App\Support\SatHach\BienBanDocxGenerator();
        $g->generateOne($row, $a);
        $row['ho_va_ten'] = 'Tran Thi B';
        $row['so_bao_danh'] = 'A02';
        $g->generateOne($row, $b);

        config(['services.word_merge.disable' => true]);
        (new BienBanDocxMerger())->merge([$a, $b], $out);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($out));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('Nguyen Van A', $xml);
        $this->assertStringContainsString('Tran Thi B', $xml);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), implode('; ', array_map(
            static fn (\LibXMLError $e): string => trim($e->message),
            libxml_get_errors()
        )));

        @unlink($a);
        @unlink($b);
        @unlink($out);
        @rmdir($dir);
    }

    private function writeSimpleDocx(string $path, string $text, ?string $png): void
    {
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $drawing = '';
        if ($png !== null) {
            $rels .= '<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/photo.png"/>';
            $drawing = '<w:r><w:drawing><wp:inline><wp:docPr id="1" name="Picture 1"/>'
                .'<a:graphic><a:graphicData><pic:pic><pic:blipFill><a:blip r:embed="rId5"/></pic:blipFill></pic:pic></a:graphicData></a:graphic>'
                .'</wp:inline></w:drawing></w:r>';
        }
        $rels .= '</Relationships>';

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            .' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            .' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            .' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<w:body><w:p><w:r><w:t>'.$text.'</w:t></w:r>'.$drawing.'</w:p>'
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/></w:sectPr></w:body></w:document>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>';

        $pkgRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $pkgRels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/_rels/document.xml.rels', $rels);
        if ($png !== null) {
            $zip->addFromString('word/media/photo.png', $png);
        }
        $zip->close();
    }
}
