@extends('layouts.storefront')

@section('content')
    <div class="container-page py-12">
        <div class="mx-auto max-w-md">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">Choose a new password</h1>

            <form method="post" action="{{ route('password.update') }}" class="card mt-6 space-y-4 p-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="field-input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="field-label">New password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="field-input">
                    <p class="mt-1 text-xs text-ink-500">At least 10 characters with upper and lower case letters and a number.</p>
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field-input">
                </div>
                <button type="submit" class="btn btn-primary w-full py-3">Reset password</button>
            </form>
        </div>
    </div>
@endsection
