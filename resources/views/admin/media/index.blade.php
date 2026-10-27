@extends('layouts.admin')

@section('title', 'Media')
@section('heading', 'Media library')
@section('subheading', 'Optimised WebP images with alt text.')

@section('content')
    <div class="grid gap-4 xl:grid-cols-[1fr_340px]">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @forelse($media as $medium)
                <article class="card overflow-hidden">
                    <img src="{{ $medium->url() }}" alt="{{ $medium->alt }}" width="{{ $medium->width }}" height="{{ $medium->height }}" loading="lazy" class="h-40 w-full bg-ink-50 object-contain">
                    <div class="p-3">
                        <p class="truncate text-xs font-medium text-ink-900">{{ $medium->title ?: $medium->filename }}</p>
                        <p class="truncate text-[11px] text-ink-500">{{ $medium->alt }}</p>
                        <p class="mt-1 text-[11px] text-ink-400">{{ $medium->width }}×{{ $medium->height }} · {{ number_format($medium->size / 1024) }} KB</p>
                        <form method="post" action="{{ route('admin.media.destroy', $medium) }}" class="mt-2" onsubmit="return confirm('Delete this media file?');">
                            @csrf @method('delete')
                            <button class="text-xs font-semibold text-red-700">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="col-span-full rounded-lg border border-dashed border-ink-300 bg-white p-8 text-sm text-ink-500">No media uploaded yet.</p>
            @endforelse
        </div>

        <aside class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Upload image</h2>
            <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                @csrf
                <div><label for="image" class="field-label">JPEG, PNG or WebP</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="field-input"></div>
                <div><label for="alt" class="field-label">Alt text</label><input id="alt" name="alt" required class="field-input"></div>
                <div><label for="title" class="field-label">Title (optional)</label><input id="title" name="title" class="field-input"></div>
                <button class="btn btn-primary w-full">Upload and optimise</button>
            </form>
            <p class="mt-3 text-xs text-ink-500">Uploads are converted to WebP and resized to a maximum of 1800px. SVG and animated formats are rejected.</p>
        </aside>
    </div>
    <div class="mt-6">{{ $media->links() }}</div>
@endsection
