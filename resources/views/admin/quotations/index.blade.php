@extends('layouts.admin')

@section('title', 'Quotations')
@section('heading', 'Quotations')
@section('subheading', 'Requests for pricing, projects and bulk orders.')

@section('content')
    <form method="get" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-[220px] flex-1">
            <label for="q" class="field-label">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" class="field-input" placeholder="Number, name, email or company">
        </div>
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input">
                <option value="">All</option>
                @foreach(['new', 'reviewed', 'quoted', 'accepted', 'declined', 'expired'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary">Apply</button>
        <a href="{{ route('admin.quotations.index') }}" class="text-sm text-ink-600 underline">Reset</a>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Number</th><th>Contact</th><th>Items</th><th>Status</th><th>Indicative total</th><th>Received</th><th></th></tr></thead>
                <tbody>
                    @forelse($quotations as $quotation)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $quotation->number }}</td>
                            <td>{{ $quotation->name }}@if($quotation->company)<span class="block text-xs text-ink-400">{{ $quotation->company }}</span>@endif<span class="block text-xs text-ink-500">{{ $quotation->email }}</span></td>
                            <td>{{ $quotation->items_count ?? $quotation->items()->count() }}</td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ $quotation->statusLabel() }}</span></td>
                            <td>{{ $quotation->formattedTotal() ?: '—' }}</td>
                            <td class="text-xs text-ink-500">{{ $quotation->created_at->diffForHumans() }}</td>
                            <td><a href="{{ route('admin.quotations.show', $quotation) }}" class="text-xs font-semibold text-brand-700">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No quotation requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $quotations->links() }}</div>
@endsection
