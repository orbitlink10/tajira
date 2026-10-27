@extends('layouts.admin')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Nested product categories and navigation.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-ink-600">{{ $categories->count() }} categories</p>
        @can('catalogue.manage')<a href="{{ route('admin.categories.create') }}" class="btn btn-primary">New category</a>@endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Navigation</th><th>Status</th><th>Position</th><th></th></tr></thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <span class="font-semibold text-ink-900">{{ $category->parent ? $category->parent->name.' / ' : '' }}{{ $category->name }}</span>
                                @if($category->image_path)<span class="ml-1 text-xs text-ink-400">image</span>@endif
                            </td>
                            <td class="text-xs text-ink-500">{{ $category->slug }}</td>
                            <td>{{ $category->products_count }} <span class="text-xs text-ink-400">/ {{ $category->published_products_count }} live</span></td>
                            <td>{{ $category->show_in_menu ? 'Menu' : 'Hidden' }}</td>
                            <td><span class="badge {{ $category->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-600' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $category->position }}</td>
                            <td class="whitespace-nowrap">
                                @can('catalogue.manage')
                                    <a class="text-xs font-semibold text-brand-700" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?');">
                                        @csrf @method('delete')
                                        <button class="ml-2 text-xs font-semibold text-red-700" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
