@extends('layouts.admin')

@section('title', 'Audit log')
@section('heading', 'Audit log')
@section('subheading', 'Administrative actions and account events. Sensitive values are redacted.')

@section('content')
    <form method="get" class="card mb-4 flex flex-wrap gap-2 p-4">
        <input name="event" value="{{ request('event') }}" class="field-input max-w-xs" placeholder="Event, e.g. product.updated">
        <input name="user" value="{{ request('user') }}" class="field-input max-w-xs" placeholder="User email">
        <button class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.audit.index') }}" class="self-center text-sm text-ink-600 underline">Reset</a>
    </form>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Time</th><th>User</th><th>Event</th><th>Subject</th><th>IP</th><th>Changes</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-ink-500">{{ $log->created_at->format('j M Y, H:i:s') }}</td>
                            <td class="text-xs">{{ $log->user?->name ?: 'System' }}<span class="block text-ink-400">{{ $log->user?->email }}</span></td>
                            <td class="font-mono text-xs">{{ $log->event }}</td>
                            <td class="text-xs">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                            <td class="text-xs">{{ $log->ip_address }}</td>
                            <td class="max-w-sm text-[11px] text-ink-500">
                                @if($log->new_values)
                                    <details><summary class="cursor-pointer">View payload</summary><pre class="mt-1 max-h-40 overflow-auto whitespace-pre-wrap break-all rounded bg-ink-50 p-2">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-sm text-ink-500">No audit entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
@endsection
