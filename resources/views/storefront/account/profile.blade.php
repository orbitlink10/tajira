@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')], ['name' => 'Profile', 'url' => route('account.profile')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Profile and password</h1>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1 space-y-5">
                <section class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Profile details</h2>
                    <form method="post" action="{{ route('account.profile.update') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('put')
                        <div>
                            <label for="name" class="field-label">Full name</label>
                            <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="field-input">
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="field-label">Phone</label>
                            <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="field-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="email" class="field-label">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="field-input">
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-primary">Save profile</button>
                        </div>
                    </form>
                </section>

                <section class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Change password</h2>
                    <form method="post" action="{{ route('account.password.update') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('put')
                        <div class="sm:col-span-2">
                            <label for="current_password" class="field-label">Current password</label>
                            <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="field-input">
                            @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
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
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-primary">Change password</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
