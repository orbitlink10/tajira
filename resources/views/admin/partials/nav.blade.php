@php
    $navIcons = [
        'admin.dashboard' => 'M4 13a8 8 0 1 1 16 0 M6 13v3 M18 13v3 M12 13l3-4 M8 17h8',
        'admin.products.index' => 'M4 9h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9Z M9 9V5h6v4 M12 9v3',
        'admin.categories.index' => 'M4 5h16v14H4z M9 9h7 M9 12h7 M9 15h7 M7 9h.01 M7 12h.01 M7 15h.01',
        'admin.brands.index' => 'M10 5H6a2 2 0 0 0-2 2v4l8 8 8-8-6-6h-4Z M8 9h.01',
        'admin.reviews.index' => 'M9 11H5V5h5v4a4 4 0 0 1-4 4 M19 11h-4V5h5v4a4 4 0 0 1-4 4',
        'admin.orders.index' => 'M4 5h2l2 10h9l2-7H7 M10 19h.01 M17 19h.01',
        'admin.pages.index' => 'M7 4h7l5 5v11H7V4Z M14 4v5h5 M9 13h6 M9 17h6',
        'admin.users.index' => 'M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M2 21v-3a6 6 0 0 1 12 0v3 M17 4a4 4 0 0 1 0 8 M18 15a5 5 0 0 1 4 5',
    ];
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
                                    <span class="tajira-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $navIcons[$item['route']] ?? 'M4 5h16v14H4z M8 9h8 M8 13h8 M8 17h4' }}"/></svg></span><span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endcan
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
