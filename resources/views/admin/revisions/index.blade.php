@extends('layouts.admin')

@section('title', 'Revision history')
@section('heading', 'Revision history')
@section('subheading', class_basename($model).': '.($model->name ?? $model->title ?? '#'.$model->id))

@section('content')
    <div class="mb-4">
        <a href="{{ $data['type'] === 'product' ? route('admin.products.edit', $model) : ($data['type'] === 'page' ? route('admin.pages.edit', $model) : route('admin.articles.edit', $model)) }}" class="text-sm font-semibold text-brand-700">← Back to editor</a>
    </div>

    <div class="space-y-3">
        @forelse($revisions as $revision)
            <article class="card p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ $revision->note ?: 'Revision' }}</p>
                        <p class="text-xs text-ink-500">{{ $revision->created_at->format('j M Y, H:i:s') }} @if($revision->user) · {{ $revision->user->name }} @endif</p>
                    </div>
                    <span class="badge bg-ink-100 text-ink-700">#{{ $revision->id }}</span>
                </div>
                <details class="mt-3">
                    <summary class="cursor-pointer text-xs font-semibold text-brand-700">View snapshot</summary>
                    <pre class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap break-all rounded bg-ink-50 p-3 text-[11px] text-ink-600">{{ json_encode($revision->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            </article>
        @empty
            <p class="rounded-lg border border-dashed border-ink-300 bg-white p-6 text-sm text-ink-500">No revisions have been recorded yet.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $revisions->links() }}</div>
@endsection
