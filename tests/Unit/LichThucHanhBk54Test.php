<?php

namespace Tests\Unit;

use App\Support\DaoTao\LichThucHanh\LichThucHanhGeneratedMetrics;
use App\Support\DaoTao\LichThucHanh\LichThucHanhMetricsDiff;
use App\Support\DaoTao\LichThucHanh\LichThucHanhReferenceMetrics;
use App\Support\DaoTao\LichThucHanh\LichGenerator;
use App\Support\DaoTao\LichThucHanh\LichValidator;
use PHPUnit\Framework\TestCase;

/** Đối chiếu generator với file Excel fixture (một khóa mẫu trong tests/fixtures). */
class LichThucHanhBk54Test extends TestCase
{
    private function fixturePath(): string
    {
        return dirname(__DIR__).'/fixtures/bk54_cau_hinh.json';
    }

    private function xlsxPath(): string
    {
        return dirname(__DIR__).'/fixtures/LI_CH_NHA__P_BK54.xlsx';
    }

    /** @return array<string, mixed> */
    private function loadCauHinh(): array
    {
        $raw = json_decode((string) file_get_contents($this->fixturePath()), true);

        return is_array($raw) ? $raw : [];
    }

    public function test_generator_matches_bk54_oracle_metrics(): void
    {
        $cauHinh = $this->loadCauHinh();
        $this->assertNotEmpty($cauHinh['ke_hoach_theo_gv'] ?? null);

        $gen = (new LichGenerator)->generate($cauHinh);
        $lich = $gen['lich'];

        $this->assertSame('2026-10-04', $lich['meta']['ngay_ket_thuc_tinh'] ?? null);

        $expected = (new LichThucHanhReferenceMetrics)->fromPath($this->xlsxPath());
        $actual = LichThucHanhGeneratedMetrics::fromLich($lich);

        $diff = (new LichThucHanhMetricsDiff)->diff($expected, $actual);
        if ($diff !== []) {
            $this->fail((new LichThucHanhMetricsDiff)->formatTable($diff));
        }

        $gv1 = $actual['44007008'] ?? null;
        $this->assertNotNull($gv1);
        $this->assertSame('2026-08-22', $gv1['cabin_ngay']);
        $this->assertSame(['2026-08-23', '2026-08-24'], $gv1['auto_block']);
        $this->assertSame('2026-10-04', $gv1['ngay_cuoi']);
    }

    public function test_validator_has_no_errors_on_bk54_preset(): void
    {
        $cauHinh = $this->loadCauHinh();
        $gen = (new LichGenerator)->generate($cauHinh);
        $result = LichValidator::validate($cauHinh, $gen['lich'], $gen['tom_tat']);

        $this->assertSame([], $result['errors'] ?? [], 'Validator errors: '.json_encode($result['errors'] ?? [], JSON_UNESCAPED_UNICODE));
    }
}
