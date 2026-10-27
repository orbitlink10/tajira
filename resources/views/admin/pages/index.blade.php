@extends('layouts.admin')

@section('title', 'Pages')
@section('heading', 'Pages')
@section('subheading', 'Policies, company pages and controlled content.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-ink-600">{{ $pages->total() }} pages</p>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">New page</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Footer</th><th>Published</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $page->title }}</td>
                            <td class="text-xs text-ink-500">/pages/{{ $page->slug }}</td>
                            <td><span class="badge {{ $page->status === 'published' ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-700' }}">{{ ucwords(str_replace('_', ' ', $page->status)) }}</span></td>
                            <td>{{ $page->show_in_footer ? 'Yes' : 'No' }}</td>
                            <td class="text-xs text-ink-500">{{ $page->published_at?->format('j M Y') ?: '—' }}</td>
                            <td class="text-xs text-ink-500">{{ $page->updated_at->diffForHumans() }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" class="ml-2 text-xs font-semibold text-ink-500">Preview</a>
                                <form method="post" action="{{ route('admin.pages.destroy', $page) }}" class="inline" onsubmit="return confirm('Delete this page?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No pages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $pages->links() }}</div>
@endsection
