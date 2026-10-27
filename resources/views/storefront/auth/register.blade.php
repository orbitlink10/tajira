@extends('layouts.storefront')

@section('content')
    <div class="container-page py-12">
        <div class="mx-auto max-w-md">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">Create an account</h1>
            <p class="mt-2 text-sm text-ink-600">Save addresses, track orders and keep a wishlist. You can still check out as a guest.</p>

            <form method="post" action="{{ route('register.store') }}" class="card mt-6 space-y-4 p-6">
                @csrf
                <div>
                    <label for="name" class="field-label">Full name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field-input">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="field-input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="field-label">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" class="field-input">
                    @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="field-label">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="field-input">
                    <p class="mt-1 text-xs text-ink-500">At least 10 characters with upper and lower case letters and a number.</p>
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field-input">
                </div>
                <label class="flex items-start gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="terms" value="1" required class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-700">
                    <span>I agree to the <a class="underline hover:text-brand-700" href="{{ route('pages.show', 'terms-and-conditions') }}">terms</a> and <a class="underline hover:text-brand-700" href="{{ route('pages.show', 'privacy-policy') }}">privacy policy</a>.</span>
                </label>
                @error('terms')<p class="field-error">{{ $message }}</p>@enderror
                <button type="submit" class="btn btn-primary w-full py-3">Create account</button>
            </form>

            <p class="mt-4 text-center text-sm text-ink-600">Already have an account? <a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('login') }}">Sign in</a></p>
        </div>
    </div>
@endsection
