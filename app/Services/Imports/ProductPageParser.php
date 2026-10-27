<?php

namespace App\Services\Imports;

use App\Support\Money;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

class ProductPageParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $html, string $url): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \RuntimeException('The HTML page could not be parsed.');
        }

        $xpath = new DOMXPath($document);
        $jsonLd = $this->jsonLdProduct($document);
        $attributes = $this->attributes($xpath, $jsonLd);
        $name = $this->firstText($xpath, [
            '//h1[contains(@class,"product_title")]',
            '//h1',
        ]) ?: ($jsonLd['name'] ?? null) ?: $this->meta($xpath, 'og:title');
        $sku = $this->firstText($xpath, ['//*[contains(@class,"sku")]']) ?: ($jsonLd['sku'] ?? null);
        $brand = $this->brand($xpath, $jsonLd);
        $price = $this->price($xpath, $jsonLd);
        $categories = $this->categories($xpath, $jsonLd);
        $images = $this->images($xpath, $document, $jsonLd);
        $description = $this->htmlOf($document, $xpath, [
            '//*[@id="tab-description"]',
            '//*[contains(@class,"woocommerce-Tabs-panel--description")]',
        ]);
        $shortDescription = $this->htmlOf($document, $xpath, [
            '//*[contains(@class,"woocommerce-product-details__short-description")]',
        ]);
        $model = $this->attributeValue($attributes, ['model', 'model number', 'model no']);
        $mpn = $this->attributeValue($attributes, ['mpn', 'manufacturer part number', 'part number']) ?: $model;
        $gtin = $jsonLd['gtin13'] ?? $jsonLd['gtin12'] ?? $jsonLd['gtin8'] ?? $jsonLd['gtin14'] ?? $jsonLd['gtin'] ?? null;
        $externalId = null;
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body && preg_match('/postid-(\d+)/', (string) $document->saveHTML($body), $matches)) {
            $externalId = $matches[1];
        }

        $errors = [];

        if (! $name) {
            $errors[] = 'Product name could not be determined.';
        }

        return [
            'source_url' => $url,
            'external_id' => $externalId,
            'raw_name' => $name ? Str::limit(trim($name), 250, '') : null,
            'raw_brand' => $brand ? Str::limit(trim($brand), 120, '') : null,
            'raw_model' => $model ? Str::limit(trim($model), 160, '') : null,
            'raw_mpn' => $mpn ? Str::limit(trim($mpn), 160, '') : null,
            'raw_gtin' => $gtin ? preg_replace('/\D/', '', (string) $gtin) : null,
            'raw_price_minor' => $price['minor'],
            'raw_currency' => $price['currency'],
            'raw_categories' => $categories,
            'raw_attributes' => $attributes,
            'raw_description' => Str::limit($shortDescription."\n".$description, 150000, ''),
            'raw_image_urls' => $images,
            'errors' => $errors,
            'content_hash' => hash('sha256', json_encode([
                $name, $brand, $model, $mpn, $price['minor'], $categories, $attributes,
            ], JSON_UNESCAPED_UNICODE)),
        ];
    }

    private function jsonLdProduct(DOMDocument $document): array
    {
        $scripts = $document->getElementsByTagName('script');

        foreach ($scripts as $script) {
            if (strtolower($script->getAttribute('type')) !== 'application/ld+json') {
                continue;
            }

            $decoded = json_decode(trim($script->textContent), true);

            if (! is_array($decoded)) {
                continue;
            }

            $product = $this->findProduct($decoded);

            if ($product) {
                return $product;
            }
        }

        return [];
    }

    private function findProduct(array $data): ?array
    {
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $node) {
                if (is_array($node) && ($found = $this->findProduct($node))) {
                    return $found;
                }
            }
        }

        $type = $data['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];

        if (in_array('Product', $types, true) || in_array('ProductGroup', $types, true)) {
            return $data;
        }

        foreach ($data as $value) {
            if (is_array($value) && ($found = $this->findProduct($value))) {
                return $found;
            }
        }

        return null;
    }

    private function attributes(DOMXPath $xpath, array $jsonLd): array
    {
        $attributes = [];

        foreach ($xpath->query('//table[contains(@class,"woocommerce-product-attributes")]//tr') ?: [] as $row) {
            $label = trim($this->firstText($xpath, ['.//th'], $row));
            $value = trim($this->firstText($xpath, ['.//td'], $row));

            if ($label !== '' && $value !== '') {
                $attributes[Str::limit($label, 120, '')] = Str::limit($value, 1000, '');
            }
        }

        foreach ((array) ($jsonLd['additionalProperty'] ?? []) as $property) {
            if (! is_array($property)) {
                continue;
            }

            $label = $property['name'] ?? null;
            $value = $property['value'] ?? null;

            if ($label && $value !== null) {
                $attributes[Str::limit((string) $label, 120, '')] = Str::limit((string) $value, 1000, '');
            }
        }

        return $attributes;
    }

    private function attributeValue(array $attributes, array $labels): ?string
    {
        foreach ($attributes as $label => $value) {
            if (in_array(strtolower(trim($label)), $labels, true)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function brand(DOMXPath $xpath, array $jsonLd): ?string
    {
        $brand = $jsonLd['brand'] ?? null;

        if (is_array($brand)) {
            $brand = $brand['name'] ?? null;
        }

        return $brand ?: $this->firstText($xpath, [
            '//*[contains(@class,"pwb-brand")]//a',
            '//*[contains(@class,"product-brand")]//a',
        ]);
    }

    private function price(DOMXPath $xpath, array $jsonLd): array
    {
        $price = null;
        $currency = 'KES';
        $offers = $jsonLd['offers'] ?? null;

        if (is_array($offers)) {
            if (isset($offers[0]) && is_array($offers[0])) {
                $offers = $offers[0];
            }

            $price = $offers['price'] ?? null;
            $currency = $offers['priceCurrency'] ?? $currency;
        }

        if ($price === null) {
            $price = $this->meta($xpath, 'product:price:amount');
        }

        if ($price === null) {
            $meta = $xpath->query('//*[contains(@class,"woocommerce-Price-amount")]')->item(0);
            $price = $meta ? preg_replace('/[^0-9.]/', '', $meta->textContent) : null;
        }

        return [
            'minor' => $price !== null ? Money::parseToMinor((string) $price) : null,
            'currency' => strtoupper((string) $currency),
        ];
    }

    private function categories(DOMXPath $xpath, array $jsonLd): array
    {
        $categories = [];

        foreach ($xpath->query('//*[contains(@class,"posted_in")]//a') ?: [] as $link) {
            $text = trim($link->textContent);

            if ($text !== '') {
                $categories[] = $text;
            }
        }

        $jsonCategory = $jsonLd['category'] ?? null;

        if (is_string($jsonCategory)) {
            foreach (explode('>', $jsonCategory) as $piece) {
                $piece = trim($piece);

                if ($piece !== '') {
                    $categories[] = $piece;
                }
            }
        } elseif (is_array($jsonCategory)) {
            foreach ($jsonCategory as $piece) {
                if (is_string($piece) && trim($piece) !== '') {
                    $categories[] = trim($piece);
                }
            }
        }

        return array_values(array_unique($categories));
    }

    private function images(DOMXPath $xpath, DOMDocument $document, array $jsonLd): array
    {
        $images = [];

        $jsonImages = $jsonLd['image'] ?? null;

        if (is_string($jsonImages)) {
            $images[] = $jsonImages;
        } elseif (is_array($jsonImages)) {
            foreach ($jsonImages as $image) {
                if (is_string($image)) {
                    $images[] = $image;
                } elseif (is_array($image) && isset($image['url'])) {
                    $images[] = $image['url'];
                }
            }
        }

        $og = $this->meta($xpath, 'og:image');

        if ($og) {
            array_unshift($images, $og);
        }

        foreach ($xpath->query('//*[contains(@class,"woocommerce-product-gallery")]//img') ?: [] as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }

            $src = $image->getAttribute('data-large_image') ?: $image->getAttribute('src');

            if ($src) {
                $images[] = $src;
            }
        }

        return array_values(array_unique(array_filter($images, fn ($url) => filter_var($url, FILTER_VALIDATE_URL))));
    }

    private function meta(DOMXPath $xpath, string $property): ?string
    {
        $node = $xpath->query('//meta[@property="'.$property.'"]/@content')->item(0)
            ?: $xpath->query('//meta[@name="'.$property.'"]/@content')->item(0);

        return $node ? trim($node->nodeValue) : null;
    }

    private function firstText(DOMXPath $xpath, array $queries, ?DOMElement $context = null): ?string
    {
        foreach ($queries as $query) {
            $nodes = $context ? $xpath->query($query, $context) : $xpath->query($query);
            $node = $nodes?->item(0);

            if ($node && trim($node->textContent) !== '') {
                return trim($node->textContent);
            }
        }

        return null;
    }

    private function htmlOf(DOMDocument $document, DOMXPath $xpath, array $queries): string
    {
        foreach ($queries as $query) {
            $node = $xpath->query($query)->item(0);

            if ($node) {
                $html = '';

                foreach ($node->childNodes as $child) {
                    $html .= $document->saveHTML($child);
                }

                return trim($html);
            }
        }

        return '';
    }
}
