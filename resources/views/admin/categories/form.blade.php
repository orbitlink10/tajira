@extends('layouts.admin')

@section('title', $category->exists ? 'Edit category' : 'New category')
@section('heading', $category->exists ? 'Edit category' : 'New category')
@section('subheading', 'Category content is server-rendered and can be indexed independently.')

@section('content')
    <form method="post" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if($category->exists) @method('put') @endif

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Basics</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="field-input">
                </div>
                <div>
                    <label for="slug" class="field-label">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="field-input" placeholder="Generated from name if blank">
                </div>
                <div>
                    <label for="parent_id" class="field-label">Parent category</label>
                    <select id="parent_id" name="parent_id" class="field-input">
                        <option value="">Top level</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" @selected((int) old('parent_id', $category->parent_id) === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="short_name" class="field-label">Short name (optional)</label>
                    <input id="short_name" name="short_name" value="{{ old('short_name', $category->short_name) }}" class="field-input">
                </div>
                <div>
                    <label for="position" class="field-label">Position</label>
                    <input id="position" type="number" name="position" min="0" value="{{ old('position', $category->position ?? 0) }}" class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="field-label">Short description</label>
                    <textarea id="description" name="description" rows="3" class="field-input" placeholder="Plain text shown near the top of the category page.">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="buying_advice" class="field-label">Buying advice (HTML)</label>
                    <textarea id="buying_advice" name="buying_advice" rows="8" class="field-input font-mono text-xs">{{ old('buying_advice', $category->buying_advice) }}</textarea>
                    <p class="mt-1 text-xs text-ink-500">Sanitised against the approved HTML allowlist on save.</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="image_path" class="field-label">Image path (storage-relative, optional)</label>
                    <input id="image_path" name="image_path" value="{{ old('image_path', $category->image_path) }}" class="field-input" placeholder="categories/example.webp">
                </div>
            </div>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">SEO</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="meta_title" class="field-label">Meta title</label>
                    <input id="meta_title" name="meta_title" value="{{ old('meta_title', $category->meta_title) }}" maxlength="180" class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="meta_description" class="field-label">Meta description</label>
                    <textarea id="meta_description" name="meta_description" rows="2" maxlength="320" class="field-input">{{ old('meta_description', $category->meta_description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="canonical_override" class="field-label">Canonical override (optional)</label>
                    <input id="canonical_override" name="canonical_override" value="{{ old('canonical_override', $category->canonical_override) }}" class="field-input" placeholder="https://…">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $category->is_active))> Active</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_indexable" value="1" class="h-4 w-4 rounded" @checked(old('is_indexable', $category->is_indexable))> Indexable</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_in_menu" value="1" class="h-4 w-4 rounded" @checked(old('show_in_menu', $category->show_in_menu))> Show in menu</label>
            </div>
        </section>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save category</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
