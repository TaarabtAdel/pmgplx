<?php

namespace Tests\Unit;

use App\Support\DaoTao\HangSatHachMatch;
use PHPUnit\Framework\TestCase;

class HangSatHachMatchTest extends TestCase
{
    public function test_b11_equals_b_dot_01_and_file_b01(): void
    {
        $this->assertTrue(HangSatHachMatch::matches('B01', 'B11', null));
        $this->assertTrue(HangSatHachMatch::matches('B01', null, 'B.01'));
        $this->assertTrue(HangSatHachMatch::matches('B11', 'B11', 'B.01'));
    }

    public function test_b1_equals_hang_dao_tao_b(): void
    {
        $this->assertTrue(HangSatHachMatch::matches('B', 'B1', 'B'));
        $this->assertTrue(HangSatHachMatch::matches('B', null, 'B'));
        $this->assertTrue(HangSatHachMatch::matches('B1', 'B1', null));
    }

    public function test_manual_b_does_not_match_b11(): void
    {
        $this->assertFalse(HangSatHachMatch::matches('B', 'B11', 'B.01'));
        $this->assertFalse(HangSatHachMatch::matches('B01', 'B1', 'B'));
    }

    public function test_b1_is_not_alias_of_b11(): void
    {
        $this->assertFalse(HangSatHachMatch::matches('B01', 'B1', null));
    }
}
