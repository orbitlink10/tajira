@extends('layouts.admin')

@section('title', $user->exists ? 'Edit administrator' : 'New administrator')
@section('heading', $user->exists ? 'Edit administrator' : 'New administrator')

@section('content')
    <form method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-2xl space-y-4">
        @csrf
        @if($user->exists) @method('put') @endif
        <section class="card p-5 grid gap-4 sm:grid-cols-2">
            <div><label for="name" class="field-label">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required class="field-input"></div>
            <div><label for="email" class="field-label">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="field-input"></div>
            <div><label for="role" class="field-label">Role</label>
                <select id="role" name="role" class="field-input">
                    @foreach(\App\Models\User::adminRoles() as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="phone" class="field-label">Phone (optional)</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="field-input"></div>
            <div><label for="password" class="field-label">{{ $user->exists ? 'New password (optional)' : 'Password' }}</label><input id="password" type="password" name="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" class="field-input"><p class="mt-1 text-xs text-ink-500">At least 12 characters with mixed case, numbers and symbols.</p></div>
            <div><label for="password_confirmation" class="field-label">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="field-input"></div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $user->is_active ?? true))> Active</label>
        </section>
        <div class="flex gap-2"><button class="btn btn-primary">Save administrator</button><a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a></div>
    </form>
@endsection
