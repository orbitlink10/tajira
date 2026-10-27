@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')], ['name' => 'Reviews', 'url' => route('account.reviews')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Your reviews</h1>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1">
                @if($reviews->isEmpty())
                    <p class="rounded-xl border border-dashed border-ink-300 bg-white p-8 text-sm text-ink-600">You have not submitted any reviews yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach($reviews as $review)
                            <article class="card p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        @if($review->product)
                                            <a href="{{ route('products.show', $review->product->slug) }}" class="text-sm font-semibold text-ink-900 hover:text-brand-700">{{ $review->product->name }}</a>
                                        @endif
                                        <x-star-rating :rating="$review->rating" :count="1" :show-count="false" />
                                    </div>
                                    <span class="badge {{ $review->status === 'approved' ? 'bg-emerald-50 text-emerald-800' : ($review->status === 'rejected' ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-900') }}">{{ ucfirst($review->status) }}</span>
                                </div>
                                @if($review->title)<p class="mt-2 text-sm font-semibold text-ink-900">{{ $review->title }}</p>@endif
                                <p class="mt-1 text-sm leading-6 text-ink-700">{{ $review->body }}</p>
                                <p class="mt-2 text-xs text-ink-400">{{ $review->created_at->format('j M Y') }}</p>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $reviews->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
