<?php

namespace Tests\Unit\QldtOto;

use App\Support\QldtOto\KtmBaoCao\KtmDocFileNhap;
use Tests\TestCase;

class KtmDocFileNhapTest extends TestCase
{
    public function test_mau_kiem_tra_lai_b_k55(): void
    {
        $path = base_path('docs/xuat-bao-cao/file_nhap/FILE_NHAP_mau.xlsx');
        if (! is_file($path)) {
            $this->markTestSkipped('Thiếu file mẫu FILE_NHAP_mau.xlsx');
        }

        $dl = KtmDocFileNhap::doc($path);
        $g = $dl->tongHopBTTml;

        $this->assertSame(34, $g->hoSo);
        $this->assertSame(33, $g->duThi);
        $this->assertSame(1, $g->vang);
        $this->assertSame(30, $g->ketQuaDat);
        $this->assertSame(10, $g->lt->tong);
        $this->assertSame(10, $g->lt->dat);
        $this->assertSame(19, $g->h->tong);
        $this->assertSame(16, $g->h->dat);
        $this->assertSame(3, $g->h->rot);
        $this->assertSame(10, $g->cc->tong);
        $this->assertSame(10, $g->cc->dat);
        $this->assertSame(23, $g->duong->tong);
        $this->assertSame(23, $g->duong->dat);
        $this->assertEqualsWithDelta(90.9, $g->tyLeDat('du_thi'), 0.05);
    }
}