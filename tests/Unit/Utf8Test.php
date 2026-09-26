<?php

namespace Tests\Unit;

use App\Support\SatHach\Utf8;
use Tests\TestCase;

class Utf8Test extends TestCase
{
    public function test_sanitize_keeps_valid_utf8_vietnamese(): void
    {
        $this->assertSame('Nguyễn Văn A', Utf8::sanitize('Nguyễn Văn A'));
    }

    public function test_sanitize_converts_cp1258_bytes_without_mb_detect_encoding(): void
    {
        $bytes = iconv('UTF-8', 'CP1258//IGNORE', 'Nguyễn');
        $this->assertNotFalse($bytes);
        $this->assertSame('Nguyễn', Utf8::sanitize($bytes));
    }

    public function test_sanitize_does_not_throw_on_arbitrary_binary(): void
    {
        $this->assertIsString(Utf8::sanitize("\xFF\xFE\x00\x01"));
    }
}
