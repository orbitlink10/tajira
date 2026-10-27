@extends('layouts.admin')

@section('title', 'Redirects')
@section('heading', 'Redirects')
@section('subheading', 'Managed permanent redirects without chains or loops.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <form method="get" class="flex gap-2"><input name="q" value="{{ request('q') }}" class="field-input" placeholder="Search path"><button class="btn btn-secondary">Search</button></form>
        <a href="{{ route('admin.redirects.create') }}" class="btn btn-primary">New redirect</a>
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>From</th><th>To</th><th>Code</th><th>Hits</th><th>Active</th><th>Note</th><th></th></tr></thead>
                <tbody>
                    @forelse($redirects as $redirect)
                        <tr>
                            <td class="text-xs font-medium text-ink-900">{{ $redirect->from_path }}</td>
                            <td class="text-xs">{{ $redirect->status_code === 410 ? 'Gone (410)' : $redirect->to_path }}</td>
                            <td>{{ $redirect->status_code }}</td>
                            <td>{{ $redirect->hits }}</td>
                            <td>{{ $redirect->is_active ? 'Yes' : 'No' }}</td>
                            <td class="max-w-xs truncate text-xs text-ink-500">{{ $redirect->note }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.redirects.edit', $redirect) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                <form method="post" action="{{ route('admin.redirects.destroy', $redirect) }}" class="inline" onsubmit="return confirm('Delete this redirect?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No redirects. Slug changes create suggestions automatically.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $redirects->links() }}</div>
@endsection
