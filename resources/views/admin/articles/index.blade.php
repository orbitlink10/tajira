@extends('layouts.admin')

@section('title', 'Articles and guides')
@section('heading', 'Articles and guides')
@section('subheading', 'Original editorial content and contextual product links.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-ink-600">{{ $articles->total() }} articles</p>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">New article</a>
    </div>

    <form method="get" class="card mb-4 flex flex-wrap gap-3 p-4">
        <input name="q" value="{{ request('q') }}" class="field-input max-w-xs" placeholder="Search title">
        <select name="status" class="field-input w-auto">
            <option value="">All statuses</option>
            @foreach(['draft', 'pending_review', 'published', 'archived'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Status</th><th>Published</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td class="max-w-sm"><span class="font-semibold text-ink-900">{{ $article->title }}</span><span class="block text-xs text-ink-400">/guides/{{ $article->slug }}</span></td>
                            <td>{{ $article->category?->name ?: '—' }}</td>
                            <td>{{ ucfirst($article->type) }}</td>
                            <td><span class="badge {{ $article->status === 'published' ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-700' }}">{{ ucwords(str_replace('_', ' ', $article->status)) }}</span></td>
                            <td class="text-xs text-ink-500">{{ $article->published_at?->format('j M Y') ?: '—' }}</td>
                            <td class="text-xs text-ink-500">{{ $article->updated_at->diffForHumans() }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" class="ml-2 text-xs font-semibold text-ink-500">Preview</a>
                                <form method="post" action="{{ route('admin.articles.destroy', $article) }}" class="inline" onsubmit="return confirm('Delete this article?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No articles yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $articles->links() }}</div>
@endsection
