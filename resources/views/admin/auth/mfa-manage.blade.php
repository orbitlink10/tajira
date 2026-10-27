@extends('layouts.admin-auth')

@section('title', 'Security settings')
@section('content')
    <div class="rounded-xl border border-ink-200 bg-white p-6 shadow-xl">
        <h1 class="text-xl font-bold text-ink-900">Security settings</h1>
        <p class="mt-1 text-sm text-ink-600">Two-factor authentication is enabled for {{ $user->email }}.</p>

        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">Administrator MFA is active.</div>

        <details class="mt-5 rounded-lg border border-ink-200 p-4">
            <summary class="cursor-pointer text-sm font-semibold text-ink-800">Disable two-factor authentication</summary>
            <form method="post" action="{{ route('admin.mfa.disable') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="password" class="field-label">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="field-input">
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="code" class="field-label">Current authenticator code</label>
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required class="field-input">
                    @error('code')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-danger">Disable MFA</button>
            </form>
        </details>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary mt-5 w-full">Back to dashboard</a>
    </div>
@endsection
