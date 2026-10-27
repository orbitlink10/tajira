@extends('layouts.storefront')

@section('content')
    <div class="container-page py-12">
        <div class="mx-auto max-w-md">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">Sign in</h1>
            <p class="mt-2 text-sm text-ink-600">Access your orders, wishlist and saved addresses.</p>

            <form method="post" action="{{ route('login.store') }}" class="card mt-6 space-y-4 p-6">
                @csrf
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus class="field-input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="field-label">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Forgot password?</a>
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="field-input">
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-ink-300 text-brand-700">
                    Keep me signed in
                </label>
                <button type="submit" class="btn btn-primary w-full py-3">Sign in</button>
            </form>

            <p class="mt-4 text-center text-sm text-ink-600">New to Tajira? <a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('register') }}">Create an account</a></p>
            <p class="mt-2 text-center text-xs text-ink-500"><a class="underline hover:text-brand-700" href="{{ route('admin.login') }}">Staff sign in</a></p>
        </div>
    </div>
@endsection
