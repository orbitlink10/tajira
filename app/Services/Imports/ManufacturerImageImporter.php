<?php

namespace App\Services\Imports;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use RuntimeException;

class ManufacturerImageImporter
{
    public function __construct(
        private readonly SafeUrlFetcher $fetcher,
        private readonly ImageService $images,
    ) {}

    /**
     * @param  array{sku:string,manufacturer_url?:string,image_url?:string,alt?:string,credit?:string,rights_note?:string}  $row
     */
    public function import(array $row): ProductImage
    {
        $product = Product::query()->where('sku', trim((string) ($row['sku'] ?? '')))->first();

        if (! $product) {
            throw new RuntimeException('Product SKU not found: '.($row['sku'] ?? ''));
        }

        $imageUrl = trim((string) ($row['image_url'] ?? ''));
        $manufacturerUrl = trim((string) ($row['manufacturer_url'] ?? ''));

        if ($imageUrl === '') {
            if ($manufacturerUrl === '') {
                throw new RuntimeException('Provide image_url or manufacturer_url.');
            }

            $imageUrl = $this->discoverImageUrl($manufacturerUrl);
        }

        if ($imageUrl === '') {
            throw new RuntimeException('No product image could be found on the manufacturer page.');
        }

        if (ProductImage::query()->where('product_id', $product->id)->where('source_url', $imageUrl)->exists()) {
            throw new RuntimeException('This image has already been attached to the product.');
        }

        $response = $this->fetcher->fetch($imageUrl, allowImage: true, enforceAllowlist: false);
        $contentType = strtolower((string) ($response['headers']['content-type'] ?? ''));

        if (! str_contains($contentType, 'image/jpeg')
            && ! str_contains($contentType, 'image/png')
            && ! str_contains($contentType, 'image/webp')) {
            throw new RuntimeException('The image URL did not return a JPEG, PNG or WebP image.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'tajira-manufacturer-image-');

        if ($temporary === false) {
            throw new RuntimeException('Could not create a temporary image file.');
        }

        file_put_contents($temporary, $response['body']);
        $basename = Str::slug($product->sku).'-'.Str::lower(Str::random(6));

        try {
            $result = $this->images->convertToWebp($temporary, 'products/'.$product->id, $basename);
        } finally {
            @unlink($temporary);
        }

        $makePrimary = ! $product->images()->exists();

        if ($makePrimary) {
            $product->images()->update(['is_primary' => false]);
        }

        return ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $result['path'],
            'alt' => $row['alt'] ?: $product->name,
            'title' => $product->name,
            'width' => $result['width'],
            'height' => $result['height'],
            'size_bytes' => $result['size'],
            'mime' => $result['mime'],
            'source_url' => $imageUrl,
            'credit' => $row['credit'] ?? null,
            'rights_note' => $row['rights_note'] ?? null,
            'is_primary' => $makePrimary,
            'position' => (int) ($product->images()->max('position') ?? 0) + 1,
        ]);
    }

    public function discoverImageUrl(string $manufacturerUrl): string
    {
        $response = $this->fetcher->fetch($manufacturerUrl, enforceAllowlist: false, throttle: false);
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>'.$response['body'], LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $ogImage = $xpath->query('//meta[@property="og:image"]/@content')->item(0)?->nodeValue
            ?: $xpath->query('//meta[@name="twitter:image"]/@content')->item(0)?->nodeValue;

        if ($ogImage) {
            return $this->resolveUrl($manufacturerUrl, trim($ogImage));
        }

        foreach ($document->getElementsByTagName('script') as $script) {
            if (strtolower($script->getAttribute('type')) !== 'application/ld+json') {
                continue;
            }

            $decoded = json_decode(trim($script->textContent), true);

            if (! is_array($decoded)) {
                continue;
            }

            $image = $decoded['image'] ?? null;

            if (is_string($image)) {
                return $this->resolveUrl($manufacturerUrl, $image);
            }

            if (is_array($image)) {
                $candidate = $image[0] ?? null;

                if (is_array($candidate)) {
                    $candidate = $candidate['url'] ?? null;
                }

                if (is_string($candidate)) {
                    return $this->resolveUrl($manufacturerUrl, $candidate);
                }
            }
        }

        return '';
    }

    private function resolveUrl(string $base, string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($url, '//')) {
            return $scheme.':'.$url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme.'://'.$host.$url;
        }

        $path = $parts['path'] ?? '/';
        $directory = substr($path, 0, strrpos($path, '/') + 1);

        return $scheme.'://'.$host.$directory.$url;
    }
}
