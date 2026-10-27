@extends('layouts.admin')

@section('title', 'Navigation')
@section('heading', 'Navigation')
@section('subheading', 'Controlled link management for header, footer and utility areas.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-ink-600">{{ $items->count() }} items</p>
        <a href="{{ route('admin.navigation.create') }}" class="btn btn-primary">New item</a>
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Label</th><th>Location</th><th>Parent</th><th>URL</th><th>Position</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $item->label }}</td>
                            <td>{{ ucfirst($item->location) }}</td>
                            <td>{{ $item->parent?->label ?: '—' }}</td>
                            <td class="max-w-xs truncate text-xs text-ink-500">{{ $item->url }}</td>
                            <td>{{ $item->position }}</td>
                            <td>{{ $item->is_active ? 'Yes' : 'No' }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.navigation.edit', $item) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                <form method="post" action="{{ route('admin.navigation.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this navigation item?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No navigation items yet. The storefront header uses categories and brands from the catalogue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
