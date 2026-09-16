<?php

namespace Tests\Unit;

use App\Support\SatHach\ImageConverterService;
use Tests\TestCase;

class ImageConverterServiceTest extends TestCase
{
    public function test_empty_base64_falls_back_to_placeholder(): void
    {
        $dir = sys_get_temp_dir().'/img-conv-'.uniqid('', true);
        $path = (new ImageConverterService())->portraitPath('', $dir);

        $this->assertSame(resource_path('templates/no-photo.png'), $path);
    }

    public function test_jpeg_portrait_is_written_to_workdir(): void
    {
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGP/9k=',
            true
        );
        $this->assertNotFalse($jpeg);

        $dir = sys_get_temp_dir().'/img-conv-'.uniqid('', true);
        $path = (new ImageConverterService())->portraitPath(base64_encode($jpeg), $dir);

        $this->assertFileExists($path);
        $this->assertNotSame(resource_path('templates/no-photo.png'), $path);
        $info = getimagesize($path);
        $this->assertIsArray($info);
        $this->assertSame('image/jpeg', $info['mime']);
    }
}
