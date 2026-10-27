<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Product;
use App\Models\ProductReview;

class StructuredDataService
{
    public function organization(): array
    {
        $name = setting('business.name', 'Tajira');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => url('/'),
            'logo' => asset('images/logo.svg'),
        ];

        $sameAs = array_values(array_filter([
            setting('social.facebook'),
            setting('social.instagram'),
            setting('social.linkedin'),
            setting('social.x'),
        ]));

        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        $email = setting('business.email');
        $phone = setting('business.phone');

        if ($email || $phone) {
            $data['contactPoint'] = array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => $email ?: null,
                'telephone' => $phone ?: null,
                'areaServed' => 'KE',
                'availableLanguage' => 'English',
            ]);
        }

        if (setting('business.address_line') && setting('business.city')) {
            $data['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => setting('business.address_line'),
                'addressLocality' => setting('business.city'),
                'addressCountry' => 'KE',
                'postalCode' => setting('business.postal_code') ?: null,
            ];
        }

        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    public function localBusiness(): ?array
    {
        if (! setting('business.address_line') || ! setting('business.phone')) {
            return null;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => setting('business.name', 'Tajira'),
            'url' => url('/'),
            'telephone' => setting('business.phone'),
            'email' => setting('business.email') ?: null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => setting('business.address_line'),
                'addressLocality' => setting('business.city'),
                'addressCountry' => 'KE',
                'postalCode' => setting('business.postal_code') ?: null,
            ],
        ]);
    }

    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => setting('business.name', 'Tajira'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{name:string,url:string}>  $crumbs
     */
    public function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => url($crumb['url']),
            ])->all(),
        ];
    }

    public function product(Product $product): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => strip_tags($product->short_description ?: $product->description ?: ''),
            'url' => route('products.show', $product->slug),
        ];

        if ($product->images->isNotEmpty()) {
            $data['image'] = $product->images->map(fn ($image) => $image->url())->all();
        }

        if ($product->brand) {
            $data['brand'] = ['@type' => 'Brand', 'name' => $product->brand->name];
        }

        if ($product->model) {
            $data['model'] = $product->model;
        }

        if ($product->mpn) {
            $data['mpn'] = $product->mpn;
        }

        if ($product->gtin && in_array(strlen((string) $product->gtin), [8, 12, 13, 14], true)) {
            $key = match (strlen((string) $product->gtin)) {
                8 => 'gtin8',
                12 => 'gtin12',
                13 => 'gtin13',
                14 => 'gtin14',
            };
            $data[$key] = $product->gtin;
        }

        $offer = $this->offerFor($product);

        if ($offer) {
            $data['offers'] = $offer;
        }

        if ($product->rating_count > 0 && $product->rating_average > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $product->rating_average, 2, '.', ''),
                'reviewCount' => (int) $product->rating_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $reviews = $product->approvedReviews()->limit(10)->get();

        if ($reviews->isNotEmpty()) {
            $data['review'] = $reviews->map(fn (ProductReview $review) => array_filter([
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review->author_name],
                'datePublished' => $review->created_at->toDateString(),
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $review->rating,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'name' => $review->title ?: null,
                'reviewBody' => $review->body,
            ]))->all();
        }

        return $data;
    }

    public function productGroup(Product $product): array
    {
        $variants = $product->activeVariants;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProductGroup',
            'name' => $product->name,
            'productGroupID' => $product->sku,
            'url' => route('products.show', $product->slug),
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'variesBy' => ['https://schema.org/description'],
            'hasVariant' => $variants->map(function ($variant) use ($product) {
                $offer = $this->offerFor($product, $variant);

                return array_filter([
                    '@type' => 'Product',
                    'name' => $product->name.' — '.$variant->name,
                    'sku' => $variant->sku,
                    'mpn' => $variant->mpn ?: null,
                    'image' => $product->images->map(fn ($image) => $image->url())->all(),
                    'offers' => $offer,
                ]);
            })->values()->all(),
        ];
    }

    public function article(Article $article): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt,
            'image' => $article->hero_image_path ? [asset('storage/'.$article->hero_image_path)] : null,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => $article->author_name ? ['@type' => 'Person', 'name' => $article->author_name] : ['@type' => 'Organization', 'name' => setting('business.name', 'Tajira')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => setting('business.name', 'Tajira'),
                'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.svg')],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('guides.show', $article->slug)],
        ]);
    }

    /**
     * @param  array<int, array{question:string,answer:string}>  $faqs
     */
    public function faqPage(array $faqs): ?array
    {
        if ($faqs === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['answer'])],
            ])->all(),
        ];
    }

    private function offerFor(Product $product, $variant = null): ?array
    {
        $price = $variant ? $variant->effectivePriceMinor() : $product->effectivePriceMinor();

        if ($price === null || $product->price_on_request) {
            return null;
        }

        $available = $variant ? $variant->isAvailable() : ($product->isAvailable() || $product->allow_oversell);

        $offer = [
            '@type' => 'Offer',
            'url' => route('products.show', $product->slug),
            'priceCurrency' => 'KES',
            'price' => number_format($price / 100, 2, '.', ''),
            'availability' => $available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@type' => 'Organization', 'name' => setting('business.name', 'Tajira')],
        ];

        $returnsDays = (int) setting('checkout.returns_days', 0);

        if ($returnsDays > 0) {
            $offer['hasMerchantReturnPolicy'] = [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => 'KE',
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => $returnsDays,
            ];
        }

        return $offer;
    }
}
