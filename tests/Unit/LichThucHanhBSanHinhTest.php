<?php

namespace Tests\Unit;

use App\Support\DaoTao\LichThucHanh\BaiGiang;
use App\Support\DaoTao\LichThucHanh\CauHinhDefaults;
use App\Support\DaoTao\LichThucHanh\LichGenerator;
use Tests\TestCase;

class LichThucHanhBSanHinhTest extends TestCase
{
    public function test_bon_ngay_dau_hinh_voi_gio_sang_chieu_khac_nhau(): void
    {
        $cauHinh = CauHinhDefaults::khung('BTEST', 'B');
        $cauHinh['ngay_khai_giang'] = '2026-08-19';
        $cauHinh['ngay_ket_thuc_du_kien'] = '2026-10-04';
        $cauHinh['cap_xe'] = [[
            'stt' => 1,
            'bien_so' => '74A-212.82',
            'gv_sang' => '44007008',
            'gv_chieu' => '44007019',
            'ten_gv_sang' => 'GV SÁNG',
            'ten_gv_chieu' => 'GV CHIỀU',
        ]];

        $lich = (new LichGenerator)->generate($cauHinh)['lich'];
        $cells = $lich['cells'] ?? [];

        foreach (['2026-08-19', '2026-08-20', '2026-08-21'] as $iso) {
            $sang = $cells[$iso.'|44007008'] ?? [];
            $chieu = $cells[$iso.'|44007019'] ?? [];
            $this->assertSame(BaiGiang::HINH, $sang['mau'] ?? '', $iso.' sang');
            $this->assertSame(BaiGiang::HINH, $chieu['mau'] ?? '', $iso.' chieu');
            $this->assertSame('5H59\'', $sang['bat_dau'] ?? '');
            $this->assertSame('13H59\'', $sang['ket_thuc'] ?? '');
        }

        $cabin = $cells['2026-08-22|44007008'] ?? [];
        $this->assertSame(BaiGiang::CABIN, $cabin['mau'] ?? '');
        $this->assertSame('CABIN 1', $cabin['bai'] ?? '');
        $this->assertSame('7H', $cabin['bat_dau'] ?? '');
        $this->assertSame('17H', $cabin['ket_thuc'] ?? '');

        $tuDong = $cells['2026-08-23|44007008'] ?? [];
        $this->assertSame(BaiGiang::TU_DONG, $tuDong['mau'] ?? '');
        $this->assertSame('TỰ ĐỘNG 74A-452.04', $tuDong['bai'] ?? '');
        $this->assertSame('7H', $tuDong['bat_dau'] ?? '');
        $this->assertSame('12H', $tuDong['ket_thuc'] ?? '');

        $chieuCabin = $cells['2026-08-22|44007019'] ?? [];
        $this->assertSame(BaiGiang::CABIN, $chieuCabin['mau'] ?? '');
        $this->assertSame('CABIN 2', $chieuCabin['bai'] ?? '');
        $this->assertSame('7H', $chieuCabin['bat_dau'] ?? '');
        $this->assertSame('17H', $chieuCabin['ket_thuc'] ?? '');

        $chieuTuDong = $cells['2026-08-23|44007019'] ?? [];
        $this->assertSame(BaiGiang::TU_DONG, $chieuTuDong['mau'] ?? '');
        $this->assertSame('13H', $chieuTuDong['bat_dau'] ?? '');
        $this->assertSame('18H', $chieuTuDong['ket_thuc'] ?? '');

        $chieuPhucTap = $cells['2026-08-25|44007019'] ?? [];
        $this->assertSame(BaiGiang::PHUC_TAP, $chieuPhucTap['mau'] ?? '');
        $this->assertSame('5H58\'', $chieuPhucTap['bat_dau'] ?? '');
        $this->assertSame('13H58\'', $chieuPhucTap['ket_thuc'] ?? '');
    }
}
