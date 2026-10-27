@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')
@section('heading', $page->exists ? 'Edit page' : 'New page')
@section('subheading', 'Published pages are server-rendered and indexable unless set otherwise.')

@section('content')
    <form method="post" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="grid gap-4 xl:grid-cols-[1fr_340px]">
        @csrf
        @if($page->exists) @method('put') @endif

        <div class="space-y-4">
            <section class="card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="field-label">Title</label>
                        <input id="title" name="title" value="{{ old('title', $page->title) }}" required class="field-input">
                    </div>
                    <div>
                        <label for="slug" class="field-label">Slug</label>
                        <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" class="field-input" placeholder="Generated from title">
                    </div>
                    <div>
                        <label for="template" class="field-label">Template</label>
                        <select id="template" name="template" class="field-input">
                            @foreach(['default' => 'Default', 'policy' => 'Policy', 'contact' => 'Contact', 'wide' => 'Wide'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('template', $page->template) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="excerpt" class="field-label">Excerpt / summary</label>
                        <textarea id="excerpt" name="excerpt" rows="2" class="field-input">{{ old('excerpt', $page->excerpt) }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="body" class="field-label">Body (HTML, sanitised)</label>
                        <textarea id="body" name="body" rows="18" class="field-input font-mono text-xs">{{ old('body', $page->body) }}</textarea>
                        <p class="mt-1 text-xs text-ink-500">Only approved HTML elements and attributes are retained. Scripts, forms and unsafe URLs are removed.</p>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Publication</h2>
                <div class="mt-3 space-y-3">
                    <div>
                        <label for="status" class="field-label">Status</label>
                        <select id="status" name="status" class="field-input">
                            @foreach(['draft', 'pending_review', 'published', 'archived'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $page->status) === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="published_at" class="field-label">Published at</label>
                        <input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}" class="field-input">
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_in_footer" value="1" class="h-4 w-4 rounded" @checked(old('show_in_footer', $page->show_in_footer))> Show in footer</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_indexable" value="1" class="h-4 w-4 rounded" @checked(old('is_indexable', $page->is_indexable))> Indexable</label>
                    <div><label for="position" class="field-label">Footer position</label><input id="position" name="position" type="number" min="0" value="{{ old('position', $page->position) }}" class="field-input"></div>
                </div>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">SEO</h2>
                <div class="mt-3 space-y-3">
                    <div><label for="meta_title" class="field-label">Meta title</label><input id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="field-input"></div>
                    <div><label for="meta_description" class="field-label">Meta description</label><textarea id="meta_description" name="meta_description" rows="3" class="field-input">{{ old('meta_description', $page->meta_description) }}</textarea></div>
                    <div><label for="canonical_override" class="field-label">Canonical override</label><input id="canonical_override" name="canonical_override" value="{{ old('canonical_override', $page->canonical_override) }}" class="field-input"></div>
                    <div><label for="robots" class="field-label">Robots override</label><input id="robots" name="robots" value="{{ old('robots', $page->robots) }}" class="field-input" placeholder="index,follow"></div>
                </div>
            </section>

            <button class="btn btn-primary w-full">Save page</button>
            @if($page->exists)
                <a href="{{ route('admin.revisions.index', ['type' => 'page', 'id' => $page->id]) }}" class="btn btn-secondary w-full">Revision history</a>
            @endif
            <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary w-full">Cancel</a>
        </aside>
    </form>
@endsection
