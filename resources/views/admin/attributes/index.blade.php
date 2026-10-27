@extends('layouts.admin')

@section('title', 'Attributes')
@section('heading', 'Attributes')
@section('subheading', 'Typed, structured specifications used for filtering and comparison.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-ink-600">{{ $attributes->count() }} attributes</p>
        @can('catalogue.manage')<a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">New attribute</a>@endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Name</th><th>Code</th><th>Type</th><th>Unit</th><th>Values</th><th>Used by</th><th>Filter</th><th>Compare</th><th></th></tr></thead>
                <tbody>
                    @forelse($attributes as $attribute)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $attribute->name }}</td>
                            <td class="text-xs text-ink-500">{{ $attribute->code }}</td>
                            <td>{{ ucfirst($attribute->type) }}</td>
                            <td>{{ $attribute->unit ?: '—' }}</td>
                            <td>{{ $attribute->values_count }}</td>
                            <td>{{ $attribute->product_values_count }}</td>
                            <td>{{ $attribute->is_filterable ? 'Yes' : 'No' }}</td>
                            <td>{{ $attribute->is_comparable ? 'Yes' : 'No' }}</td>
                            <td class="whitespace-nowrap">
                                @can('catalogue.manage')
                                    <a class="text-xs font-semibold text-brand-700" href="{{ route('admin.attributes.edit', $attribute) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.attributes.destroy', $attribute) }}" class="inline" onsubmit="return confirm('Delete this attribute?');">
                                        @csrf @method('delete')
                                        <button class="ml-2 text-xs font-semibold text-red-700" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-sm text-ink-500">No attributes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
