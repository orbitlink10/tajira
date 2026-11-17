@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Nested product categories and navigation.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 class="tajira-page-title">Categories</h2>
        @can('catalogue.manage')<a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Create New Category</a>@endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin tajira-category-table">
                <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Photo</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>
                                <span class="font-semibold text-ink-900">{{ $category->parent ? $category->parent->name.' / ' : '' }}{{ $category->name }}</span>
                                <small class="tajira-category-detail">{{ $category->products_count }} products / {{ $category->published_products_count }} live &middot; {{ $category->is_active ? 'Active' : 'Inactive' }}<br>{{ $category->show_in_menu ? 'In navigation' : 'Hidden from navigation' }} &middot; Position {{ $category->position }}</small>
                            </td>
                            <td class="text-xs text-ink-500">{{ $category->slug }}</td>
                            <td>
                                @if($category->image_path)
                                    <img class="tajira-category-image" src="{{ asset('storage/'.$category->image_path) }}" alt="{{ $category->name }}" loading="lazy">
                                @else
                                    <span class="tajira-category-image tajira-no-image">No Image</span>
                                @endif
                            </td>
                            <td><div class="tajira-category-actions">
                                <a class="tajira-preview" href="{{ route('categories.show', $category) }}" target="_blank" rel="noopener">Preview</a>
                                @can('catalogue.manage')
                                    <a class="tajira-update" href="{{ route('admin.categories.edit', $category) }}">Update</a>
                                    <form method="post" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?');">
                                        @csrf @method('delete')
                                        <button class="tajira-delete" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-sm text-ink-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
