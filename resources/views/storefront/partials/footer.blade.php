@php($menuCategories = $menuCategories ?? collect(); $menuBrands = $menuBrands ?? collect(); $footerPages = $footerPages ?? collect(); $navFooter = $navFooter ?? collect(); @endphp)
<footer class="mt-16 border-t border-ink-200 bg-white">
    <div class="container-page grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <img src="{{ asset('images/logo.svg') }}" alt="{{ setting('business.name', 'Tajira') }}" width="190" height="44" class="h-10 w-auto">
            <p class="mt-4 max-w-sm text-sm leading-6 text-ink-600">
                Networking equipment for individuals, businesses, installers, institutions and ISPs in Kenya.
            </p>
            <div class="mt-4 space-y-2 text-sm text-ink-700">
                @if(setting('business.phone'))
                    <p><a class="hover:text-brand-700" href="{{ tel_url() }}">{{ setting('business.phone') }}</a></p>
                @endif
                @if(setting('business.email'))
                    <p><a class="hover:text-brand-700" href="mailto:{{ setting('business.email') }}">{{ setting('business.email') }}</a></p>
                @endif
                @if(setting('business.address_line'))
                    <p>{{ setting('business.address_line') }}, {{ setting('business.city') }}</p>
                @else
                    <p>{{ setting('business.city') }}, {{ setting('business.country', 'Kenya') }}</p>
                @endif
            </div>

            @php
                $socials = array_filter([
                    'Facebook' => setting('social.facebook'),
                    'Instagram' => setting('social.instagram'),
                    'LinkedIn' => setting('social.linkedin'),
                    'X' => setting('social.x'),
                ]);
            @endphp

            @if($socials)
                <ul class="mt-4 flex flex-wrap gap-3 text-sm">
                    @foreach($socials as $label => $url)
                        <li><a class="font-medium text-brand-700 hover:text-brand-900" href="{{ $url }}" rel="noopener noreferrer" target="_blank">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-900">Shop by category</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                @foreach($menuCategories->take(6) as $category)
                    <li><a class="hover:text-brand-700" href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a></li>
                @endforeach
                <li><a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('products.index') }}">All products</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-900">Popular brands</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                @foreach($menuBrands->take(7) as $brand)
                    <li><a class="hover:text-brand-700" href="{{ route('brands.show', $brand->slug) }}">{{ $brand->name }}</a></li>
                @endforeach
                <li><a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('brands.index') }}">All brands</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-900">Company and policies</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                @foreach($footerPages as $page)
                    <li><a class="hover:text-brand-700" href="{{ route('pages.show', $page->slug) }}">{{ $page->title }}</a></li>
                @endforeach
                @foreach($navFooter as $link)
                    <li><a class="hover:text-brand-700" href="{{ $link->url ?: '#' }}">{{ $link->label }}</a></li>
                @endforeach
                <li><a class="hover:text-brand-700" href="{{ route('contact') }}">Contact us</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('guides.index') }}">Buying and setup guides</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('orders.track') }}">Track an order</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('quotes.create') }}">Request a quotation</a></li>
            </ul>

            <div class="mt-5 rounded-lg border border-ink-200 bg-ink-50 p-3 text-xs text-ink-600">
                <p class="font-semibold text-ink-800">Payment options are configured by Tajira.</p>
                <p class="mt-1">M-Pesa and bank transfer options appear at checkout when enabled and configured. Prices are displayed in {{ config('tajira.currency', 'KES') }}.</p>
            </div>
        </div>
    </div>

    <div class="border-t border-ink-100">
        <div class="container-page flex flex-col gap-2 py-4 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} {{ setting('business.legal_name') ?: setting('business.name', 'Tajira') }}. All rights reserved.</p>
            <div class="flex flex-wrap gap-4">
                <a class="hover:text-brand-700" href="{{ route('pages.show', 'privacy-policy') }}">Privacy</a>
                <a class="hover:text-brand-700" href="{{ route('pages.show', 'terms-and-conditions') }}">Terms</a>
                <a class="hover:text-brand-700" href="{{ route('sitemaps.index') }}">Sitemap</a>
                @if(whatsapp_url())
                    <a class="hover:text-brand-700" href="{{ whatsapp_url('Hello Tajira, I would like help with a product.') }}" rel="noopener noreferrer" target="_blank">WhatsApp</a>
                @endif
            </div>
        </div>
    </div>
</footer>
