@php($menuCategories = $menuCategories ?? collect(); $menuBrands = $menuBrands ?? collect(); $navHeader = $navHeader ?? collect(); $cartCount = $cartCount ?? 0; @endphp)
<header x-data="mobileMenu()">
    <div class="bg-ink-950 text-ink-100">
        <div class="container-page flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
            <p class="flex items-center gap-2">
                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-brand-400" aria-hidden="true"></span>
                {{ setting('checkout.delivery_notice', 'Delivery options and charges are confirmed at checkout.') }}
            </p>
            <div class="flex items-center gap-4">
                @if(setting('business.phone'))
                    <a class="hover:text-white" href="{{ tel_url() }}">{{ setting('business.phone') }}</a>
                @endif
                @if(setting('business.email'))
                    <a class="hidden hover:text-white sm:inline" href="mailto:{{ setting('business.email') }}">{{ setting('business.email') }}</a>
                @endif
                <a class="font-semibold text-brand-200 hover:text-white" href="{{ route('quotes.create') }}">Request a quote</a>
            </div>
        </div>
    </div>

    <div class="border-b border-ink-200 bg-white">
        <div class="container-page flex items-center gap-3 py-3 lg:gap-6">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ setting('business.name', 'Tajira') }} home">
                <img src="{{ asset('images/logo.svg') }}" alt="{{ setting('business.name', 'Tajira') }}" width="200" height="47" class="h-10 w-auto sm:h-11">
            </a>

            <div class="relative hidden flex-1 md:block" x-data="searchBox()" @focusout="window.setTimeout(() => $el.contains(document.activeElement) || close(), 150)">
                <form action="{{ route('search') }}" method="get" role="search" @submit.prevent="submit()">
                    <label for="site-search" class="sr-only">Search products</label>
                    <div class="flex">
                        <input
                            id="site-search"
                            type="search"
                            name="q"
                            x-ref="input"
                            x-model="query"
                            @input.debounce.300ms="run($event.target.value)"
                            @keydown.escape="close()"
                            @keydown.arrow-down.prevent="move(1)"
                            @keydown.arrow-up.prevent="move(-1)"
                            @keydown.enter.prevent="submit()"
                            class="w-full rounded-l-lg border border-r-0 border-ink-300 bg-white px-4 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200"
                            placeholder="Search model, brand or SKU — e.g. RB941-2nD"
                            autocomplete="off"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="search-results"
                        >
                        <button type="submit" class="btn btn-primary rounded-l-none rounded-r-lg px-4" aria-label="Search">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></svg>
                        </button>
                    </div>
                </form>

                <div
                    id="search-results"
                    x-show="open"
                    x-cloak
                    x-transition
                    class="absolute left-0 right-0 top-full z-40 mt-2 max-h-[70vh] overflow-y-auto rounded-xl border border-ink-200 bg-white p-2 shadow-xl"
                    role="listbox"
                >
                    <div x-show="loading" class="px-3 py-2 text-sm text-ink-500">Searching…</div>
                    <div x-show="!loading && !hasResults()" class="px-3 py-4 text-sm text-ink-600">
                        No matches yet. Try a model number, brand, or use <a class="font-semibold text-brand-700 underline" href="{{ route('products.index') }}">all products</a>.
                    </div>

                    <template x-if="results.products.length">
                        <div>
                            <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Products</p>
                            <template x-for="(product, index) in results.products" :key="product.url">
                                <a :href="product.url" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-ink-50" :class="active === index ? 'bg-ink-50' : ''">
                                    <img :src="product.image" alt="" width="44" height="44" loading="lazy" class="h-11 w-11 rounded-md border border-ink-100 object-contain">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-ink-900" x-text="product.name"></span>
                                        <span class="block text-xs text-ink-500"><span x-text="product.brand || ''"></span> · <span x-text="product.price"></span></span>
                                    </span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="results.categories.length || results.brands.length">
                        <div class="mt-1 border-t border-ink-100 pt-1">
                            <template x-for="category in results.categories" :key="category.url">
                                <a :href="category.url" class="block rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">
                                    <span class="text-ink-400">Category · </span><span x-text="category.name"></span>
                                </a>
                            </template>
                            <template x-for="brand in results.brands" :key="brand.url">
                                <a :href="brand.url" class="block rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">
                                    <span class="text-ink-400">Brand · </span><span x-text="brand.name"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}" class="hidden items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-ink-700 hover:bg-ink-50 sm:flex">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 1 1 15 0"/></svg>
                    <span class="hidden lg:inline">{{ auth()->check() ? 'Account' : 'Sign in' }}</span>
                </a>

                @auth
                    <a href="{{ route('account.wishlist') }}" class="hidden items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-ink-700 hover:bg-ink-50 sm:flex">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                        <span class="hidden lg:inline">Wishlist</span>
                    </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="relative flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold text-ink-900 hover:bg-ink-50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a1.125 1.125 0 0 0-1.08-1.487H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                    <span class="hidden lg:inline">Cart</span>
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-700 px-1.5 py-0.5 text-[11px] font-bold text-white">{{ $cartCount ?? 0 }}</span>
                </a>

                <button type="button" class="ml-1 rounded-lg border border-ink-300 p-2 text-ink-800 lg:hidden" @click="toggle()" :aria-expanded="open" aria-controls="mobile-menu" aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                </button>
            </div>
        </div>

        <div class="container-page pb-3 md:hidden" x-data="searchBox()">
            <form action="{{ route('search') }}" method="get" role="search">
                <label for="mobile-search" class="sr-only">Search products</label>
                <div class="flex">
                    <input id="mobile-search" type="search" name="q" class="w-full rounded-l-lg border border-r-0 border-ink-300 px-3 py-2 text-sm" placeholder="Search model, brand or SKU">
                    <button class="btn btn-primary rounded-l-none rounded-r-lg px-3" type="submit" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <nav class="hidden border-b border-ink-200 bg-white lg:block" aria-label="Primary">
        <div class="container-page flex items-center gap-1">
            <a href="{{ route('products.index') }}" class="px-3 py-3 text-sm font-semibold text-ink-900 hover:text-brand-700">Shop all</a>

            @foreach($menuCategories as $category)
                <div class="group relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @focusin="open = true" @focusout="window.setTimeout(() => $el.contains(document.activeElement) || (open = false), 150)">
                    <div class="flex items-center">
                        <a href="{{ route('categories.show', $category->slug) }}" class="px-3 py-3 text-sm font-medium text-ink-700 hover:text-brand-700">{{ $category->name }}</a>
                        @if($category->activeChildren->isNotEmpty())
                            <button type="button" class="-ml-2 rounded p-1 text-ink-400 hover:text-brand-700" @click.prevent="open = !open" :aria-expanded="open" aria-label="Show {{ $category->name }} subcategories">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                        @endif
                    </div>

                    @if($category->activeChildren->isNotEmpty())
                        <div x-show="open" x-cloak x-transition.opacity class="absolute left-0 top-full z-40 w-[34rem] rounded-b-xl border border-t-0 border-ink-200 bg-white p-4 shadow-xl">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($category->activeChildren as $child)
                                    <a href="{{ route('categories.show', $child->slug) }}" class="rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50 hover:text-brand-700">
                                        {{ $child->name }}
                                        <span class="block text-xs text-ink-400">{{ $child->published_products_count }} products</span>
                                    </a>
                                @endforeach
                            </div>
                            <div class="mt-3 border-t border-ink-100 pt-3">
                                <a href="{{ route('categories.show', $category->slug) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">View all {{ $category->name }} →</a>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <a href="{{ route('brands.index') }}" class="px-3 py-3 text-sm font-medium text-ink-700 hover:text-brand-700">Brands</a>
            <a href="{{ route('guides.index') }}" class="px-3 py-3 text-sm font-medium text-ink-700 hover:text-brand-700">Guides</a>
            @foreach($navHeader as $link)
                <a href="{{ $link->url ?: '#' }}" target="{{ $link->target }}" @if($link->target === '_blank') rel="noopener noreferrer" @endif class="px-3 py-3 text-sm font-medium text-ink-700 hover:text-brand-700">{{ $link->label }}</a>
            @endforeach
            <a href="{{ route('contact') }}" class="px-3 py-3 text-sm font-medium text-ink-700 hover:text-brand-700">Contact</a>
            <a href="{{ route('quotes.create') }}" class="ml-auto my-1.5 rounded-lg bg-sand-400 px-3 py-2 text-sm font-semibold text-ink-950 hover:bg-sand-300">Get a quotation</a>
        </div>
    </nav>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="absolute inset-0 bg-ink-950/60" @click="close()"></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl" x-trap.noscroll="open">
            <div class="flex items-center justify-between border-b border-ink-200 px-4 py-3">
                <span class="text-sm font-bold uppercase tracking-wide text-ink-600">Menu</span>
                <button type="button" class="rounded-lg p-2 text-ink-700 hover:bg-ink-50" @click="close()" aria-label="Close menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-4">
                <div class="mb-4">
                    <label for="drawer-search" class="sr-only">Search products</label>
                    <form action="{{ route('search') }}" method="get" role="search" class="flex">
                        <input id="drawer-search" name="q" type="search" class="field-input rounded-r-none" placeholder="Search model, brand or SKU">
                        <button class="btn btn-primary rounded-l-none" type="submit">Go</button>
                    </form>
                </div>

                <nav aria-label="Mobile primary">
                    <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-ink-900 hover:bg-ink-50">Shop all products</a>

                    @foreach($menuCategories as $category)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">
                                <a href="{{ route('categories.show', $category->slug) }}" class="flex-1">{{ $category->name }}</a>
                                <svg class="h-4 w-4 text-ink-400 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </summary>
                            <div class="ml-3 border-l border-ink-200 pl-3">
                                @foreach($category->activeChildren as $child)
                                    <a href="{{ route('categories.show', $child->slug) }}" class="block rounded px-2 py-2 text-sm text-ink-600 hover:bg-ink-50">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endforeach

                    <a href="{{ route('brands.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">Brands</a>
                    <a href="{{ route('guides.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">Guides and articles</a>
                    @foreach($navHeader as $link)
                        <a href="{{ $link->url ?: '#' }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">{{ $link->label }}</a>
                    @endforeach
                    <a href="{{ route('compare.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">Compare products</a>
                    <a href="{{ route('orders.track') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">Track an order</a>
                    <a href="{{ route('contact') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-800 hover:bg-ink-50">Contact</a>
                </nav>

                <div class="mt-4 border-t border-ink-200 pt-4">
                    <a href="{{ route('quotes.create') }}" class="btn btn-accent w-full">Request a quotation</a>
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="btn btn-secondary mt-2 w-full">Your account</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-secondary mt-2 w-full">Sign in</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</header>
