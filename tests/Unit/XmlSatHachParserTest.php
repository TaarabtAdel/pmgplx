<?php

namespace Tests\Unit;

use App\Support\SatHach\BienBanDocxGenerator;
use App\Support\SatHach\XmlSatHachParser;
use Tests\TestCase;

class XmlSatHachParserTest extends TestCase
{
    public function test_parses_sat_hach_candidates_and_formats_fields(): void
    {
        $parsed = (new XmlSatHachParser())->parse(base_path('tests/fixtures/sat-hach-mini.xml'));

        $this->assertSame('2', $parsed['header']['TONG_SO_BAN_GHI']);
        $this->assertCount(2, $parsed['thi_sinh']);
        $this->assertSame(1, $parsed['thi_sinh'][0]['so_tt']);
        $this->assertSame('HỒ THỊ SÁU', $parsed['thi_sinh'][0]['ho_va_ten']);
        $this->assertSame('30/04/1987', $parsed['thi_sinh'][0]['ngay_sinh']);
        $this->assertSame('106', $parsed['thi_sinh'][0]['so_bao_danh']);
        $this->assertSame('28', $parsed['thi_sinh'][0]['diem_lt_dat']);
        $this->assertSame('30', $parsed['thi_sinh'][0]['diem_lt_toida']);
        $this->assertSame('35', $parsed['thi_sinh'][1]['diem_lt_toida']);
        $this->assertSame('-', $parsed['thi_sinh'][0]['diem_hinh_dat']);
        $this->assertSame('25', $parsed['thi_sinh'][0]['ngay_ky']);
        $this->assertSame('07', $parsed['thi_sinh'][0]['thang_ky']);
        $this->assertSame('2026', $parsed['thi_sinh'][0]['nam_ky']);
        $this->assertStringContainsString('☑', $parsed['thi_sinh'][0]['ket_qua_text']);
        $this->assertSame('NGUYỄN VĂN A', $parsed['thi_sinh'][1]['ho_va_ten']);
        $this->assertStringContainsString('Không đạt ☑', $parsed['thi_sinh'][1]['ket_qua_text']);
    }

    public function test_diem_lt_toi_da_theo_hang(): void
    {
        $this->assertSame('30', XmlSatHachParser::diemLtToiDa('B'));
        $this->assertSame('30', XmlSatHachParser::diemLtToiDa('b.01'));
        $this->assertSame('35', XmlSatHachParser::diemLtToiDa('C1'));
        $this->assertSame('', XmlSatHachParser::diemLtToiDa('A1'));
    }

    public function test_rejects_wrong_root(): void
    {
        $path = sys_get_temp_dir().'/not-sat-hach.xml';
        file_put_contents($path, '<?xml version="1.0"?><DANG_KY_KHOA_HOC></DANG_KY_KHOA_HOC>');

        $this->expectException(\InvalidArgumentException::class);
        (new XmlSatHachParser())->parse($path);
    }

    public function test_generates_docx_for_two_candidates(): void
    {
        $parsed = (new XmlSatHachParser())->parse(base_path('tests/fixtures/sat-hach-mini.xml'));
        $out = sys_get_temp_dir().'/bien-ban-test-'.uniqid('', true).'.docx';

        (new BienBanDocxGenerator())->generate($parsed['thi_sinh'], $out);

        $this->assertFileExists($out);
        $this->assertGreaterThan(1000, filesize($out));

        $xml = $this->docxMainXml($out);
        $this->assertStringContainsString('>30<', $xml);
        $this->assertStringContainsString('>35<', $xml);
        $this->assertStringNotContainsString('DIEM_LT_TOIDA', $xml);
        @unlink($out);
    }

    private function docxMainXml(string $path): string
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }
}
