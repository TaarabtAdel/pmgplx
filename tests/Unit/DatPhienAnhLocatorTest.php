<?php

namespace Tests\Unit;

use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatAnhDuongDan;
use App\Support\DaoTao\DatPhienAnhLocator;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatPhienAnhLocatorTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/dat-anh-'.uniqid('', true);
        File::makeDirectory($this->basePath.'/KH01/HV01/09/06', 0755, true);
        File::put($this->basePath.'/KH01/HV01/09/06/11-13-59.jpg', 'skip');
        File::put($this->basePath.'/KH01/HV01/09/06/11-14-00.jpg', 'in');
        File::put($this->basePath.'/KH01/HV01/09/06/11-20-10.jpg', 'in');
        File::put($this->basePath.'/KH01/HV01/09/06/11-34-59.jpg', 'in');
        File::put($this->basePath.'/KH01/HV01/09/06/11-35-00.jpg', 'skip');
        File::put($this->basePath.'/KH01/HV01/09/06/11-14-00.xml', 'ignore');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_lists_images_within_session_minute_window(): void
    {
        $phien = new DatDSPhien();
        $phien->Id = 9;
        $phien->MaKhoaHoc = 'KH01';
        $phien->MaHocVien = 'HV01';
        $phien->ThoiGianBatDauPhienHoc = Carbon::parse('2026-09-06 11:14:40');
        $phien->ThoiGianKetThucPhienHoc = Carbon::parse('2026-09-06 11:34:00');

        $result = (new DatPhienAnhLocator())->forPhien($phien, $this->basePath);

        $this->assertTrue($result['ok']);
        $this->assertSame(3, $result['so_anh']);
        $this->assertSame(
            ['11-14-00.jpg', '11-20-10.jpg', '11-34-59.jpg'],
            array_column($result['anh'], 'ten')
        );
    }

    public function test_skips_missing_files_and_unknown_folder(): void
    {
        $phien = new DatDSPhien();
        $phien->Id = 10;
        $phien->MaKhoaHoc = 'KH01';
        $phien->MaHocVien = 'KHONG-CO';
        $phien->ThoiGianBatDauPhienHoc = Carbon::parse('2026-09-06 11:14:00');
        $phien->ThoiGianKetThucPhienHoc = Carbon::parse('2026-09-06 11:59:00');

        $result = (new DatPhienAnhLocator())->forPhien($phien, $this->basePath);

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['so_anh']);
    }

    public function test_accepts_windows_backslash_and_forward_slash(): void
    {
        $this->assertSame(
            'E:/DAT_ETM/PhanMem/ServerCenter/ImageFilesThuNghiem/2026',
            DatAnhDuongDan::normalize('E:\\DAT_ETM\\PhanMem\\ServerCenter\\ImageFilesThuNghiem\\2026\\')
        );
        $this->assertSame(
            '/Users/tpt/Downloads/image-logs',
            DatAnhDuongDan::normalize('/Users/tpt/Downloads/image-logs/')
        );
    }
}
