@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="$crumbs" />
        <article class="mx-auto mt-6 max-w-3xl">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">{{ $page->title }}</h1>
            @if($page->excerpt)
                <p class="mt-3 text-lg leading-8 text-ink-600">{{ $page->excerpt }}</p>
            @endif
            <div class="prose-content mt-8">
                {!! $page->body !!}
            </div>
        </article>
    </div>
@endsection
