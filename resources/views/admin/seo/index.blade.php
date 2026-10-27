@extends('layouts.admin')

@section('title', 'SEO reports')
@section('heading', 'SEO diagnostics')
@section('subheading', 'Diagnostics and editorial checks, not guarantees of rankings or rich results.')

@section('content')
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            'Published products' => $metrics['published_products'],
            'Missing meta descriptions' => $metrics['missing_meta_description'],
            'Missing images' => $metrics['missing_images'],
            'Missing specifications' => $metrics['missing_specifications'],
            'Orphan products' => $metrics['orphan_products'],
            'Duplicate meta titles' => $metrics['duplicate_titles'],
            'Images missing alt text' => $metrics['missing_alt'],
            'Broken internal links' => $metrics['broken_internal_links'],
        ] as $label => $value)
            <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <form method="post" action="{{ route('admin.seo.audit-links') }}">@csrf<button class="btn btn-primary" type="submit">Run internal link audit</button></form>
        <a href="{{ route('sitemaps.index') }}" target="_blank" class="btn btn-secondary">View sitemap index</a>
        <a href="{{ route('robots') }}" target="_blank" class="btn btn-secondary">View robots.txt</a>
        <a href="{{ route('admin.redirects.index') }}" class="btn btn-secondary">Manage redirects</a>
        <a href="{{ route('admin.seo.landing-pages.index') }}" class="btn btn-secondary">Approved collections</a>
        @if($linkReport['checked_at'])<span class="text-xs text-ink-500">Link audit: {{ $linkReport['checked_at'] }}</span>@endif
    </div>

    @if(!empty($linkReport['issues']))
        <section class="card mt-4 overflow-hidden">
            <h2 class="border-b border-ink-200 px-4 py-3 text-sm font-bold text-ink-900">Broken internal links</h2>
            <div class="overflow-x-auto">
                <table class="table-admin">
                    <thead><tr><th>URL</th><th>Reason</th></tr></thead>
                    <tbody>
                        @foreach($linkReport['issues'] as $issue)
                            <tr><td class="text-xs">{{ $issue['url'] }}</td><td class="text-xs text-ink-600">{{ $issue['reason'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Products with missing descriptions</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse($problems['missing_meta'] as $product)
                    <li><a class="font-medium text-brand-700" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a> <span class="text-xs text-ink-400">{{ $product->sku }}</span></li>
                @empty
                    <li class="text-ink-500">None.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Products without images</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse($problems['missing_images'] as $product)
                    <li><a class="font-medium text-brand-700" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a> <span class="text-xs text-ink-400">{{ $product->sku }}</span></li>
                @empty
                    <li class="text-ink-500">None.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Products without specifications</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse($problems['missing_specs'] as $product)
                    <li><a class="font-medium text-brand-700" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a> <span class="text-xs text-ink-400">{{ $product->sku }}</span></li>
                @empty
                    <li class="text-ink-500">None.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Duplicate meta titles</h2>
            @forelse($duplicates as $group)
                <div class="mt-3 rounded-lg border border-ink-200 p-3">
                    <p class="text-xs font-semibold text-ink-900">{{ $group['meta_title'] }} ({{ $group['total'] }})</p>
                    <ul class="mt-1 space-y-1 text-xs">
                        @foreach($group['products'] as $product)
                            <li><a class="text-brand-700" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="mt-2 text-sm text-ink-500">No duplicate meta titles among published products.</p>
            @endforelse
        </section>
    </div>

    <div class="mt-4 card p-5">
        <h2 class="text-sm font-bold text-ink-900">Indexation and feed reminders</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ink-600">
            <li>Sitemaps include only published, indexable, successful canonical URLs.</li>
            <li>Filter and sort combinations are noindexed. Approved landing pages require genuine content and product selection.</li>
            <li>Merchant feeds use Tajira prices, stock, URLs and approved images only.</li>
            <li>Validate structured data after material catalogue changes; rich results are never guaranteed.</li>
        </ul>
    </div>
@endsection
