@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'New product')
@section('heading', $product->exists ? $product->name : 'New product')
@section('subheading', $product->exists ? 'SKU '.$product->sku.' · '.ucwords(str_replace('_', ' ', $product->status)) : 'Create a draft, verify the data, then publish.')

@section('content')
    @if($product->exists)
        <div class="mb-4 flex flex-wrap gap-2">
            @if($product->status !== 'published')
                @can('catalogue.manage')
                    <form method="post" action="{{ route('admin.products.publish', $product) }}">@csrf<button class="btn btn-primary" type="submit">Publish</button></form>
                @endcan
            @else
                @can('catalogue.manage')
                    <form method="post" action="{{ route('admin.products.unpublish', $product) }}">@csrf<button class="btn btn-secondary" type="submit">Unpublish</button></form>
                    <form method="post" action="{{ route('admin.products.archive', $product) }}">@csrf<button class="btn btn-secondary" type="submit">Archive</button></form>
                @endcan
            @endif
            <a href="{{ route('admin.products.preview', $product) }}" target="_blank" class="btn btn-secondary">Preview storefront</a>
            <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">Variants ({{ $product->variants->count() }})</a>
            <a href="{{ route('admin.revisions.index', ['type' => 'product', 'id' => $product->id]) }}" class="btn btn-secondary">Revisions</a>
            @can('catalogue.manage')
                <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="ml-auto" onsubmit="return confirm('Delete this product? This is a soft delete.');">
                    @csrf @method('delete')
                    <button class="btn btn-secondary text-red-700" type="submit">Delete</button>
                </form>
            @endcan
        </div>
    @endif

    <form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="space-y-4">
        @csrf
        @if($product->exists) @method('put') @endif

        <div class="grid gap-4 xl:grid-cols-[1fr_360px]">
            <div class="space-y-4">
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Identity</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="field-label">Product name</label>
                            <input id="name" name="name" value="{{ old('name', $product->name) }}" required class="field-input">
                            <p class="mt-1 text-xs text-ink-500">Use a descriptive name with brand and exact model, without keyword stuffing.</p>
                        </div>
                        <div>
                            <label for="slug" class="field-label">Slug</label>
                            <input id="slug" name="slug" value="{{ old('slug', $product->slug) }}" class="field-input" placeholder="Generated from name if blank">
                        </div>
                        <div>
                            <label for="sku" class="field-label">SKU</label>
                            <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" class="field-input" placeholder="Auto-generated if blank">
                        </div>
                        <div>
                            <label for="brand_id" class="field-label">Brand</label>
                            <select id="brand_id" name="brand_id" class="field-input">
                                <option value="">No brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected((int) old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="primary_category_id" class="field-label">Primary category</label>
                            <select id="primary_category_id" name="primary_category_id" class="field-input">
                                <option value="">No primary category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('primary_category_id', $product->primary_category_id) === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="model" class="field-label">Model</label>
                            <input id="model" name="model" value="{{ old('model', $product->model) }}" class="field-input">
                        </div>
                        <div>
                            <label for="mpn" class="field-label">MPN</label>
                            <input id="mpn" name="mpn" value="{{ old('mpn', $product->mpn) }}" class="field-input">
                        </div>
                        <div>
                            <label for="gtin" class="field-label">GTIN (only if verified)</label>
                            <input id="gtin" name="gtin" value="{{ old('gtin', $product->gtin) }}" class="field-input" placeholder="8, 12, 13 or 14 digits">
                        </div>
                        <div>
                            <label for="gtin_type" class="field-label">GTIN type</label>
                            <input id="gtin_type" name="gtin_type" value="{{ old('gtin_type', $product->gtin_type) }}" class="field-input" placeholder="ean, upc, isbn">
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Content</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="short_description" class="field-label">Buyer-focused summary</label>
                            <textarea id="short_description" name="short_description" rows="3" maxlength="1000" class="field-input">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>
                        <div>
                            <label for="description" class="field-label">Original description (HTML)</label>
                            <textarea id="description" name="description" rows="12" class="field-input font-mono text-xs">{{ old('description', $product->description) }}</textarea>
                            <p class="mt-1 text-xs text-ink-500">Do not paste competitor copy. Content is sanitised before saving.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="key_features_text" class="field-label">Key features (one per line)</label>
                                <textarea id="key_features_text" name="key_features_text" rows="6" class="field-input">{{ old('key_features_text', implode("\n", $product->key_features ?? [])) }}</textarea>
                            </div>
                            <div>
                                <label for="applications_text" class="field-label">Applications (one per line)</label>
                                <textarea id="applications_text" name="applications_text" rows="6" class="field-input">{{ old('applications_text', implode("\n", $product->applications ?? [])) }}</textarea>
                            </div>
                        </div>
                        <div>
                            <label for="compatibility" class="field-label">Compatibility and limitations (HTML)</label>
                            <textarea id="compatibility" name="compatibility" rows="5" class="field-input font-mono text-xs">{{ old('compatibility', $product->compatibility) }}</textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="package_contents_text" class="field-label">Verified package contents (one per line)</label>
                                <textarea id="package_contents_text" name="package_contents_text" rows="5" class="field-input">{{ old('package_contents_text', implode("\n", $product->package_contents ?? [])) }}</textarea>
                            </div>
                            <div>
                                <label for="setup_overview" class="field-label">Setup overview (HTML)</label>
                                <textarea id="setup_overview" name="setup_overview" rows="5" class="field-input font-mono text-xs">{{ old('setup_overview', $product->setup_overview) }}</textarea>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="warranty_text" class="field-label">Product warranty note</label>
                                <input id="warranty_text" name="warranty_text" value="{{ old('warranty_text', $product->warranty_text) }}" class="field-input">
                            </div>
                            <div>
                                <label for="returns_text" class="field-label">Product returns note</label>
                                <input id="returns_text" name="returns_text" value="{{ old('returns_text', $product->returns_text) }}" class="field-input">
                            </div>
                        </div>
                        <div>
                            <label for="disclaimer" class="field-label">Disclaimer (plain text)</label>
                            <textarea id="disclaimer" name="disclaimer" rows="2" class="field-input">{{ old('disclaimer', $product->disclaimer) }}</textarea>
                        </div>
                        <div>
                            <label for="source_notes" class="field-label">Verification / source notes (internal)</label>
                            <textarea id="source_notes" name="source_notes" rows="3" class="field-input">{{ old('source_notes', $product->source_notes) }}</textarea>
                            <p class="mt-1 text-xs text-ink-500">Record manufacturer datasheets and checks internally. Never publish invented specifications.</p>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Structured specifications</h2>
                    <p class="mt-1 text-xs text-ink-500">Only add values you can verify. Empty fields remain unknown.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach($attributes as $attribute)
                            @php($selected = $selectedAttributes[$attribute->id] ?? null)
                            <div>
                                <label for="attr-{{ $attribute->id }}" class="field-label">{{ $attribute->name }}@if($attribute->unit) ({{ $attribute->unit }})@endif</label>
                                @if($attribute->type === 'select')
                                    <select id="attr-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}][value_id]" class="field-input">
                                        <option value="">Not specified</option>
                                        @foreach($attribute->values as $value)
                                            <option value="{{ $value->id }}" @selected((int) old('attributes.'.$attribute->id.'.value_id', $selected?->attribute_value_id) === $value->id)>{{ $value->value }}</option>
                                        @endforeach
                                    </select>
                                @elseif($attribute->type === 'number')
                                    <div class="flex gap-2">
                                        <input id="attr-{{ $attribute->id }}" type="number" step="any" name="attributes[{{ $attribute->id }}][value_number]" value="{{ old('attributes.'.$attribute->id.'.value_number', $selected?->value_number) }}" class="field-input">
                                        <input name="attributes[{{ $attribute->id }}][unit]" value="{{ old('attributes.'.$attribute->id.'.unit', $selected?->unit ?? $attribute->unit) }}" class="field-input w-24" placeholder="Unit">
                                    </div>
                                @elseif($attribute->type === 'boolean')
                                    <select id="attr-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}][value_boolean]" class="field-input">
                                        <option value="">Not specified</option>
                                        <option value="1" @selected((string) old('attributes.'.$attribute->id.'.value_boolean', $selected?->value_boolean) === '1')>Yes</option>
                                        <option value="0" @selected((string) old('attributes.'.$attribute->id.'.value_boolean', $selected?->value_boolean) === '0')>No</option>
                                    </select>
                                @else
                                    <input id="attr-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}][value_text]" value="{{ old('attributes.'.$attribute->id.'.value_text', $selected?->value_text) }}" class="field-input">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Categories</h2>
                    <p class="mt-1 text-xs text-ink-500">A product may belong to several categories. The primary category controls breadcrumbs.</p>
                    <div class="mt-3 grid max-h-72 gap-2 overflow-y-auto sm:grid-cols-2">
                        @foreach($categories as $category)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="h-4 w-4 rounded" @checked(in_array($category->id, old('categories', $selectedCategories)))>
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Related products</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        @foreach(['related' => 'Related products', 'accessory' => 'Compatible accessories', 'compatible' => 'Compatible products'] as $type => $label)
                            <div>
                                <label for="rel-{{ $type }}" class="field-label">{{ $label }}</label>
                                <select id="rel-{{ $type }}" name="{{ $type === 'related' ? 'related_ids' : ($type === 'accessory' ? 'accessory_ids' : 'compatible_ids') }}[]" multiple size="8" class="field-input">
                                    @foreach($relationOptions as $option)
                                        <option value="{{ $option->id }}" @selected(in_array($option->id, old($type === 'related' ? 'related_ids' : ($type === 'accessory' ? 'accessory_ids' : 'compatible_ids'), $relations[$type] ?? [])))>{{ $option->name }} — {{ $option->sku }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[11px] text-ink-400">Ctrl/Cmd-click to select multiple.</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Publication</h2>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="status" class="field-label">Status</label>
                            <select id="status" name="status" class="field-input">
                                @foreach(['draft' => 'Draft', 'pending_review' => 'Pending review', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $product->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="scheduled_at" class="field-label">Scheduled publication</label>
                            <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $product->scheduled_at?->format('Y-m-d\TH:i')) }}" class="field-input">
                        </div>
                        <div>
                            <label for="product_type" class="field-label">Product type</label>
                            <select id="product_type" name="product_type" class="field-input">
                                <option value="simple" @selected(old('product_type', $product->product_type) === 'simple')>Simple</option>
                                <option value="variant" @selected(old('product_type', $product->product_type) === 'variant')>Has variants</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded" @checked(old('is_featured', $product->is_featured))> Featured on homepage</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_indexable" value="1" class="h-4 w-4 rounded" @checked(old('is_indexable', $product->is_indexable))> Allow search indexing</label>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Pricing</h2>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="price" class="field-label">Price (KES)</label>
                            <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price_minor !== null ? number_format($product->price_minor / 100, 2, '.', '') : '') }}" class="field-input">
                        </div>
                        <div>
                            <label for="sale_price" class="field-label">Sale price (KES)</label>
                            <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price_minor !== null ? number_format($product->sale_price_minor / 100, 2, '.', '') : '') }}" class="field-input">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="sale_starts_at" class="field-label">Sale starts</label>
                                <input id="sale_starts_at" type="datetime-local" name="sale_starts_at" value="{{ old('sale_starts_at', $product->sale_starts_at?->format('Y-m-d\TH:i')) }}" class="field-input">
                            </div>
                            <div>
                                <label for="sale_ends_at" class="field-label">Sale ends</label>
                                <input id="sale_ends_at" type="datetime-local" name="sale_ends_at" value="{{ old('sale_ends_at', $product->sale_ends_at?->format('Y-m-d\TH:i')) }}" class="field-input">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="price_on_request" value="1" class="h-4 w-4 rounded" @checked(old('price_on_request', $product->price_on_request))> Quotation-only (no online price)</label>
                        <div>
                            <label for="tax_mode" class="field-label">Tax treatment</label>
                            @php($taxMode = old('tax_mode', $product->tax_inclusive === true ? 'inclusive' : ($product->tax_inclusive === false ? 'exclusive' : 'default')))
                            <select id="tax_mode" name="tax_mode" class="field-input">
                                <option value="default" @selected($taxMode === 'default')>Use store default</option>
                                <option value="inclusive" @selected($taxMode === 'inclusive')>Price includes tax</option>
                                <option value="exclusive" @selected($taxMode === 'exclusive')>Price excludes tax</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Inventory</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div>
                            <label for="stock_quantity" class="field-label">On hand</label>
                            <input id="stock_quantity" type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', $product->stock_quantity) }}" class="field-input">
                        </div>
                        <div>
                            <label for="low_stock_threshold" class="field-label">Low stock at</label>
                            <input id="low_stock_threshold" type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" class="field-input">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-ink-500">Reserved: {{ $product->reserved_quantity }}. Sold stock is committed through order payments, not by editing this field.</p>
                    <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="allow_oversell" value="1" class="h-4 w-4 rounded" @checked(old('allow_oversell', $product->allow_oversell))> Allow orders beyond stock</label>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Shipping</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div><label for="weight_grams" class="field-label">Weight (g)</label><input id="weight_grams" type="number" name="weight_grams" min="0" value="{{ old('weight_grams', $product->weight_grams) }}" class="field-input"></div>
                        <div><label for="length_mm" class="field-label">Length (mm)</label><input id="length_mm" type="number" name="length_mm" min="0" value="{{ old('length_mm', $product->length_mm) }}" class="field-input"></div>
                        <div><label for="width_mm" class="field-label">Width (mm)</label><input id="width_mm" type="number" name="width_mm" min="0" value="{{ old('width_mm', $product->width_mm) }}" class="field-input"></div>
                        <div><label for="height_mm" class="field-label">Height (mm)</label><input id="height_mm" type="number" name="height_mm" min="0" value="{{ old('height_mm', $product->height_mm) }}" class="field-input"></div>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">SEO</h2>
                    <div class="mt-4 space-y-3">
                        <div><label for="meta_title" class="field-label">Meta title</label><input id="meta_title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" class="field-input"></div>
                        <div><label for="meta_description" class="field-label">Meta description</label><textarea id="meta_description" name="meta_description" rows="3" class="field-input">{{ old('meta_description', $product->meta_description) }}</textarea></div>
                        <div><label for="canonical_override" class="field-label">Canonical override</label><input id="canonical_override" name="canonical_override" value="{{ old('canonical_override', $product->canonical_override) }}" class="field-input"></div>
                        <div><label for="og_image_path" class="field-label">Social image path</label><input id="og_image_path" name="og_image_path" value="{{ old('og_image_path', $product->og_image_path) }}" class="field-input"></div>
                        <div><label for="robots" class="field-label">Robots override</label><input id="robots" name="robots" value="{{ old('robots', $product->robots) }}" class="field-input" placeholder="index,follow"></div>
                    </div>
                </section>

                <button type="submit" class="btn btn-primary w-full py-3">Save product</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary w-full">Back to products</a>
            </aside>
        </div>
    </form>

    @if($product->exists)
        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Images</h2>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @forelse($product->images as $image)
                        <div class="rounded-lg border border-ink-200 p-2">
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt }}" width="{{ $image->width }}" height="{{ $image->height }}" loading="lazy" class="h-28 w-full rounded object-contain">
                            <p class="mt-1 line-clamp-2 text-xs text-ink-600">{{ $image->alt }}</p>
                            @if($image->is_primary)<span class="badge mt-1 bg-brand-50 text-brand-800">Primary</span>@endif
                            <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" class="mt-1" onsubmit="return confirm('Remove this image record?');">
                                @csrf @method('delete')
                                <button class="text-xs font-semibold text-red-700">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="col-span-full text-sm text-ink-500">No images uploaded.</p>
                    @endforelse
                </div>

                <form method="post" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data" class="mt-4 space-y-3 border-t border-ink-100 pt-4">
                    @csrf
                    <div>
                        <label for="image" class="field-label">Upload JPEG, PNG or WebP</label>
                        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="field-input">
                    </div>
                    <div>
                        <label for="alt" class="field-label">Alt text (required)</label>
                        <input id="alt" name="alt" required class="field-input">
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_primary" value="1" class="h-4 w-4 rounded"> Make primary image</label>
                    <button class="btn btn-secondary" type="submit">Optimise and upload</button>
                </form>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Documents</h2>
                <ul class="mt-3 divide-y divide-ink-100 text-sm">
                    @forelse($product->documents as $document)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <a class="font-medium text-brand-700" href="{{ $document->url() }}" target="_blank" rel="noopener">{{ $document->title }}</a>
                            <form method="post" action="{{ route('admin.products.documents.destroy', [$product, $document]) }}">
                                @csrf @method('delete')
                                <button class="text-xs font-semibold text-red-700">Remove</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-ink-500">No documents uploaded.</li>
                    @endforelse
                </ul>

                <form method="post" action="{{ route('admin.products.documents.store', $product) }}" enctype="multipart/form-data" class="mt-4 space-y-3 border-t border-ink-100 pt-4">
                    @csrf
                    <div><label for="document" class="field-label">PDF document</label><input id="document" type="file" name="document" accept="application/pdf" required class="field-input"></div>
                    <div><label for="doc_title" class="field-label">Title</label><input id="doc_title" name="title" required class="field-input"></div>
                    <div>
                        <label for="doc_type" class="field-label">Type</label>
                        <select id="doc_type" name="type" class="field-input">
                            @foreach(['datasheet' => 'Datasheet', 'manual' => 'Manual', 'guide' => 'Guide', 'certificate' => 'Certificate'] as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-secondary" type="submit">Upload document</button>
                </form>
            </section>
        </div>
    @endif
@endsection
