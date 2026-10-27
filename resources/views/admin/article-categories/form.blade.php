@extends('layouts.admin')

@section('title', $category->exists ? 'Edit article category' : 'New article category')
@section('heading', $category->exists ? 'Edit article category' : 'New article category')

@section('content')
    <form method="post" action="{{ $category->exists ? route('admin.article-categories.update', $category) : route('admin.article-categories.store') }}" class="max-w-2xl space-y-4">
        @csrf
        @if($category->exists) @method('put') @endif
        <section class="card p-5 space-y-4">
            <div><label for="name" class="field-label">Name</label><input id="name" name="name" value="{{ old('name', $category->name) }}" required class="field-input"></div>
            <div><label for="slug" class="field-label">Slug</label><input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="field-input"></div>
            <div><label for="description" class="field-label">Description</label><textarea id="description" name="description" rows="4" class="field-input">{{ old('description', $category->description) }}</textarea></div>
            <div><label for="position" class="field-label">Position</label><input id="position" name="position" type="number" min="0" value="{{ old('position', $category->position) }}" class="field-input"></div>
        </section>
        <div class="flex gap-2"><button class="btn btn-primary">Save category</button><a href="{{ route('admin.article-categories.index') }}" class="btn btn-secondary">Cancel</a></div>
    </form>
@endsection
