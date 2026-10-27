@extends('layouts.admin')

@section('title', 'Administrators')
@section('heading', 'Administrators')
@section('subheading', 'Least-privilege roles, active status and MFA.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <form method="get" class="flex flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" class="field-input" placeholder="Name or email">
            <select name="role" class="field-input w-auto">
                <option value="">All roles</option>
                @foreach(\App\Models\User::adminRoles() as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary">Filter</button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">New administrator</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>MFA</th><th>Active</th><th>Last login</th><th></th></tr></thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $user->name }}</td>
                            <td class="text-xs">{{ $user->email }}</td>
                            <td>{{ $user->roleLabel() }}</td>
                            <td><span class="badge {{ $user->mfa_enabled ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">{{ $user->mfa_enabled ? 'Enabled' : 'Not set' }}</span></td>
                            <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                            <td class="text-xs text-ink-500">{{ $user->last_login_at?->format('j M Y, H:i') ?: '—' }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                @if($user->is_active && $user->id !== auth()->id())
                                    <form method="post" action="{{ route('admin.users.deactivate', $user) }}" class="inline" onsubmit="return confirm('Deactivate this account?');">
                                        @csrf
                                        <button class="ml-2 text-xs font-semibold text-red-700">Deactivate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
