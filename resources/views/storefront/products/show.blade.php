@extends('layouts.storefront')

@section('content')
    @php
        $images = $product->images;
        $galleryImages = $images->isNotEmpty()
            ? $images->map(fn ($image) => ['url' => $image->url(), 'alt' => $image->alt, 'width' => $image->width ?: 800, 'height' => $image->height ?: 800])->values()
            : collect([['url' => asset('images/product-placeholder.svg'), 'alt' => $product->name.' image pending', 'width' => 800, 'height' => 800]]);
        $specifications = $product->specificationRows();
        $inStock = $product->isAvailable()
            || $product->allow_oversell
            || $product->activeVariants->contains(fn ($variant) => $variant->isAvailable());
        $warranty = $product->warranty_text ?: setting('checkout.warranty_summary');
        $returns = $product->returns_text ?: setting('checkout.returns_summary');
    @endphp

    <div class="container-page pt-5">
        <x-breadcrumbs :crumbs="$crumbs" />
    </div>

    <div class="container-page py-6">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-12">
            <div x-data="gallery()" class="min-w-0">
                <div class="relative overflow-hidden rounded-xl border border-ink-200 bg-white">
                    <div class="aspect-square overflow-hidden">
                        @foreach($galleryImages as $index => $image)
                            <div
                                data-gallery-image
                                data-src="{{ $image['url'] }}"
                                data-alt="{{ $image['alt'] }}"
                                x-show="active === {{ $index }}"
                                class="h-full w-full"
                            >
                                <img
                                    src="{{ $image['url'] }}"
                                    alt="{{ $image['alt'] ?: $product->name }}"
                                    width="{{ $image['width'] }}"
                                    height="{{ $image['height'] }}"
                                    @if($index === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                                    decoding="async"
                                    class="h-full w-full object-contain p-6 transition duration-300 sm:p-10"
                                    :class="zoom ? 'scale-[1.8] cursor-zoom-out' : 'cursor-zoom-in'"
                                    @click="toggleZoom()"
                                >
                            </div>
                        @endforeach
                    </div>
                    <div class="absolute bottom-3 right-3 flex gap-2">
                        <button type="button" class="rounded-lg border border-ink-200 bg-white/95 px-2.5 py-1.5 text-xs font-semibold text-ink-700 shadow-sm hover:bg-white" @click="toggleZoom()">
                            <span x-text="zoom ? 'Reset zoom' : 'Zoom image'">Zoom image</span>
                        </button>
                    </div>
                </div>

                @if($galleryImages->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Product images">
                        @foreach($galleryImages as $index => $image)
                            <button
                                type="button"
                                class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border bg-white p-1.5"
                                :class="active === {{ $index }} ? 'border-brand-600 ring-2 ring-brand-100' : 'border-ink-200 hover:border-ink-300'"
                                @click="select({{ $index }})"
                                aria-label="Show image {{ $index + 1 }}: {{ $image['alt'] ?: $product->name }}"
                            >
                                <img src="{{ $image['url'] }}" alt="" width="64" height="64" loading="lazy" class="h-full w-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif

                <p class="mt-2 text-xs text-ink-500">
                    Images are supplied by the manufacturer or Tajira. Confirm physical appearance and package contents before installation.
                    @if($product->primaryImage?->credit)
                        Image credit: {{ $product->primaryImage->credit }}
                        @if($product->primaryImage->source_url)
                            · <a class="underline hover:text-brand-700" href="{{ $product->primaryImage->source_url }}" target="_blank" rel="noopener noreferrer">source</a>
                        @endif
                    @endif
                </p>
            </div>

            <div class="min-w-0">
                @if($product->brand)
                    <a href="{{ route('brands.show', $product->brand->slug) }}" class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700 hover:text-brand-900">{{ $product->brand->name }}</a>
                @endif

                <h1 class="mt-2 text-3xl font-bold leading-tight tracking-tight text-ink-900 sm:text-4xl">{{ $product->name }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-500">
                    @if($product->model)<span>Model <strong class="font-semibold text-ink-800">{{ $product->model }}</strong></span>@endif
                    <span>SKU <strong class="font-semibold text-ink-800">{{ $product->sku }}</strong></span>
                    @if($product->mpn)<span>MPN <strong class="font-semibold text-ink-800">{{ $product->mpn }}</strong></span>@endif
                    @if($product->rating_count > 0)
                        <a href="#reviews"><x-star-rating :rating="$product->rating_average" :count="$product->rating_count" /></a>
                    @endif
                </div>

                @if($product->short_description)
                    <p class="mt-4 text-base leading-7 text-ink-700">{{ $product->short_description }}</p>
                @elseif(blank($product->description))
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                        <strong>Catalogue record pending verification.</strong> Manufacturer specifications, package contents, availability and approved Tajira pricing for this model are being confirmed. Request a quotation for a verified response.
                    </div>
                @endif

                <div class="mt-5 rounded-xl border border-ink-200 bg-white p-5">
                    <x-price :product="$product" size="lg" />

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="badge {{ $inStock ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-700' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $inStock ? 'bg-emerald-600' : 'bg-ink-400' }}" aria-hidden="true"></span>
                            {{ $product->stockLabel() }}
                        </span>
                        @if($product->taxInclusive())
                            <span class="text-xs text-ink-500">Tax included at the configured rate</span>
                        @else
                            <span class="text-xs text-ink-500">Tax added at checkout</span>
                        @endif
                    </div>

                    @if($product->isPurchasable())
                        <form method="post" action="{{ route('cart.add') }}" class="mt-5 space-y-3">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            @if($product->activeVariants->isNotEmpty())
                                <div>
                                    <label for="variant" class="field-label">Choose a variant</label>
                                    <select id="variant" name="variant_id" class="field-input" required>
                                        <option value="">Select an option</option>
                                        @foreach($product->activeVariants as $variant)
                                            <option value="{{ $variant->id }}" @disabled(!$variant->isAvailable() && !$product->allow_oversell)>
                                                {{ $variant->name }}{{ $variant->sku ? ' — '.$variant->sku : '' }} — {{ $variant->effectivePriceMinor() !== null ? money($variant->effectivePriceMinor()) : 'Request price' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-end gap-2">
                                <div class="w-28">
                                    <label for="quantity" class="field-label">Quantity</label>
                                    <input id="quantity" type="number" name="quantity" value="1" min="1" max="99" step="1" inputmode="numeric" class="field-input">
                                </div>
                                <button type="submit" class="btn btn-primary flex-1 py-3" @disabled(!$inStock)>
                                    {{ $inStock ? 'Add to cart' : 'Out of stock' }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-5">
                            <a href="{{ route('quotes.create', ['product' => $product->id]) }}" class="btn btn-accent w-full py-3">Request price and availability</a>
                            <p class="mt-2 text-xs text-ink-500">This item is quoted rather than sold online.</p>
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('quotes.create', ['product' => $product->id]) }}" class="btn btn-secondary flex-1 text-xs">Request a quotation</a>

                        @auth
                            <form method="post" action="{{ route('wishlist.toggle', $product->slug) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary text-xs" aria-label="Add {{ $product->name }} to wishlist">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                                    Wishlist
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-secondary text-xs">Sign in to save</a>
                        @endauth

                        <form method="post" action="{{ route('compare.add') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <button type="submit" class="btn btn-secondary text-xs">Compare</button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-ink-200 bg-white p-4">
                        <p class="flex items-center gap-2 text-sm font-semibold text-ink-900">
                            <svg class="h-4 w-4 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                            Delivery
                        </p>
                        <p class="mt-2 text-xs leading-5 text-ink-600">{{ setting('checkout.delivery_notice') ?: 'Delivery options and charges are confirmed at checkout.' }}</p>
                        @if(setting('checkout.pickup_enabled'))
                            <p class="mt-1 text-xs leading-5 text-ink-600">Pickup may be available. Collection details are confirmed after your order is accepted.</p>
                        @endif
                    </div>
                    <div class="rounded-lg border border-ink-200 bg-white p-4">
                        <p class="flex items-center gap-2 text-sm font-semibold text-ink-900">
                            <svg class="h-4 w-4 text-brand-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Warranty and returns
                        </p>
                        <p class="mt-2 text-xs leading-5 text-ink-600">{{ $warranty ?: 'Warranty terms are confirmed with the manufacturer and Tajira policy.' }}</p>
                        @if($returns)<p class="mt-1 text-xs leading-5 text-ink-600">{{ $returns }}</p>@endif
                    </div>
                </div>

                @if($product->gtin)
                    <p class="mt-3 text-xs text-ink-500">Verified GTIN: {{ $product->gtin }}</p>
                @endif
            </div>
        </div>

        <div class="mt-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0 space-y-10">
                @if(!empty($product->key_features))
                    <section aria-labelledby="features-heading">
                        <h2 id="features-heading" class="text-xl font-bold text-ink-900">Key features</h2>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach($product->key_features as $feature)
                                <li class="flex items-start gap-2 rounded-lg border border-ink-200 bg-white p-3 text-sm leading-6 text-ink-700">
                                    <svg class="mt-1 h-4 w-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($product->description)
                    <section aria-labelledby="description-heading">
                        <h2 id="description-heading" class="text-xl font-bold text-ink-900">Product overview</h2>
                        <div class="prose-content mt-3">{!! $product->description !!}</div>
                    </section>
                @endif

                <section aria-labelledby="specifications-heading">
                    <h2 id="specifications-heading" class="text-xl font-bold text-ink-900">Specifications</h2>
                    @if($specifications === [])
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Structured specifications for this product are still being verified. Ask us for the manufacturer datasheet before purchase.
                        </div>
                    @else
                        <div class="mt-3 overflow-hidden rounded-xl border border-ink-200 bg-white">
                            <table class="w-full text-sm">
                                <caption class="sr-only">Specifications for {{ $product->name }}</caption>
                                <tbody>
                                    @foreach($specifications as $row)
                                        <tr class="border-b border-ink-100 last:border-0">
                                            <th scope="row" class="w-2/5 bg-ink-50 px-4 py-3 text-left align-top font-semibold text-ink-700">{{ $row['label'] }}</th>
                                            <td class="px-4 py-3 align-top text-ink-800">{{ $row['value'] }}{{ $row['unit'] ? ' '.$row['unit'] : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($product->source_notes)
                            <p class="mt-2 text-xs text-ink-500">Specification source notes: {{ $product->source_notes }}</p>
                        @endif
                    @endif
                </section>

                @if(!empty($product->applications))
                    <section aria-labelledby="applications-heading">
                        <h2 id="applications-heading" class="text-xl font-bold text-ink-900">Typical applications</h2>
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-6 text-ink-700">
                            @foreach($product->applications as $application)
                                <li>{{ $application }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($product->compatibility)
                    <section aria-labelledby="compatibility-heading">
                        <h2 id="compatibility-heading" class="text-xl font-bold text-ink-900">Compatibility and limitations</h2>
                        <div class="prose-content mt-3 text-sm">{!! $product->compatibility !!}</div>
                    </section>
                @endif

                @if(!empty($product->package_contents) || $product->setup_overview)
                    <section aria-labelledby="package-heading" class="grid gap-6 md:grid-cols-2">
                        @if(!empty($product->package_contents))
                            <div>
                                <h2 id="package-heading" class="text-xl font-bold text-ink-900">Verified package contents</h2>
                                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-6 text-ink-700">
                                    @foreach($product->package_contents as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-2 text-xs text-ink-500">Confirm contents on delivery. Accessories shown in images may not be included unless listed above.</p>
                            </div>
                        @endif

                        @if($product->setup_overview)
                            <div>
                                <h2 class="text-xl font-bold text-ink-900">Setup overview</h2>
                                <div class="prose-content mt-3 text-sm">{!! $product->setup_overview !!}</div>
                            </div>
                        @endif
                    </section>
                @endif

                @if($faqData !== [])
                    <section aria-labelledby="faq-heading">
                        <h2 id="faq-heading" class="text-xl font-bold text-ink-900">Product questions</h2>
                        <div class="mt-3 divide-y divide-ink-200 rounded-xl border border-ink-200 bg-white">
                            @foreach($faqData as $faq)
                                <details class="group p-4">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-ink-900">
                                        {{ $faq['question'] }}
                                        <svg class="h-4 w-4 shrink-0 text-ink-400 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </summary>
                                    <div class="prose-content mt-3 text-sm">{!! $faq['answer'] !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section id="reviews" aria-labelledby="reviews-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="reviews-heading" class="text-xl font-bold text-ink-900">Customer reviews</h2>
                        @if($product->rating_count > 0)
                            <x-star-rating :rating="$product->rating_average" :count="$product->rating_count" />
                        @endif
                    </div>

                    @if($product->approvedReviews->isEmpty())
                        <p class="mt-3 rounded-lg border border-ink-200 bg-white p-4 text-sm text-ink-600">No approved reviews yet. Reviews are moderated before publication.</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach($product->approvedReviews as $review)
                                <article class="rounded-lg border border-ink-200 bg-white p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-ink-900">{{ $review->author_name }}</span>
                                            @if($review->verified_purchase)
                                                <span class="badge bg-emerald-50 text-emerald-800">Verified purchase</span>
                                            @endif
                                        </div>
                                        <time class="text-xs text-ink-500" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j M Y') }}</time>
                                    </div>
                                    <x-star-rating :rating="$review->rating" :count="1" :show-count="false" />
                                    @if($review->title)<h3 class="mt-2 text-sm font-semibold text-ink-900">{{ $review->title }}</h3>@endif
                                    <p class="mt-1 text-sm leading-6 text-ink-700">{{ $review->body }}</p>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6 rounded-xl border border-ink-200 bg-white p-5">
                        <h3 class="text-base font-bold text-ink-900">Write a review</h3>
                        <p class="mt-1 text-xs text-ink-500">Only genuine experiences. Reviews are checked before they appear; we do not edit ratings.</p>
                        <form method="post" action="{{ route('products.reviews.store', $product->slug) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                            @csrf
                            <div class="hidden" aria-hidden="true">
                                <label for="review-website">Website</label>
                                <input id="review-website" type="text" name="website" tabindex="-1" autocomplete="off">
                            </div>
                            <div>
                                <label for="author_name" class="field-label">Your name</label>
                                <input id="author_name" name="author_name" value="{{ old('author_name', auth()->user()?->name) }}" required class="field-input">
                            </div>
                            <div>
                                <label for="author_email" class="field-label">Email (not published)</label>
                                <input id="author_email" type="email" name="author_email" value="{{ old('author_email', auth()->user()?->email) }}" required class="field-input">
                            </div>
                            <div>
                                <label for="rating" class="field-label">Rating</label>
                                <select id="rating" name="rating" class="field-input" required>
                                    <option value="">Select a rating</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected((int) old('rating') === $i)>{{ $i }} out of 5</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label for="review_title" class="field-label">Headline (optional)</label>
                                <input id="review_title" name="title" value="{{ old('title') }}" class="field-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="review_body" class="field-label">Your review</label>
                                <textarea id="review_body" name="body" rows="4" required class="field-input">{{ old('body') }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <button type="submit" class="btn btn-primary">Submit review</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                @if($product->accessories->isNotEmpty())
                    <section class="rounded-xl border border-ink-200 bg-white p-4">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-ink-900">Compatible accessories</h2>
                        <ul class="mt-3 divide-y divide-ink-100">
                            @foreach($product->accessories as $accessory)
                                <li class="py-3 first:pt-0 last:pb-0">
                                    <a href="{{ route('products.show', $accessory->slug) }}" class="flex items-center gap-3">
                                        <img src="{{ $accessory->imageUrl() }}" alt="" width="48" height="48" loading="lazy" class="h-12 w-12 rounded border border-ink-100 object-contain">
                                        <span>
                                            <span class="block text-sm font-medium text-ink-900 hover:text-brand-700">{{ $accessory->name }}</span>
                                            <span class="text-xs text-ink-500">{{ $accessory->formattedPrice() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($product->documents->isNotEmpty())
                    <section class="rounded-xl border border-ink-200 bg-white p-4">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-ink-900">Documents</h2>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach($product->documents as $document)
                                <li><a class="font-medium text-brand-700 hover:text-brand-900" href="{{ $document->url() }}" target="_blank" rel="noopener">{{ $document->title }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="rounded-xl border border-ink-200 bg-ink-50 p-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-ink-900">Need a professional recommendation?</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">Send your project details and our team will help match the product to your network requirements.</p>
                    <a href="{{ route('contact') }}" class="btn btn-secondary mt-3 w-full">Contact Tajira</a>
                    @if(whatsapp_url())
                        <a href="{{ whatsapp_url('Hello Tajira, I need help with '.$product->name.' (SKU '.$product->sku.').') }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary mt-2 w-full">WhatsApp about this product</a>
                    @endif
                </section>
            </aside>
        </div>

        @if($product->relatedProducts->isNotEmpty())
            <section class="mt-14">
                <x-section-heading
                    eyebrow="Related"
                    title="You may also need"
                    :link="$product->primaryCategory ? route('categories.show', $product->primaryCategory->slug) : route('products.index')"
                />
                <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach($product->relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        window.dataLayer = window.dataLayer || [];
        @if($product->effectivePriceMinor() !== null)
        window.dataLayer.push({
            event: 'view_item',
            ecommerce: {
                currency: '{{ config('tajira.currency', 'KES') }}',
                value: {{ $product->effectivePriceMinor() / 100 }},
                items: [{
                    item_id: @json($product->sku),
                    item_name: @json($product->name),
                    item_brand: @json($product->brand?->name),
                    item_category: @json($product->primaryCategory?->name),
                    price: {{ $product->effectivePriceMinor() / 100 }},
                    quantity: 1
                }]
            }
        });
        @endif
    </script>
@endpush
