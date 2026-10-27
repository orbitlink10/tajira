<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class SkuGenerator
{
    public function forProduct(?string $brand, ?string $model, string $name): string
    {
        $brandPart = Str::upper(Str::substr(Str::slug((string) $brand, ''), 0, 4));
        $modelPart = Str::upper(Str::substr(Str::slug((string) ($model ?: $name), '-'), 0, 28));
        $base = trim(($brandPart ? $brandPart.'-' : '').$modelPart, '-');
        $base = $base !== '' ? $base : 'TAJIRA-PRODUCT';

        return $this->unique($base, Product::class);
    }

    public function forVariant(string $productSku, string $name): string
    {
        $base = Str::upper(Str::substr(Str::slug($productSku.'-'.$name, '-'), 0, 72));

        return $this->unique($base, ProductVariant::class);
    }

    private function unique(string $base, string $modelClass): string
    {
        $sku = $base;
        $counter = 2;

        while ($modelClass::query()->where('sku', $sku)->exists()) {
            $sku = Str::substr($base, 0, 70).'-'.$counter;
            $counter++;
        }

        return $sku;
    }
}
