@php
    $groups = [
        ['label' => null, 'items' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'ability' => 'dashboard.view', 'pattern' => 'admin.dashboard'],
        ]],
        ['label' => 'Catalogue', 'items' => [
            ['route' => 'admin.products.index', 'label' => 'Products', 'ability' => 'catalogue.view', 'pattern' => 'admin.products.*'],
            ['route' => 'admin.categories.index', 'label' => 'Categories', 'ability' => 'catalogue.view', 'pattern' => 'admin.categories.*'],
            ['route' => 'admin.brands.index', 'label' => 'Brands', 'ability' => 'catalogue.view', 'pattern' => 'admin.brands.*'],
            ['route' => 'admin.attributes.index', 'label' => 'Attributes', 'ability' => 'catalogue.view', 'pattern' => 'admin.attributes.*'],
            ['route' => 'admin.imports.index', 'label' => 'Catalogue imports', 'ability' => 'imports.manage', 'pattern' => 'admin.imports.*'],
        ]],
        ['label' => 'Sales', 'items' => [
            ['route' => 'admin.orders.index', 'label' => 'Orders', 'ability' => 'orders.view', 'pattern' => 'admin.orders.*'],
            ['route' => 'admin.quotations.index', 'label' => 'Quotations', 'ability' => 'quotations.view', 'pattern' => 'admin.quotations.*'],
            ['route' => 'admin.contact-messages.index', 'label' => 'Contact messages', 'ability' => 'quotations.view', 'pattern' => 'admin.contact-messages.*'],
            ['route' => 'admin.reviews.index', 'label' => 'Reviews', 'ability' => 'reviews.moderate', 'pattern' => 'admin.reviews.*'],
        ]],
        ['label' => 'Content', 'items' => [
            ['route' => 'admin.pages.index', 'label' => 'Pages', 'ability' => 'content.view', 'pattern' => 'admin.pages.*'],
            ['route' => 'admin.articles.index', 'label' => 'Articles and guides', 'ability' => 'content.view', 'pattern' => 'admin.articles.*'],
            ['route' => 'admin.article-categories.index', 'label' => 'Article categories', 'ability' => 'content.view', 'pattern' => 'admin.article-categories.*'],
            ['route' => 'admin.navigation.index', 'label' => 'Navigation', 'ability' => 'content.manage', 'pattern' => 'admin.navigation.*'],
            ['route' => 'admin.homepage.index', 'label' => 'Homepage sections', 'ability' => 'content.manage', 'pattern' => 'admin.homepage.*'],
            ['route' => 'admin.media.index', 'label' => 'Media', 'ability' => 'media.manage', 'pattern' => 'admin.media.*'],
        ]],
        ['label' => 'Marketing', 'items' => [
            ['route' => 'admin.coupons.index', 'label' => 'Coupons', 'ability' => 'marketing.manage', 'pattern' => 'admin.coupons.*'],
            ['route' => 'admin.shipping.index', 'label' => 'Shipping', 'ability' => 'marketing.manage', 'pattern' => 'admin.shipping.*'],
            ['route' => 'admin.redirects.index', 'label' => 'Redirects', 'ability' => 'seo.manage', 'pattern' => 'admin.redirects.*'],
            ['route' => 'admin.seo.index', 'label' => 'SEO reports', 'ability' => 'seo.manage', 'pattern' => 'admin.seo.index'],
            ['route' => 'admin.seo.landing-pages.index', 'label' => 'Collections', 'ability' => 'seo.manage', 'pattern' => 'admin.seo.landing-pages.*'],
        ]],
        ['label' => 'System', 'items' => [
            ['route' => 'admin.settings.index', 'label' => 'Business settings', 'ability' => 'settings.manage', 'pattern' => 'admin.settings.*'],
            ['route' => 'admin.users.index', 'label' => 'Administrators', 'ability' => 'users.manage', 'pattern' => 'admin.users.*'],
            ['route' => 'admin.audit.index', 'label' => 'Audit log', 'ability' => 'audit.view', 'pattern' => 'admin.audit.*'],
        ]],
    ];
@endphp

<nav aria-label="Administration" class="space-y-5">
    @foreach($groups as $group)
        <div>
            @if($group['label'])
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-ink-400">{{ $group['label'] }}</p>
            @endif
            <ul class="space-y-0.5">
                @foreach($group['items'] as $item)
                    @can($item['ability'])
                        @if(Route::has($item['route']))
                            <li>
                                <a href="{{ route($item['route']) }}" class="admin-nav-link {{ request()->routeIs($item['pattern']) ? 'is-active' : '' }}">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endif
                    @endcan
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
