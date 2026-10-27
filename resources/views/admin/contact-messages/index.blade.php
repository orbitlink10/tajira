@extends('layouts.admin')

@section('title', 'Contact messages')
@section('heading', 'Contact messages')
@section('subheading', 'Enquiries submitted through the storefront contact form.')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.contact-messages.index') }}" class="badge {{ !request('status') ? 'bg-ink-900 text-white' : 'bg-white border border-ink-200 text-ink-700' }}">All</a>
        @foreach(['new', 'read', 'replied', 'closed'] as $status)
            <a href="{{ route('admin.contact-messages.index', ['status' => $status]) }}" class="badge {{ request('status') === $status ? 'bg-ink-900 text-white' : 'bg-white border border-ink-200 text-ink-700' }}">{{ ucfirst($status) }}</a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>From</th><th>Subject</th><th>Status</th><th>Received</th><th></th></tr></thead>
                <tbody>
                    @forelse($messages as $message)
                        <tr>
                            <td>{{ $message->name }}<span class="block text-xs text-ink-400">{{ $message->email }}</span></td>
                            <td>{{ $message->subject ?: 'General enquiry' }}</td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ ucfirst($message->status) }}</span></td>
                            <td class="text-xs text-ink-500">{{ $message->created_at->diffForHumans() }}</td>
                            <td><a href="{{ route('admin.contact-messages.show', $message) }}" class="text-xs font-semibold text-brand-700">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-sm text-ink-500">No messages.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $messages->links() }}</div>
@endsection
