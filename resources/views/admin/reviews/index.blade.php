@extends('layouts.admin')

@section('title', 'Reviews')
@section('heading', 'Reviews')
@section('subheading', 'Moderate genuine customer reviews before publication.')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.reviews.index') }}" class="badge {{ !request('status') ? 'bg-ink-900 text-white' : 'bg-white border border-ink-200 text-ink-700' }}">All</a>
        @foreach(['pending', 'approved', 'rejected'] as $status)
            <a href="{{ route('admin.reviews.index', ['status' => $status]) }}" class="badge {{ request('status') === $status ? 'bg-ink-900 text-white' : 'bg-white border border-ink-200 text-ink-700' }}">{{ ucfirst($status) }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($reviews as $review)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        @if($review->product)<a href="{{ route('admin.products.edit', $review->product) }}" class="text-sm font-semibold text-ink-900 hover:text-brand-700">{{ $review->product->name }}</a>@endif
                        <p class="mt-0.5 text-xs text-ink-500">{{ $review->author_name }} · {{ $review->author_email }} · {{ $review->created_at->format('j M Y') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-star-rating :rating="$review->rating" :count="1" :show-count="false" />
                        <span class="badge {{ $review->status === 'approved' ? 'bg-emerald-50 text-emerald-800' : ($review->status === 'rejected' ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-900') }}">{{ ucfirst($review->status) }}</span>
                        @if($review->verified_purchase)<span class="badge bg-sky-50 text-sky-800">Verified purchase</span>@endif
                    </div>
                </div>
                @if($review->title)<p class="mt-2 text-sm font-semibold text-ink-900">{{ $review->title }}</p>@endif
                <p class="mt-1 text-sm leading-6 text-ink-700">{{ $review->body }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if($review->status !== 'approved')
                        <form method="post" action="{{ route('admin.reviews.approve', $review) }}">@csrf<button class="btn btn-primary px-3 py-1.5 text-xs">Approve</button></form>
                    @endif
                    @if($review->status !== 'rejected')
                        <form method="post" action="{{ route('admin.reviews.reject', $review) }}">@csrf<button class="btn btn-secondary px-3 py-1.5 text-xs">Reject</button></form>
                    @endif
                    <form method="post" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review permanently?');">@csrf @method('delete')<button class="btn btn-secondary px-3 py-1.5 text-xs text-red-700">Delete</button></form>
                </div>
            </article>
        @empty
            <p class="rounded-lg border border-dashed border-ink-300 bg-white p-8 text-sm text-ink-500">No reviews in this view.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
@endsection
