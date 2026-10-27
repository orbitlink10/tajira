@extends('layouts.admin-auth')

@section('title', 'Sign in')
@section('content')
    <div class="rounded-xl border border-ink-200 bg-white p-6 shadow-xl">
        <h1 class="text-xl font-bold text-ink-900">Staff sign in</h1>
        <p class="mt-1 text-sm text-ink-600">Authorised administrators only. Activity is logged.</p>

        <form method="post" action="{{ route('admin.login.store') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field-input">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="field-input">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-700">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-ink-300 text-brand-700">
                Keep me signed in on this device
            </label>
            <button type="submit" class="btn btn-primary w-full py-2.5">Sign in</button>
        </form>
    </div>
@endsection
