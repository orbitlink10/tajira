@extends('layouts.admin')

@section('title', $brand->exists ? 'Edit brand' : 'New brand')
@section('heading', $brand->exists ? 'Edit brand' : 'New brand')
@section('subheading', 'Brand pages can carry original introductions and verified product links.')

@section('content')
    <form method="post" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-4">
        @csrf
        @if($brand->exists) @method('put') @endif

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Basics</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" value="{{ old('name', $brand->name) }}" required class="field-input">
                </div>
                <div>
                    <label for="slug" class="field-label">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" class="field-input" placeholder="Generated if blank">
                </div>
                <div class="sm:col-span-2">
                    <label for="website_url" class="field-label">Manufacturer website (optional)</label>
                    <input id="website_url" name="website_url" value="{{ old('website_url', $brand->website_url) }}" class="field-input" placeholder="https://…">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="field-label">Short description (plain text)</label>
                    <textarea id="description" name="description" rows="3" class="field-input">{{ old('description', $brand->description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="intro" class="field-label">Brand introduction (HTML)</label>
                    <textarea id="intro" name="intro" rows="8" class="field-input font-mono text-xs">{{ old('intro', $brand->intro) }}</textarea>
                </div>
                <div>
                    <label for="logo" class="field-label">Logo (optional)</label>
                    <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="field-input">
                    @if($brand->logo_path)
                        <label class="mt-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_logo" value="1" class="h-4 w-4"> Remove existing logo</label>
                    @endif
                </div>
                <div>
                    <label for="position" class="field-label">Position</label>
                    <input id="position" type="number" name="position" min="0" value="{{ old('position', $brand->position ?? 0) }}" class="field-input">
                </div>
            </div>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">SEO and status</h2>
            <div class="mt-4 grid gap-4">
                <div>
                    <label for="meta_title" class="field-label">Meta title</label>
                    <input id="meta_title" name="meta_title" value="{{ old('meta_title', $brand->meta_title) }}" class="field-input">
                </div>
                <div>
                    <label for="meta_description" class="field-label">Meta description</label>
                    <textarea id="meta_description" name="meta_description" rows="2" class="field-input">{{ old('meta_description', $brand->meta_description) }}</textarea>
                </div>
                <div>
                    <label for="canonical_override" class="field-label">Canonical override (optional)</label>
                    <input id="canonical_override" name="canonical_override" value="{{ old('canonical_override', $brand->canonical_override) }}" class="field-input">
                </div>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $brand->is_active))> Active</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_indexable" value="1" class="h-4 w-4 rounded" @checked(old('is_indexable', $brand->is_indexable))> Indexable</label>
                </div>
            </div>
        </section>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save brand</button>
            <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
