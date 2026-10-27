@extends('layouts.storefront')

@section('content')
    @php
        $hero = $sections->get('hero');
        $heroTitle = $hero?->title ?: 'Networking equipment for the way Kenya connects.';
        $heroSubtitle = $hero?->subtitle ?: 'Routers, switches, wireless links, fibre, security and telephony — organised with structured specifications, clear pricing and quotation support for projects.';
    @endphp

    <section class="relative overflow-hidden bg-ink-900 text-white">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, #3cc3c4 1px, transparent 0); background-size: 28px 28px;"></div>
        <div class="absolute -right-24 top-1/2 h-96 w-96 -translate-y-1/2 rounded-full bg-brand-700/30 blur-3xl" aria-hidden="true"></div>

        <div class="container-page relative grid gap-10 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:py-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-brand-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-sand-400" aria-hidden="true"></span>
                    For homes, businesses, installers, institutions and ISPs
                </p>
                <h1 class="mt-5 max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-[3.4rem] lg:leading-[1.08]">
                    {{ $heroTitle }}
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-ink-100 sm:text-lg">
                    {{ $heroSubtitle }}
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="btn btn-accent px-5 py-3">Browse products</a>
                    <a href="{{ route('quotes.create') }}" class="btn border border-white/25 bg-white/10 px-5 py-3 text-white hover:bg-white/20">Request a quotation</a>
                </div>
                <dl class="mt-9 grid max-w-xl grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                    <div class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <span><span class="block font-semibold text-white">Structured specifications</span><span class="text-ink-200">Filter by the technical details that matter.</span></span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        <span><span class="block font-semibold text-white">Delivery by county</span><span class="text-ink-200">Charges and lead times show at checkout.</span></span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
                        <span><span class="block font-semibold text-white">Project quotations</span><span class="text-ink-200">Send a list, get pricing and availability.</span></span>
                    </div>
                </dl>
            </div>

            <div class="relative">
                <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm sm:p-6">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-200">Shop by need</p>
                        <a href="{{ $categories->first() ? route('categories.show', $categories->first()->slug) : route('products.index') }}" class="text-xs font-semibold text-white/80 hover:text-white">All categories →</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach($categories->take(6) as $category)
                            <a href="{{ route('categories.show', $category->slug) }}" class="group rounded-xl border border-white/10 bg-ink-950/40 p-3.5 transition hover:border-brand-300 hover:bg-ink-950/60">
                                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-500/20 text-xs font-bold text-brand-200">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                <span class="mt-2 block text-sm font-semibold leading-5 text-white">{{ $category->name }}</span>
                                <span class="mt-0.5 block text-xs text-ink-300">{{ $category->published_products_count }} products</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2.5 text-xs text-ink-100">
                        <svg class="h-4 w-4 shrink-0 text-brand-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></svg>
                        Search by model, e.g. <span class="font-semibold text-white">RB941-2nD</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container-page py-12 sm:py-16">
        <x-section-heading
            eyebrow="Catalogue"
            title="Browse by category"
            description="Find equipment by the job it needs to do. Categories are kept separate from brands so you can browse either way."
            :link="route('products.index')"
            link-label="View all products"
        />

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($categories as $category)
                <a href="{{ route('categories.show', $category->slug) }}" class="group card flex items-start gap-3 p-4 transition hover:border-brand-300 hover:shadow-sm">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-sm font-bold text-brand-700">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold leading-5 text-ink-900 group-hover:text-brand-700">{{ $category->name }}</span>
                        <span class="mt-0.5 block text-xs text-ink-500">{{ $category->published_products_count }} products</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="bg-white py-12 sm:py-16">
        <div class="container-page">
            <x-section-heading
                eyebrow="Selected products"
                title="Equipment worth a closer look"
                description="Featured items from the catalogue. Every displayed price is controlled in the Tajira administration area."
                :link="route('products.index')"
            />

            @if($featured->isEmpty())
                <div class="mt-6 rounded-xl border border-dashed border-ink-300 bg-ink-50 p-10 text-center">
                    <p class="font-semibold text-ink-800">The catalogue is being prepared.</p>
                    <p class="mt-1 text-sm text-ink-600">Published products will appear here once approved with prices or quotation options.</p>
                    <a href="{{ route('quotes.create') }}" class="btn btn-primary mt-4">Request a quotation</a>
                </div>
            @else
                <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach($featured as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="container-page py-12 sm:py-16">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="card p-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                </span>
                <h3 class="mt-4 font-bold text-ink-900">Choose the right specification</h3>
                <p class="mt-2 text-sm leading-6 text-ink-600">Use structured attributes such as port count, PoE budget, wireless standard and fibre connector type to narrow the catalogue.</p>
            </div>
            <div class="card p-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sand-100 text-sand-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </span>
                <h3 class="mt-4 font-bold text-ink-900">Buy for a project</h3>
                <p class="mt-2 text-sm leading-6 text-ink-600">Send a bill of materials or a list of models for a formal quotation. Useful for installers, institutions, businesses and ISPs.</p>
                <a href="{{ route('quotes.create') }}" class="mt-3 inline-block text-sm font-semibold text-brand-700 hover:text-brand-900">Start a quotation →</a>
            </div>
            <div class="card p-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-ink-100 text-ink-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <h3 class="mt-4 font-bold text-ink-900">Accurate product information</h3>
                <p class="mt-2 text-sm leading-6 text-ink-600">Specifications are structured and attributable. Unknown values stay unknown rather than being guessed, and reviews are moderated.</p>
            </div>
        </div>
    </section>

    <section class="border-y border-ink-200 bg-white py-10">
        <div class="container-page">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-ink-800">Brands in the Tajira catalogue</h2>
                <a href="{{ route('brands.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">All brands →</a>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($brands as $brand)
                    <a href="{{ route('brands.show', $brand->slug) }}" class="rounded-full border border-ink-200 bg-ink-50 px-3.5 py-1.5 text-sm font-medium text-ink-700 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-800">{{ $brand->name }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="container-page py-12 sm:py-16">
        <x-section-heading
            eyebrow="Guides"
            title="Make a more informed purchase"
            description="Practical guides written for Kenyan buyers, installers and network teams."
            :link="route('guides.index')"
            link-label="All guides"
        />

        @if($guides->isEmpty())
            <p class="mt-6 rounded-xl border border-dashed border-ink-300 bg-white p-8 text-sm text-ink-600">Buying and setup guides will appear here once published.</p>
        @else
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach($guides as $guide)
                    <article class="card flex flex-col p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ ucfirst($guide->type) }}</p>
                        <h3 class="mt-2 text-lg font-bold leading-6 text-ink-900">
                            <a href="{{ route('guides.show', $guide->slug) }}" class="hover:text-brand-700">{{ $guide->title }}</a>
                        </h3>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-ink-600">{{ $guide->excerpt }}</p>
                        <a href="{{ route('guides.show', $guide->slug) }}" class="mt-4 text-sm font-semibold text-brand-700 hover:text-brand-900">Read guide →</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="container-page pb-16">
        <div class="overflow-hidden rounded-2xl bg-ink-900 px-6 py-10 text-white sm:px-10">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-300">For projects and bulk orders</p>
                    <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Need a formal quotation?</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-200">Send the models, quantities and delivery county. Our team will confirm price, availability, warranty and delivery terms.</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    <a href="{{ route('quotes.create') }}" class="btn btn-accent">Request a quotation</a>
                    @if(whatsapp_url())
                        <a href="{{ whatsapp_url('Hello Tajira, I would like a quotation.') }}" target="_blank" rel="noopener noreferrer" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">Chat on WhatsApp</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
