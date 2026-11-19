@extends('layouts.storefront')

@section('content')
    <section class="tajira-home-intro" aria-labelledby="tajira-home-title">
        <h1 id="tajira-home-title">Tajira Kenya | Networking Equipment for Every Connection</h1>
        <p>Shop routers, Wi-Fi systems, switches and connectivity solutions from leading brands. Find the right equipment for homes, businesses, installers and ISPs across Kenya.</p>
    </section>

    <section class="tajira-brand-showcase" aria-label="Shop networking equipment by brand">
        @foreach([
            ['name' => 'MikroTik', 'slug' => 'mikrotik', 'alt' => 'MikroTik wireless router'],
            ['name' => 'TP-Link', 'slug' => 'tp-link', 'alt' => 'TP-Link network switch'],
            ['name' => 'Tenda', 'slug' => 'tenda', 'alt' => 'Tenda wireless router'],
            ['name' => 'Ubiquiti', 'slug' => 'ubiquiti', 'alt' => 'Ubiquiti UniFi wireless access point'],
            ['name' => 'Ruijie', 'slug' => 'ruijie', 'alt' => 'Ruijie Reyee outdoor access point'],
            ['name' => 'D-Link', 'slug' => 'd-link', 'alt' => 'D-Link network switch'],
        ] as $showcaseBrand)
            <a class="tajira-brand-tile" href="{{ route('brands.show', $showcaseBrand['slug']) }}">
                <img src="{{ asset('images/brands/'.$showcaseBrand['slug'].'.webp') }}" alt="{{ $showcaseBrand['alt'] }}" width="320" height="220" decoding="async">
                <h2>{{ $showcaseBrand['name'] }}</h2>
            </a>
        @endforeach
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
