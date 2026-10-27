<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use App\Services\InventoryService;
use App\Services\RevisionService;
use App\Services\SeoService;
use App\Services\SkuGenerator;
use App\Services\SlugService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('catalogue.view');

        $products = Product::query()
            ->with(['brand:id,name', 'primaryCategory:id,name', 'primaryImage'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('model', 'like', $term)->orWhere('mpn', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('brand'), fn ($query) => $query->whereHas('brand', fn ($q) => $q->where('slug', $request->query('brand'))))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $brands = Brand::query()->orderBy('name')->get(['id', 'name', 'slug']);
        $statusCounts = Product::query()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.products.index', compact('products', 'brands', 'statusCounts'));
    }

    public function create()
    {
        $this->authorize('catalogue.manage');
        $product = new Product([
            'status' => Product::STATUS_DRAFT,
            'product_type' => 'simple',
            'is_indexable' => true,
            'stock_quantity' => 0,
            'low_stock_threshold' => 0,
        ]);

        return view('admin.products.form', $this->formData($product));
    }

    public function store(Request $request, SlugService $slugs, SkuGenerator $skus, AuditLogger $audit, InventoryService $inventory, HtmlSanitizer $sanitizer)
    {
        $this->authorize('catalogue.manage');
        $data = $this->validated($request, null, $slugs, $skus, $sanitizer);
        $categories = $data['categories'] ?? [];
        $attributes = $request->input('attributes', []);
        $relations = [
            'related' => (array) $request->input('related_ids', []),
            'accessory' => (array) $request->input('accessory_ids', []),
            'compatible' => (array) $request->input('compatible_ids', []),
        ];
        unset($data['categories']);

        $product = DB::transaction(function () use ($data, $categories, $attributes, $relations, $audit) {
            $product = Product::query()->create($data);
            $this->syncCategories($product, $categories, $data['primary_category_id'] ?? null);
            $this->syncAttributes($product, $attributes);
            $this->syncRelations($product, $relations);
            $audit->log('product.created', $product, [], $product->toArray());

            return $product;
        });

        app(InventoryService::class)->syncStockStatus($product);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created. Add images, verify specifications and publish when ready.');
    }

    public function edit(Product $product)
    {
        $this->authorize('catalogue.manage');
        $product->load(['categories', 'attributeValues', 'images', 'documents', 'productRelations', 'brand']);

        return view('admin.products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product, SlugService $slugs, SkuGenerator $skus, AuditLogger $audit, RevisionService $revisions, InventoryService $inventory, HtmlSanitizer $sanitizer)
    {
        $this->authorize('catalogue.manage');
        $old = $product->toArray();
        $oldSlug = $product->slug;
        $data = $this->validated($request, $product, $slugs, $skus, $sanitizer);
        $categories = $data['categories'] ?? [];
        $attributes = $request->input('attributes', []);
        $relations = [
            'related' => (array) $request->input('related_ids', []),
            'accessory' => (array) $request->input('accessory_ids', []),
            'compatible' => (array) $request->input('compatible_ids', []),
        ];
        unset($data['categories']);

        DB::transaction(function () use ($product, $data, $categories, $attributes, $relations, $audit) {
            $product->update($data);
            $this->syncCategories($product, $categories, $data['primary_category_id'] ?? null);
            $this->syncAttributes($product, $attributes);
            $this->syncRelations($product, $relations);
            $audit->log('product.updated', $product, [], $product->fresh()->toArray());
        });

        if ($oldSlug !== $product->slug) {
            $slugs->handleSlugChange($product, $oldSlug, '/products/'.$product->slug, '/products/'.$oldSlug);
        }

        $revisions->record($product, $request->user(), 'Product update');
        $inventory->syncStockStatus($product->fresh());

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated.');
    }

    public function publish(Request $request, Product $product, AuditLogger $audit, RevisionService $revisions)
    {
        $this->authorize('catalogue.manage');

        $errors = [];

        if (! $product->price_on_request && $product->price_minor === null) {
            $errors[] = 'Set a price or mark the product as quotation-only.';
        }

        if (blank($product->short_description) && blank($product->description)) {
            $errors[] = 'Add a buyer-focused description.';
        }

        if (! $product->brand_id || ! $product->primary_category_id) {
            $errors[] = 'Assign a brand and a primary category.';
        }

        if ($product->product_type === 'variant' && ! $product->variants()->exists()) {
            $errors[] = 'Add at least one variant.';
        }

        if ($errors !== []) {
            return back()->with('error', implode(' ', $errors));
        }

        $product->update([
            'status' => Product::STATUS_PUBLISHED,
            'published_at' => $product->published_at ?? now(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $audit->log('product.published', $product);
        $revisions->record($product, $request->user(), 'Published');

        return back()->with('success', 'Product published.');
    }

    public function unpublish(Request $request, Product $product, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $product->update(['status' => Product::STATUS_DRAFT]);
        $audit->log('product.unpublished', $product);

        return back()->with('success', 'Product returned to draft.');
    }

    public function archive(Request $request, Product $product, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $product->update(['status' => Product::STATUS_ARCHIVED]);
        $audit->log('product.archived', $product);

        return back()->with('success', 'Product archived.');
    }

    public function preview(Product $product, SeoService $seo)
    {
        $this->authorize('catalogue.view');
        $product->load([
            'brand', 'primaryCategory', 'categories', 'primaryImage', 'images', 'documents',
            'activeVariants', 'attributeValues.attribute', 'attributeValues.attributeValue',
            'faqs', 'approvedReviews', 'accessories', 'relatedProducts',
        ]);

        $crumbs = [['name' => 'Home', 'url' => '/'], ['name' => 'Preview', 'url' => '#']];
        $meta = $seo->forProduct($product);
        $meta['robots'] = 'noindex,nofollow';
        $jsonLd = [];
        $faqData = $product->faqs->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer])->all();
        $preview = true;

        return view('storefront.products.show', compact('product', 'crumbs', 'meta', 'jsonLd', 'faqData', 'preview'));
    }

    public function destroy(Product $product, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $audit->log('product.deleted', $product, $product->toArray());
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted. Existing redirects are not removed automatically.');
    }

    public function storeImage(Request $request, Product $product, ImageService $images, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $request->validate([
            'image' => ['required', 'image', 'max:8192', 'mimes:jpg,jpeg,png,webp'],
            'alt' => ['required', 'string', 'max:220'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $result = $images->storeProductImage($request->file('image'), $product->id, $request->input('alt'));
        $makePrimary = $request->boolean('is_primary') || ! $product->images()->exists();

        if ($makePrimary) {
            $product->images()->update(['is_primary' => false]);
        }

        $image = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $result['path'],
            'alt' => $request->input('alt'),
            'width' => $result['width'],
            'height' => $result['height'],
            'size_bytes' => $result['size'],
            'mime' => $result['mime'],
            'is_primary' => $makePrimary,
            'position' => (int) $product->images()->max('position') + 1,
        ]);

        $audit->log('product.image.uploaded', $product, [], ['image_id' => $image->id]);

        return back()->with('success', 'Image optimised and added.');
    }

    public function destroyImage(Product $product, ProductImage $image, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        abort_unless($image->product_id === $product->id, 404);
        $audit->log('product.image.deleted', $product, ['image_id' => $image->id]);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    public function storeDocument(Request $request, Product $product, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:datasheet,manual,guide,certificate'],
        ]);

        $file = $request->file('document');
        $path = $file->store('product-documents/'.$product->id, 'public');

        ProductDocument::query()->create([
            'product_id' => $product->id,
            'path' => $path,
            'title' => $request->input('title'),
            'type' => $request->input('type'),
            'size_bytes' => $file->getSize(),
        ]);

        $audit->log('product.document.uploaded', $product, [], ['title' => $request->input('title')]);

        return back()->with('success', 'Document uploaded.');
    }

    public function destroyDocument(Product $product, ProductDocument $document, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        abort_unless($document->product_id === $product->id, 404);
        $audit->log('product.document.deleted', $product, ['document_id' => $document->id]);
        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    public function bulkStatus(Request $request, AuditLogger $audit, RevisionService $revisions)
    {
        $this->authorize('catalogue.manage');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
            'action' => ['required', 'in:publish,draft,archive'],
        ]);

        $skipped = [];
        $updated = 0;

        foreach (Product::query()->whereIn('id', $data['ids'])->get() as $product) {
            if ($data['action'] === 'publish') {
                $errors = [];

                if (! $product->price_on_request && $product->price_minor === null) {
                    $errors[] = 'no price or quotation-only flag';
                }

                if (blank($product->short_description) && blank($product->description)) {
                    $errors[] = 'no description';
                }

                if (! $product->brand_id || ! $product->primary_category_id) {
                    $errors[] = 'missing brand or primary category';
                }

                if ($product->product_type === 'variant' && ! $product->variants()->exists()) {
                    $errors[] = 'no variants';
                }

                if ($errors !== []) {
                    $skipped[] = $product->name.' ('.implode(', ', $errors).')';

                    continue;
                }

                $product->update([
                    'status' => Product::STATUS_PUBLISHED,
                    'published_at' => $product->published_at ?? now(),
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);
            } else {
                $product->update([
                    'status' => $data['action'] === 'archive' ? Product::STATUS_ARCHIVED : Product::STATUS_DRAFT,
                ]);
            }

            $updated++;
            $revisions->record($product, $request->user(), 'Bulk status: '.$data['action']);
        }

        $audit->log('products.bulk-status', null, [], ['action' => $data['action'], 'ids' => $data['ids'], 'updated' => $updated, 'skipped' => $skipped]);

        $message = $updated.' product(s) updated.';

        if ($skipped !== []) {
            $message .= ' Skipped: '.implode('; ', array_slice($skipped, 0, 5)).(count($skipped) > 5 ? '…' : '');
        }

        return back()->with($skipped === [] ? 'success' : 'warning', $message);
    }

    public function export()
    {
        $this->authorize('catalogue.view');

        $headers = ['name', 'sku', 'brand', 'category', 'model', 'mpn', 'price_kes', 'stock', 'status', 'slug'];

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            Product::query()->with(['brand:id,name', 'primaryCategory:id,name'])->orderBy('id')->chunk(500, function ($products) use ($handle) {
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product->name,
                        $product->sku,
                        $product->brand?->name,
                        $product->primaryCategory?->name,
                        $product->model,
                        $product->mpn,
                        Money::minorToDecimal($product->price_minor),
                        $product->stock_quantity,
                        $product->status,
                        $product->slug,
                    ]);
                }
            });

            fclose($handle);
        }, 'tajira-products-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function importForm()
    {
        $this->authorize('catalogue.manage');

        return view('admin.products.import');
    }

    public function import(Request $request, SkuGenerator $skus)
    {
        $this->authorize('catalogue.manage');
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $dryRun = $request->boolean('dry_run');
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle) ?: [];
        $header = array_map(fn ($value) => Str::lower(trim((string) $value)), $header);

        if (! in_array('name', $header, true)) {
            fclose($handle);

            return back()->with('error', 'The CSV needs a header row containing at least a “name” column.');
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $payload = [];

            foreach ($header as $index => $key) {
                $payload[$key] = trim((string) ($values[$index] ?? ''));
            }

            $rows[] = ['row' => $line, 'payload' => $payload];
        }

        fclose($handle);

        $batch = ImportBatch::query()->create([
            'reference' => 'CSV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)),
            'type' => 'csv',
            'source' => $request->file('file')->getClientOriginalName(),
            'status' => $dryRun ? 'dry_run' : 'running',
            'total_rows' => count($rows),
            'params' => ['dry_run' => $dryRun],
            'started_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        $imported = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $payload = $row['payload'];
            $errors = [];

            if (blank($payload['name'] ?? null)) {
                $errors[] = 'Name is required.';
            }

            $sku = $payload['sku'] ?? '';
            $existing = $sku !== '' ? Product::query()->where('sku', $sku)->first() : null;

            if ($existing) {
                $errors[] = 'SKU already exists. The importer does not overwrite existing products.';
            }

            $brand = null;

            if (! empty($payload['brand'])) {
                $brand = Brand::query()->where('name', 'like', $payload['brand'])->orWhere('slug', Str::slug($payload['brand']))->first();

                if (! $brand) {
                    $errors[] = 'Brand not found: '.$payload['brand'].'. Create it before importing.';
                }
            }

            $price = null;

            if (($payload['price_kes'] ?? '') !== '') {
                $price = Money::parseToMinor($payload['price_kes']);

                if ($price === null) {
                    $errors[] = 'Price could not be parsed.';
                }
            }

            $status = $errors === [] ? ($dryRun ? 'valid' : 'imported') : 'error';

            ImportRow::query()->create([
                'import_batch_id' => $batch->id,
                'row_number' => $row['row'],
                'payload' => $payload,
                'status' => $status,
                'errors' => $errors,
            ]);

            if ($errors !== []) {
                $failed++;

                continue;
            }

            if ($dryRun) {
                continue;
            }

            if ($sku === '') {
                $sku = $skus->forProduct($brand?->name, $payload['model'] ?? null, $payload['name']);
            }

            $category = null;

            if (! empty($payload['category'])) {
                $category = Category::query()->where('name', 'like', $payload['category'])->first();
            }

            $product = Product::query()->create([
                'name' => $payload['name'],
                'sku' => $sku,
                'slug' => app(SlugService::class)->unique($payload['name'], Product::class),
                'brand_id' => $brand?->id,
                'primary_category_id' => $category?->id,
                'model' => $payload['model'] ?? null,
                'mpn' => $payload['mpn'] ?? null,
                'price_minor' => $price,
                'stock_quantity' => (int) ($payload['stock'] ?? 0),
                'status' => Product::STATUS_DRAFT,
                'product_type' => 'simple',
                'is_indexable' => true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            if ($category) {
                $product->categories()->sync([$category->id => ['is_primary' => true, 'position' => 0]]);
            }

            $imported++;
        }

        $batch->update([
            'processed_rows' => count($rows),
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'status' => $dryRun ? 'dry_run_complete' : 'completed',
            'finished_at' => now(),
            'last_checkpoint_at' => now(),
        ]);

        return redirect()->route('admin.imports.show', $batch)->with('success', $dryRun ? 'Dry run complete. No products were created.' : 'CSV import finished. New products are drafts pending verification.');
    }

    private function formData(Product $product): array
    {
        $brands = Brand::query()->orderBy('name')->get(['id', 'name']);
        $categories = Category::query()->orderBy('name')->get(['id', 'name', 'parent_id']);
        $attributes = Attribute::query()->with('values')->orderBy('position')->orderBy('name')->get();
        $selectedAttributes = $product->exists
            ? $product->attributeValues->keyBy('attribute_id')
            : collect();

        $relationOptions = Product::query()
            ->whereKeyNot($product->id ?? 0)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'sku']);

        $selectedCategories = $product->exists ? $product->categories->pluck('id')->all() : [];
        $relations = $product->exists
            ? $product->productRelations->groupBy('type')->map->pluck('related_product_id')->all()
            : [];

        return compact('product', 'brands', 'categories', 'attributes', 'selectedAttributes', 'relationOptions', 'selectedCategories', 'relations');
    }

    private function validated(Request $request, ?Product $product, SlugService $slugs, SkuGenerator $skus, HtmlSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product?->id)],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'primary_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'model' => ['nullable', 'string', 'max:160'],
            'mpn' => ['nullable', 'string', 'max:160'],
            'gtin' => ['nullable', 'string', 'max:32'],
            'gtin_type' => ['nullable', 'string', 'max:20'],
            'product_type' => ['required', 'in:simple,variant'],
            'status' => ['required', 'in:draft,pending_review,scheduled,published,archived'],
            'scheduled_at' => ['nullable', 'date'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:100000'],
            'key_features_text' => ['nullable', 'string', 'max:10000'],
            'applications_text' => ['nullable', 'string', 'max:10000'],
            'compatibility' => ['nullable', 'string', 'max:20000'],
            'package_contents_text' => ['nullable', 'string', 'max:10000'],
            'setup_overview' => ['nullable', 'string', 'max:20000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'price_on_request' => ['nullable', 'boolean'],
            'tax_mode' => ['required', 'in:default,inclusive,exclusive'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'allow_oversell' => ['nullable', 'boolean'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'length_mm' => ['nullable', 'integer', 'min:0'],
            'width_mm' => ['nullable', 'integer', 'min:0'],
            'height_mm' => ['nullable', 'integer', 'min:0'],
            'warranty_text' => ['nullable', 'string', 'max:255'],
            'returns_text' => ['nullable', 'string', 'max:255'],
            'disclaimer' => ['nullable', 'string', 'max:20000'],
            'source_notes' => ['nullable', 'string', 'max:20000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_override' => ['nullable', 'url', 'max:255'],
            'og_image_path' => ['nullable', 'string', 'max:255'],
            'robots' => ['nullable', 'string', 'max:60'],
            'is_featured' => ['nullable', 'boolean'],
            'is_indexable' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'attributes' => ['nullable', 'array'],
            'related_ids' => ['nullable', 'array'],
            'accessory_ids' => ['nullable', 'array'],
            'compatible_ids' => ['nullable', 'array'],
        ]);

        $data['slug'] = $slugs->unique($data['slug'] ?: $data['name'], Product::class, $product?->id);
        $data['sku'] = $data['sku'] ?: $skus->forProduct(
            Brand::query()->find($data['brand_id'] ?? null)?->name,
            $data['model'] ?? null,
            $data['name']
        );
        $data['price_minor'] = Money::parseToMinor($request->input('price'));
        $data['sale_price_minor'] = Money::parseToMinor($request->input('sale_price'));
        $data['price_on_request'] = $request->boolean('price_on_request');
        $data['tax_inclusive'] = match ($data['tax_mode']) {
            'inclusive' => true,
            'exclusive' => false,
            default => null,
        };
        $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);
        $data['low_stock_threshold'] = (int) ($data['low_stock_threshold'] ?? 0);
        $data['allow_oversell'] = $request->boolean('allow_oversell');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['key_features'] = $this->lines($request->input('key_features_text'));
        $data['applications'] = $this->lines($request->input('applications_text'));
        $data['package_contents'] = $this->lines($request->input('package_contents_text'));
        $data['description'] = $sanitizer->clean($data['description'] ?? null);
        $data['compatibility'] = $sanitizer->clean($data['compatibility'] ?? null);
        $data['setup_overview'] = $sanitizer->clean($data['setup_overview'] ?? null);
        $data['disclaimer'] = isset($data['disclaimer']) ? strip_tags($data['disclaimer']) : null;
        $data['source_notes'] = isset($data['source_notes']) ? strip_tags($data['source_notes']) : null;
        $data['scheduled_at'] = $request->input('scheduled_at') ?: null;
        $data['updated_by'] = $request->user()->id;

        if ($data['status'] === Product::STATUS_SCHEDULED && ! $data['scheduled_at']) {
            throw ValidationException::withMessages(['scheduled_at' => 'Choose a scheduled publication date and time.']);
        }

        if ($data['status'] === Product::STATUS_PUBLISHED && ! $product?->isPublished()) {
            $errors = [];

            if (! $data['price_on_request'] && $data['price_minor'] === null) {
                $errors[] = 'Set an approved price or mark the product as quotation-only before publishing.';
            }

            if (blank($data['short_description'] ?? null) && blank($data['description'] ?? null)) {
                $errors[] = 'Add a product description before publishing.';
            }

            if (($data['brand_id'] ?? null) === null || ($data['primary_category_id'] ?? null) === null) {
                $errors[] = 'Assign a brand and primary category before publishing.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages(['status' => implode(' ', $errors)]);
            }
        }

        if ($data['status'] === Product::STATUS_PUBLISHED) {
            $data['published_at'] = $product?->published_at ?? now();
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();
        }

        unset($data['key_features_text'], $data['applications_text'], $data['package_contents_text'], $data['tax_mode'], $data['price'], $data['sale_price'], $data['related_ids'], $data['accessory_ids'], $data['compatible_ids'], $data['attributes']);

        return $data;
    }

    private function lines(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function syncCategories(Product $product, array $categoryIds, ?int $primaryId): void
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));

        if ($primaryId && ! in_array($primaryId, $categoryIds, true)) {
            $categoryIds[] = $primaryId;
        }

        if ($primaryId === null && $categoryIds !== []) {
            $primaryId = $categoryIds[0];
        }

        $payload = [];

        foreach ($categoryIds as $index => $id) {
            $payload[$id] = ['is_primary' => $id === $primaryId, 'position' => $index];
        }

        $product->categories()->sync($payload);
        $product->forceFill(['primary_category_id' => $primaryId])->save();
    }

    private function syncAttributes(Product $product, array $inputs): void
    {
        $product->attributeValues()->delete();

        foreach ($inputs as $attributeId => $input) {
            $attribute = Attribute::query()->find($attributeId);

            if (! $attribute) {
                continue;
            }

            if ($attribute->type === 'number') {
                if (($input['value_number'] ?? '') === '' || ! is_numeric($input['value_number'])) {
                    continue;
                }

                $product->attributeValues()->create([
                    'attribute_id' => $attribute->id,
                    'value_number' => (float) $input['value_number'],
                    'unit' => $input['unit'] ?? $attribute->unit,
                ]);
            } elseif ($attribute->type === 'boolean') {
                if (! isset($input['value_boolean']) || $input['value_boolean'] === '') {
                    continue;
                }

                $product->attributeValues()->create([
                    'attribute_id' => $attribute->id,
                    'value_boolean' => (bool) (int) $input['value_boolean'],
                ]);
            } elseif ($attribute->type === 'select') {
                $valueId = $input['value_id'] ?? null;

                if (! $valueId) {
                    continue;
                }

                $value = $attribute->values()->find($valueId);

                if (! $value) {
                    continue;
                }

                $product->attributeValues()->create([
                    'attribute_id' => $attribute->id,
                    'attribute_value_id' => $value->id,
                    'value_number' => $value->numeric_value,
                    'unit' => $value->unit ?: $attribute->unit,
                ]);
            } else {
                $text = trim((string) ($input['value_text'] ?? ''));

                if ($text === '') {
                    continue;
                }

                $product->attributeValues()->create([
                    'attribute_id' => $attribute->id,
                    'value_text' => $text,
                ]);
            }
        }
    }

    private function syncRelations(Product $product, array $relations): void
    {
        $product->productRelations()->delete();

        foreach ($relations as $type => $ids) {
            foreach (array_unique(array_map('intval', $ids)) as $id) {
                if ($id === $product->id || ! Product::query()->whereKey($id)->exists()) {
                    continue;
                }

                $product->productRelations()->create([
                    'related_product_id' => $id,
                    'type' => $type,
                    'position' => 0,
                ]);
            }
        }
    }
}
