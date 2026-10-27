<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class MerchantFeedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $enabled = setting('seo.merchant_feed_enabled', config('tajira.feeds.merchant_enabled', false));

        if (! $enabled) {
            abort(404);
        }

        $token = (string) config('tajira.feeds.merchant_token');

        if ($token !== '' && ! hash_equals($token, (string) $request->query('token'))) {
            abort(403);
        }

        $xml = Cache::remember('merchant.feed.v1', 3600, function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n<channel>\n";
            $xml .= '<title>'.e(setting('business.name', 'Tajira')).' product feed</title>'."\n";
            $xml .= '<link>'.e(url('/')).'</link>'."\n";
            $xml .= '<description>Approved Tajira catalogue feed</description>'."\n";

            Product::query()
                ->published()
                ->where('is_indexable', true)
                ->where('price_on_request', false)
                ->whereNotNull('price_minor')
                ->where(fn ($q) => $q->whereNull('robots')->orWhere('robots', 'not like', '%noindex%'))
                ->whereHas('images')
                ->with(['brand', 'primaryImage', 'images'])
                ->chunk(500, function ($products) use (&$xml) {
                    foreach ($products as $product) {
                        $price = $product->effectivePriceMinor();

                        if ($price === null) {
                            continue;
                        }

                        $xml .= "<item>\n";
                        $xml .= '<g:id>'.e($product->sku)."</g:id>\n";
                        $xml .= '<g:title>'.e($product->name)."</g:title>\n";
                        $xml .= '<g:description>'.e(strip_tags($product->short_description ?: $product->description ?: $product->name))."</g:description>\n";
                        $xml .= '<g:link>'.e(route('products.show', $product->slug))."</g:link>\n";
                        $xml .= '<g:image_link>'.e($product->primaryImage->url())."</g:image_link>\n";

                        foreach ($product->images->skip(1)->take(3) as $image) {
                            $xml .= '<g:additional_image_link>'.e($image->url())."</g:additional_image_link>\n";
                        }

                        $xml .= '<g:availability>'.($product->isAvailable() ? 'in_stock' : 'out_of_stock')."</g:availability>\n";
                        $xml .= '<g:price>'.e(number_format($price / 100, 2, '.', '')).' KES'."</g:price>\n";
                        $xml .= '<g:condition>new</g:condition>'."\n";

                        if ($product->brand) {
                            $xml .= '<g:brand>'.e($product->brand->name)."</g:brand>\n";
                        }

                        if ($product->mpn) {
                            $xml .= '<g:mpn>'.e($product->mpn)."</g:mpn>\n";
                        }

                        if ($product->gtin && in_array(strlen((string) $product->gtin), [8, 12, 13, 14], true)) {
                            $xml .= '<g:gtin>'.e($product->gtin)."</g:gtin>\n";
                        }

                        if (! $product->brand && ! $product->gtin && ! $product->mpn) {
                            $xml .= "<g:identifier_exists>no</g:identifier_exists>\n";
                        }

                        $xml .= '<g:product_type>'.e($product->primaryCategory?->name ?? 'Networking')."</g:product_type>\n";
                        $xml .= "</item>\n";
                    }
                });

            return $xml."</channel>\n</rss>";
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
