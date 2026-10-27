@extends('layouts.admin-auth')

@section('title', 'Two-factor authentication')
@section('content')
    <div class="rounded-xl border border-ink-200 bg-white p-6 shadow-xl">
        <h1 class="text-xl font-bold text-ink-900">Two-factor authentication</h1>
        <p class="mt-1 text-sm text-ink-600">Enter the six-digit code from your authenticator app.</p>

        <form method="post" action="{{ route('admin.mfa.challenge.verify') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="code" class="field-label">Authentication code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="8" required autofocus class="field-input text-center text-xl tracking-[0.4em]">
                @error('code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full py-2.5">Verify and continue</button>
            <a href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout').submit();" class="block text-center text-xs font-semibold text-ink-500 hover:text-brand-700">Cancel and sign out</a>
        </form>
        <form id="admin-logout" method="post" action="{{ route('admin.logout') }}" class="hidden">@csrf</form>
    </div>
@endsection
