@extends('layouts.admin')

@section('title', 'Article categories')
@section('heading', 'Article categories')
@section('subheading', 'Group guides and buying advice.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-ink-600">{{ $categories->count() }} categories</p>
        <a href="{{ route('admin.article-categories.create') }}" class="btn btn-primary">New category</a>
    </div>
    <div class="card overflow-hidden">
        <table class="table-admin">
            <thead><tr><th>Name</th><th>Slug</th><th>Articles</th><th>Position</th><th></th></tr></thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td class="font-semibold text-ink-900">{{ $category->name }}</td>
                        <td class="text-xs text-ink-500">{{ $category->slug }}</td>
                        <td>{{ $category->articles_count }}</td>
                        <td>{{ $category->position }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('admin.article-categories.edit', $category) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                            <form method="post" action="{{ route('admin.article-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?');">
                                @csrf @method('delete')
                                <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-sm text-ink-500">No article categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
