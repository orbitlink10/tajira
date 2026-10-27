@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')], ['name' => 'Wishlist', 'url' => route('account.wishlist')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Your wishlist</h1>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1">
                @if($items->isEmpty())
                    <div class="rounded-xl border border-dashed border-ink-300 bg-white p-10 text-center">
                        <p class="font-semibold text-ink-900">Your wishlist is empty.</p>
                        <p class="mt-1 text-sm text-ink-600">Use the wishlist button on a product page to save it here.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary mt-4">Browse products</a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($items as $item)
                            <div class="flex h-full flex-col">
                                <x-product-card :product="$item->product" />
                                <form method="post" action="{{ route('wishlist.destroy', $item->product->slug) }}" class="mt-2 text-right">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900">Remove from wishlist</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
