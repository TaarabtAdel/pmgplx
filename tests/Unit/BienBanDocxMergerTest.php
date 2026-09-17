<?php

namespace Tests\Unit;

use App\Support\SatHach\BienBanDocxMerger;
use Tests\TestCase;

class BienBanDocxMergerTest extends TestCase
{
    public function test_merges_two_docx_into_one_altchunk_file(): void
    {
        $template = resource_path('templates/bien_ban_tong_hop.docx');
        $this->assertFileExists($template);

        $dir = sys_get_temp_dir().'/bb-merge-'.uniqid('', true);
        @mkdir($dir, 0755, true);
        $a = $dir.'/a.docx';
        $b = $dir.'/b.docx';
        $out = $dir.'/out.docx';
        copy($template, $a);
        copy($template, $b);

        (new BienBanDocxMerger())->merge([$a, $b], $out);

        $this->assertFileExists($out);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($out));
        $xml = (string) $zip->getFromName('word/document.xml');
        $this->assertStringContainsString('w:altChunk', $xml);
        $this->assertNotFalse($zip->locateName('word/chunks/c001.docx'));
        $this->assertNotFalse($zip->locateName('word/chunks/c002.docx'));
        $zip->close();

        @unlink($a);
        @unlink($b);
        @unlink($out);
        @rmdir($dir);
    }
}
