@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Guides', 'url' => route('guides.index')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Buying and setup guides</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">Original editorial content for Kenyan buyers, installers, businesses and ISPs. Guides separate general advice from product-specific verified specifications.</p>

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('guides.index') }}" class="rounded-full border px-3 py-1.5 text-sm font-medium {{ !$category ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-ink-200 bg-white text-ink-700' }}">All guides</a>
            @foreach($categories as $item)
                <a href="{{ route('guides.index', ['category' => $item->slug]) }}" class="rounded-full border px-3 py-1.5 text-sm font-medium {{ $category?->id === $item->id ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-ink-200 bg-white text-ink-700' }}">{{ $item->name }}</a>
            @endforeach
        </div>

        @if($articles->isEmpty())
            <div class="mt-8 rounded-xl border border-dashed border-ink-300 bg-white p-10 text-center">
                <p class="font-semibold text-ink-900">No guides have been published yet.</p>
                <p class="mt-1 text-sm text-ink-600">Published buying, setup and compatibility guides will appear here.</p>
            </div>
        @else
            <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($articles as $article)
                    <article class="card flex flex-col overflow-hidden">
                        @if($article->hero_image_path)
                            <a href="{{ route('guides.show', $article->slug) }}" class="block aspect-[16/9] overflow-hidden bg-ink-50">
                                <img src="{{ asset('storage/'.$article->hero_image_path) }}" alt="" width="640" height="360" loading="lazy" class="h-full w-full object-cover">
                            </a>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ $article->type }}</p>
                            <h2 class="mt-2 text-lg font-bold leading-6 text-ink-900">
                                <a href="{{ route('guides.show', $article->slug) }}" class="hover:text-brand-700">{{ $article->title }}</a>
                            </h2>
                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-ink-600">{{ $article->excerpt }}</p>
                            <div class="mt-auto pt-4 text-xs text-ink-500">
                                {{ $article->published_at?->format('j M Y') }}
                                @if($article->reading_minutes) · {{ $article->reading_minutes }} min read @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $articles->links() }}</div>
        @endif
    </div>
@endsection
