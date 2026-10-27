@extends('layouts.admin')

@section('title', $page->exists ? 'Edit collection' : 'New collection')
@section('heading', $page->exists ? 'Edit collection' : 'New collection')
@section('subheading', 'Only create indexable collections with a real product selection and useful original content.')

@section('content')
    <form method="post" action="{{ $page->exists ? route('admin.seo.landing-pages.update', $page) : route('admin.seo.landing-pages.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if($page->exists) @method('put') @endif

        <section class="card p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label for="title" class="field-label">Title</label><input id="title" name="title" value="{{ old('title', $page->title) }}" required class="field-input"></div>
                <div><label for="slug" class="field-label">Slug</label><input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" class="field-input" placeholder="Generated from title"></div>
                <div><label for="type" class="field-label">Type</label>
                    <select id="type" name="type" class="field-input">
                        @foreach(['category' => 'Category collection', 'brand' => 'Brand collection', 'filter' => 'Specification collection'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $page->type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label for="category_id" class="field-label">Category (optional)</label>
                    <select id="category_id" name="category_id" class="field-input">
                        <option value="">Any category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $page->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label for="brand_id" class="field-label">Brand (optional)</label>
                    <select id="brand_id" name="brand_id" class="field-input">
                        <option value="">Any brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((int) old('brand_id', $page->brand_id) === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="intro" class="field-label">Original introduction (HTML, at least 40 characters)</label>
                    <textarea id="intro" name="intro" rows="7" required class="field-input font-mono text-xs">{{ old('intro', $page->intro) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="filters_json" class="field-label">Additional filters (JSON, optional)</label>
                    <textarea id="filters_json" name="filters_json" rows="4" class="field-input font-mono text-xs">{{ old('filters_json', $page->filters ? json_encode($page->filters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                    <p class="mt-1 text-xs text-ink-500">Example: {"attr":{"poe_standard":["802-3at-poe"]},"price_max":50000}. Invalid combinations can make the page empty and therefore unavailable.</p>
                </div>
                <div><label for="meta_title" class="field-label">Meta title</label><input id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="field-input"></div>
                <div><label for="meta_description" class="field-label">Meta description</label><input id="meta_description" name="meta_description" value="{{ old('meta_description', $page->meta_description) }}" class="field-input"></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $page->is_active))> Active</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_indexable" value="1" class="h-4 w-4 rounded" @checked(old('is_indexable', $page->is_indexable))> Indexable</label>
            </div>
        </section>

        <div class="flex gap-2"><button class="btn btn-primary">Save collection</button><a href="{{ route('admin.seo.landing-pages.index') }}" class="btn btn-secondary">Cancel</a></div>
    </form>
@endsection
