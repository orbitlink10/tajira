@extends('layouts.admin')

@section('title', 'Brands')
@section('heading', 'Brands')
@section('subheading', 'Brands are separate entities, not category branches.')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-2">
        <p class="text-sm text-ink-600">{{ $brands->count() }} brands</p>
        @can('catalogue.manage')<a href="{{ route('admin.brands.create') }}" class="btn btn-primary">New brand</a>@endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th>Website</th><th></th></tr></thead>
                <tbody>
                    @foreach($brands as $brand)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $brand->name }}</td>
                            <td class="text-xs text-ink-500">{{ $brand->slug }}</td>
                            <td>{{ $brand->products_count }}</td>
                            <td><span class="badge {{ $brand->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-600' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-xs">{{ $brand->website_url ? 'Yes' : '—' }}</td>
                            <td class="whitespace-nowrap">
                                @can('catalogue.manage')
                                    <a class="text-xs font-semibold text-brand-700" href="{{ route('admin.brands.edit', $brand) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.brands.destroy', $brand) }}" class="inline" onsubmit="return confirm('Delete this brand?');">
                                        @csrf @method('delete')
                                        <button class="ml-2 text-xs font-semibold text-red-700" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
