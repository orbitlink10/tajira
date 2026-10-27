@extends('layouts.admin-auth')

@section('title', 'Set up two-factor authentication')
@section('content')
    <div class="rounded-xl border border-ink-200 bg-white p-6 shadow-xl">
        <h1 class="text-xl font-bold text-ink-900">Set up two-factor authentication</h1>
        <p class="mt-1 text-sm text-ink-600">Use any TOTP authenticator app. Add this secret manually or open the provisioning link on your device.</p>

        <div class="mt-4 rounded-lg border border-ink-200 bg-ink-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Secret key</p>
            <code class="mt-1 block break-all text-sm font-bold tracking-wider text-ink-900">{{ $secret }}</code>
            <a class="mt-2 inline-block text-xs font-semibold text-brand-700 underline" href="{{ $uri }}">Open provisioning URI</a>
        </div>

        <p class="mt-3 text-xs leading-5 text-ink-500">Store the secret securely. There are no recovery codes in this release; an administrator with server access can reset MFA using an artisan command if a device is lost.</p>

        <form method="post" action="{{ route('admin.mfa.enable') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="code" class="field-label">Enter a code to confirm</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="8" required class="field-input text-center text-xl tracking-[0.4em]">
                @error('code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full py-2.5">Enable MFA</button>
        </form>
    </div>
@endsection
