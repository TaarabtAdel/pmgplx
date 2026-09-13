<?php

namespace Tests\Unit;

use App\Support\DaoTao\DatGpsCoordinateExtractor;
use App\Support\DaoTao\DatNominatimReverseGeocode;
use App\Support\DaoTao\DatTuyenDuongGpsMatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DatTuyenDuongGpsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function fakeNominatimSampleResponse(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Đường mẫu, Phường mẫu, Quảng Trị, Việt Nam',
                'address' => [
                    'road' => 'Đường mẫu',
                    'suburb' => 'Phường mẫu',
                    'city' => 'Quảng Trị',
                ],
            ], 200),
        ]);
    }

    public function test_extracts_start_and_end_coordinates_from_segments(): void
    {
        $segments = [[
            'ListCoordinate' => [
                ['Latitude' => 16.803143, 'Longitude' => 107.10512],
                ['Latitude' => 16.801816, 'Longitude' => 107.108379],
            ],
        ]];

        $endpoints = DatGpsCoordinateExtractor::endpoints(
            DatGpsCoordinateExtractor::collectPoints($segments)
        );

        $this->assertNotNull($endpoints);
        $this->assertSame(16.803143, $endpoints['start']['lat']);
        $this->assertSame(107.10512, $endpoints['start']['lng']);
        $this->assertSame(16.801816, $endpoints['end']['lat']);
        $this->assertSame(107.108379, $endpoints['end']['lng']);
    }

    public function test_analyze_returns_list_coordinate_for_map(): void
    {
        $this->fakeNominatimSampleResponse();

        $matcher = new DatTuyenDuongGpsMatcher();
        $analysis = $matcher->analyze([[
            'StartTime' => '2026-09-12T08:08:05',
            'EndTime' => '2026-09-12T09:21:21',
            'tongKm' => 53.59,
            'ListCoordinate' => [
                ['Latitude' => 16.803143, 'Longitude' => 107.10512, 'StrPoint' => '16.803143,107.10512'],
                ['Latitude' => 17.0701, 'Longitude' => 107.0012, 'StrPoint' => '17.0701,107.0012'],
            ],
        ]]);

        $this->assertSame(53.59, $analysis['tong_km']);
        $this->assertSame(2, $analysis['so_diem_gps']);
        $this->assertCount(2, $analysis['list_coordinate']);
        $this->assertSame('mẫu', $analysis['ten_diem_dau']);
        $this->assertSame('mẫu', $analysis['ten_diem_cuoi']);
        $this->assertSame('mẫu', $analysis['ten_tuyen_nominatim']);
        $this->assertSame('mẫu', $analysis['ten_tuyen_mac_dinh']);
    }

    public function test_prefers_route_name_from_segment_when_available(): void
    {
        $this->fakeNominatimSampleResponse();

        $matcher = new DatTuyenDuongGpsMatcher();
        $analysis = $matcher->analyze([[
            'TenTuyenDuong' => 'Đông Hà - Vĩnh Linh',
            'ListCoordinate' => [
                ['Latitude' => 16.803143, 'Longitude' => 107.10512],
            ],
        ]]);

        $this->assertSame('Đông Hà - Vĩnh Linh', $analysis['ten_tuyen_mac_dinh']);
        $this->assertSame('Đông Hà - Vĩnh Linh', $analysis['ten_tuyen_trong_api']);
    }

    public function test_nominatim_label_from_user_sample_coordinate(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Quốc lộ 1A, Xã Gio Linh, Huyện Gio Linh, Quảng Trị, Việt Nam',
                'address' => [
                    'road' => 'Quốc lộ 1A',
                    'village' => 'Xã Gio Linh',
                    'county' => 'Huyện Gio Linh',
                    'state' => 'Quảng Trị',
                ],
            ], 200),
        ]);

        $nominatim = new DatNominatimReverseGeocode();
        $label = $nominatim->labelForCoordinate(16.625002, 106.731118);

        $this->assertSame('Gio Linh', $label);
    }
}
