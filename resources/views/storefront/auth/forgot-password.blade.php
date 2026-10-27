@extends('layouts.storefront')

@section('content')
    <div class="container-page py-12">
        <div class="mx-auto max-w-md">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">Reset your password</h1>
            <p class="mt-2 text-sm text-ink-600">Enter the email address on your account. If it is registered, we will send a reset link.</p>

            <form method="post" action="{{ route('password.email') }}" class="card mt-6 space-y-4 p-6">
                @csrf
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="field-input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary w-full py-3">Send reset link</button>
            </form>

            <p class="mt-4 text-center text-sm"><a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('login') }}">Back to sign in</a></p>
        </div>
    </div>
@endsection
