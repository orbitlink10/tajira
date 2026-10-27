@extends('layouts.admin')

@section('title', 'Approved collections')
@section('heading', 'Approved collections')
@section('subheading', 'Curated brand/category/filter pages with genuine content and products.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-ink-600">{{ $pages->total() }} collections</p>
        <a href="{{ route('admin.seo.landing-pages.create') }}" class="btn btn-primary">New collection</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Title</th><th>URL</th><th>Selection</th><th>Products</th><th>Indexable</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $page->title }}</td>
                            <td class="text-xs text-ink-500">/collections/{{ $page->slug }}</td>
                            <td class="text-xs">{{ $page->category?->name ?: 'Any category' }} · {{ $page->brand?->name ?: 'Any brand' }}</td>
                            <td class="text-xs">{{ $page->is_active ? 'Check live' : 'Inactive' }}</td>
                            <td>{{ $page->is_indexable ? 'Yes' : 'No' }}</td>
                            <td>{{ $page->is_active ? 'Yes' : 'No' }}</td>
                            <td class="whitespace-nowrap">
                                <a class="text-xs font-semibold text-brand-700" href="{{ route('admin.seo.landing-pages.edit', $page) }}">Edit</a>
                                <a class="ml-2 text-xs font-semibold text-ink-500" href="{{ route('collections.show', $page->slug) }}" target="_blank">View</a>
                                <form method="post" action="{{ route('admin.seo.landing-pages.destroy', $page) }}" class="inline" onsubmit="return confirm('Delete this collection?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No approved collections yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $pages->links() }}</div>
@endsection
