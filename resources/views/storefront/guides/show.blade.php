@extends('layouts.storefront')

@section('content')
    <article class="container-page pt-6">
        <x-breadcrumbs :crumbs="$crumbs" />

        <header class="mx-auto mt-6 max-w-3xl">
            @if($article->category)
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">{{ $article->category->name }}</p>
            @endif
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">{{ $article->title }}</h1>
            @if($article->excerpt)
                <p class="mt-3 text-lg leading-8 text-ink-600">{{ $article->excerpt }}</p>
            @endif
            <div class="mt-4 flex flex-wrap items-center gap-2 border-b border-ink-200 pb-5 text-xs text-ink-500">
                @if($article->author_name)<span>{{ $article->author_name }}</span><span aria-hidden="true">·</span>@endif
                @if($article->published_at)<time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('j F Y') }}</time><span aria-hidden="true">·</span>@endif
                @if($article->reading_minutes)<span>{{ $article->reading_minutes }} min read</span>@endif
            </div>
        </header>

        @if($article->hero_image_path)
            <div class="mx-auto mt-6 max-w-4xl overflow-hidden rounded-xl border border-ink-200 bg-white">
                <img src="{{ asset('storage/'.$article->hero_image_path) }}" alt="" width="1200" height="675" class="h-auto w-full">
            </div>
        @endif

        <div class="prose-content mx-auto mt-8 max-w-3xl">
            {!! $article->body !!}
        </div>

        @if($article->products->isNotEmpty())
            <section class="mx-auto mt-12 max-w-5xl">
                <h2 class="text-xl font-bold text-ink-900">Products mentioned in this guide</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                    @foreach($article->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if($related->isNotEmpty())
            <section class="mx-auto mt-12 max-w-3xl border-t border-ink-200 pt-6">
                <h2 class="text-lg font-bold text-ink-900">Related guides</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($related as $item)
                        <li><a class="font-medium text-brand-700 hover:text-brand-900" href="{{ route('guides.show', $item->slug) }}">{{ $item->title }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
