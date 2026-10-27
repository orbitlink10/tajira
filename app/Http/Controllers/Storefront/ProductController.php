<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\SearchService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request, SearchService $search, SeoService $seo)
    {
        $filters = $this->filtersFromRequest($request);
        $products = $search->results($filters, [], null, (int) config('tajira.seo.pagination_per_page', 24));
        $filterGroups = $search->filterGroups([], null, $filters);
        $filterBrands = Brand::query()->where('is_active', true)->whereHas('publishedProducts')->withCount('publishedProducts')->orderBy('name')->get();
        $meta = $seo->build(
            'All networking products | '.setting('business.name', 'Tajira'),
            'Browse networking, fibre, security and telephony products available from Tajira in Kenya.',
            route('products.index')
        );

        return view('storefront.products.index', compact('products', 'filterGroups', 'filters', 'filterBrands', 'meta'));
    }

    public function show(Request $request, Product $product, SeoService $seo, StructuredDataService $structured)
    {
        abort_unless($product->isPublished(), 404);

        $product->load([
            'brand',
            'primaryCategory',
            'categories',
            'primaryImage',
            'images',
            'documents',
            'activeVariants',
            'attributeValues.attribute',
            'attributeValues.attributeValue',
            'faqs',
            'approvedReviews' => fn ($query) => $query->limit(20),
            'accessories' => fn ($query) => $query->published()->with(['brand:id,name', 'primaryImage'])->limit(6),
            'relatedProducts' => fn ($query) => $query->published()->with(['brand:id,name', 'primaryImage'])->limit(6),
        ]);

        $primaryCategory = $product->primaryCategory;
        $crumbs = [['name' => 'Home', 'url' => '/']];

        if ($primaryCategory) {
            foreach ($primaryCategory->ancestors() as $ancestor) {
                $crumbs[] = ['name' => $ancestor->name, 'url' => route('categories.show', $ancestor->slug)];
            }

            $crumbs[] = ['name' => $primaryCategory->name, 'url' => route('categories.show', $primaryCategory->slug)];
        }

        $crumbs[] = ['name' => $product->name, 'url' => route('products.show', $product->slug)];

        $meta = $seo->forProduct($product);
        $jsonLd = [
            $structured->breadcrumbs($crumbs),
            $product->product_type === 'variant' && $product->activeVariants->isNotEmpty()
                ? $structured->productGroup($product)
                : $structured->product($product),
        ];

        $faqData = $product->faqs->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer])->all();
        $faqSchema = $structured->faqPage($faqData);

        if ($faqSchema) {
            $jsonLd[] = $faqSchema;
        }

        return view('storefront.products.show', compact('product', 'crumbs', 'meta', 'jsonLd', 'faqData'));
    }

    public function search(Request $request, SearchService $search, SeoService $seo)
    {
        $filters = $this->filtersFromRequest($request);
        $filters['q'] = trim((string) $request->query('q', ''));
        $products = $search->results($filters, [], null, (int) config('tajira.seo.search_per_page', 24));
        $filterGroups = $filters['q'] !== '' ? $search->filterGroups([], null, $filters) : [];
        $filterBrands = Brand::query()->where('is_active', true)->whereHas('publishedProducts')->withCount('publishedProducts')->orderBy('name')->get();

        $meta = $seo->build(
            $filters['q'] !== '' ? 'Search results for "'.$filters['q'].'" | '.setting('business.name', 'Tajira') : 'Search | '.setting('business.name', 'Tajira'),
            'Search Tajira products by model number, SKU, brand or product name.',
            route('search').($filters['q'] !== '' ? '?q='.urlencode($filters['q']) : ''),
            'noindex,follow'
        );

        return view('storefront.products.search', compact('products', 'filterGroups', 'filters', 'filterBrands', 'meta'));
    }

    public function autocomplete(Request $request, SearchService $search)
    {
        $term = trim((string) $request->query('q', ''));

        return response()->json($search->autocomplete($term, 8))
            ->header('Cache-Control', 'no-store');
    }

    public function storeReview(Request $request, Product $product)
    {
        abort_unless($product->isPublished(), 404);

        if ($request->filled('website')) {
            throw ValidationException::withMessages(['review' => 'Your review could not be submitted.']);
        }

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'author_email' => ['required', 'email', 'max:180'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $verified = false;

        if ($request->user()) {
            $verified = Order::query()
                ->where('user_id', $request->user()->id)
                ->where('payment_status', 'paid')
                ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
                ->exists();
        } else {
            $verified = Order::query()
                ->where('customer_email', strtolower($data['author_email']))
                ->where('payment_status', 'paid')
                ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
                ->exists();
        }

        ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'author_name' => $data['author_name'],
            'author_email' => strtolower($data['author_email']),
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => strip_tags($data['body']),
            'status' => ProductReview::STATUS_PENDING,
            'verified_purchase' => $verified,
            'ip_hash' => hash('sha256', (string) $request->ip().'|'.config('app.key')),
        ]);

        return back()->with('success', 'Thank you. Your review has been received and will be published after moderation.');
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'brand' => array_values(array_filter((array) $request->query('brand', []))),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'availability' => $request->query('availability'),
            'sort' => in_array($request->query('sort'), ['relevance', 'price_asc', 'price_desc', 'newest', 'name'], true)
                ? $request->query('sort')
                : 'relevance',
            'attr' => array_filter((array) $request->query('attr', []), fn ($value) => is_array($value)),
            'attr_min' => array_filter((array) $request->query('attr_min', []), fn ($value) => is_numeric($value)),
        ];
    }
}
