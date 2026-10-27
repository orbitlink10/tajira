<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageService
{
    public const MAX_BYTES = 8_000_000;

    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return array{path:string,width:int,height:int,mime:string,size:int}
     */
    public function storeProductImage(UploadedFile $file, int $productId, ?string $alt = null): array
    {
        $this->assertSafe($file);

        $directory = 'products/'.$productId;
        Storage::disk('public')->makeDirectory($directory);

        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $basename = $basename.'-'.Str::lower(Str::random(8));

        return $this->convertToWebp($file->getRealPath(), $directory, $basename);
    }

    /**
     * @return array{path:string,width:int,height:int,mime:string,size:int}
     */
    public function convertToWebp(string $sourcePath, string $directory, string $basename): array
    {
        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded image.');
        }

        $image = @imagecreatefromstring($contents);

        if (! $image) {
            throw new RuntimeException('The image could not be processed. Upload a JPEG, PNG or WebP image.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 1800;

        if ($width > $max || $height > $max) {
            $ratio = min($max / $width, $max / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }

        $path = $directory.'/'.$basename.'.webp';
        $fullPath = Storage::disk('public')->path($path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        if (! imagewebp($image, $fullPath, 82)) {
            imagedestroy($image);

            throw new RuntimeException('Unable to write the optimised image.');
        }

        imagedestroy($image);

        return [
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'mime' => 'image/webp',
            'size' => (int) filesize($fullPath),
        ];
    }

    public function assertSafe(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The upload failed. Please try again.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('Images must be smaller than 8 MB.');
        }

        $mime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($mime, self::ALLOWED_MIMES, true) || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new RuntimeException('Only JPEG, PNG and WebP images are accepted.');
        }

        $dimensions = @getimagesize($file->getRealPath());

        if (! $dimensions || $dimensions[0] < 10 || $dimensions[1] < 10 || $dimensions[0] > 10000 || $dimensions[1] > 10000) {
            throw new RuntimeException('The image dimensions are not valid.');
        }
    }
}
