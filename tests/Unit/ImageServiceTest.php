<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    public function test_indexed_png_can_be_converted_to_webp_with_transparency(): void
    {
        Storage::fake('public');
        $source = tempnam(sys_get_temp_dir(), 'palette-test-');
        $image = imagecreate(20, 20);
        $transparent = imagecolorallocate($image, 255, 255, 255);
        imagecolortransparent($image, $transparent);
        $color = imagecolorallocate($image, 0, 128, 128);
        imagefilledrectangle($image, 5, 5, 15, 15, $color);
        imagepng($image, $source);
        imagedestroy($image);

        try {
            $result = app(ImageService::class)->convertToWebp($source, 'products/1', 'indexed');
            $output = imagecreatefromwebp(Storage::disk('public')->path($result['path']));
            $this->assertSame(20, imagesx($output));
            $this->assertSame(127, imagecolorsforindex($output, imagecolorat($output, 0, 0))['alpha']);
            $this->assertGreaterThan(0, $result['size']);
            imagedestroy($output);
        } finally {
            unlink($source);
        }
    }
}
