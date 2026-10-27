@php
    $links = [
        ['route' => 'account.dashboard', 'label' => 'Overview'],
        ['route' => 'account.orders', 'label' => 'Orders'],
        ['route' => 'account.wishlist', 'label' => 'Wishlist'],
        ['route' => 'account.addresses', 'label' => 'Addresses'],
        ['route' => 'account.reviews', 'label' => 'Reviews'],
        ['route' => 'account.profile', 'label' => 'Profile and password'],
        ['route' => 'account.privacy', 'label' => 'Privacy and data'],
    ];
@endphp

<aside class="lg:w-60">
    <nav class="card p-2" aria-label="Account">
        <ul class="space-y-1">
            @foreach($links as $link)
                <li>
                    <a href="{{ route($link['route']) }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($link['route']) ? 'bg-brand-50 text-brand-800' : 'text-ink-700 hover:bg-ink-50' }}" @if(request()->routeIs($link['route'])) aria-current="page" @endif>
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
            <li class="border-t border-ink-100 pt-1">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-ink-600 hover:bg-ink-50">Sign out</button>
                </form>
            </li>
        </ul>
    </nav>
</aside>
