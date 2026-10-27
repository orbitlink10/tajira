<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    private const KEY = 'compare.products';

    public function index(Request $request)
    {
        $ids = $request->session()->get(self::KEY, []);
        $products = Product::query()
            ->published()
            ->with([
                'brand:id,name',
                'primaryImage',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
            ])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($product) => array_search($product->id, $ids, true))
            ->values();

        $attributes = $products->flatMap(fn ($product) => $product->attributeValues)
            ->filter(fn ($value) => $value->attribute?->is_comparable)
            ->groupBy(fn ($value) => $value->attribute->name);

        $meta = [
            'title' => 'Compare products | '.setting('business.name', 'Tajira'),
            'description' => 'Compare compatible networking products side by side.',
            'canonical' => route('compare.index'),
            'robots' => 'noindex,follow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];

        return view('storefront.compare.index', compact('products', 'attributes', 'meta'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::query()->published()->with('primaryCategory')->findOrFail($data['product_id']);
        $ids = $request->session()->get(self::KEY, []);

        if (in_array($product->id, $ids, true)) {
            return back()->with('info', 'This product is already in your comparison.');
        }

        if (count($ids) >= 4) {
            return back()->with('error', 'You can compare up to four products at a time.');
        }

        if ($ids !== []) {
            $existingCategory = Product::query()->whereIn('id', $ids)->value('primary_category_id');

            if ($existingCategory && $product->primary_category_id && $existingCategory !== $product->primary_category_id) {
                return back()->with('error', 'Compare products from the same primary category so the specifications align.');
            }
        }

        $ids[] = $product->id;
        $request->session()->put(self::KEY, $ids);

        return redirect()->route('compare.index')->with('success', $product->name.' added to comparison.');
    }

    public function remove(Request $request, Product $product)
    {
        $ids = array_values(array_filter($request->session()->get(self::KEY, []), fn ($id) => (int) $id !== $product->id));
        $request->session()->put(self::KEY, $ids);

        return back()->with('success', 'Product removed from comparison.');
    }

    public function clear(Request $request)
    {
        $request->session()->forget(self::KEY);

        return back()->with('success', 'Comparison cleared.');
    }
}
