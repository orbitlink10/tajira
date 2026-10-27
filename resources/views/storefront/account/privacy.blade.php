@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')], ['name' => 'Privacy', 'url' => route('account.privacy')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Privacy and your data</h1>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1 space-y-5">
                <section class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Access your data</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">Download a JSON copy of your account details, saved addresses, orders, reviews and wishlist.</p>
                    <a href="{{ route('account.privacy.export') }}" class="btn btn-secondary mt-3">Download my data</a>
                </section>

                <section class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Request account deletion</h2>
                    @if($user->deletion_requested_at)
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Request recorded {{ $user->deletion_requested_at->format('j F Y') }}. Our team will review it against accounting, tax and legal retention obligations, then confirm the outcome by email.
                        </div>
                    @else
                        <p class="mt-2 text-sm leading-6 text-ink-600">You can request deletion of your account and personal data. Some order and transaction records may need to be retained where required for accounting, tax, fraud prevention or legal obligations. We will explain what is retained when we process the request.</p>
                        <form method="post" action="{{ route('account.privacy.deletion') }}" class="mt-4 max-w-md space-y-3">
                            @csrf
                            <div>
                                <label for="password" class="field-label">Confirm your password</label>
                                <input id="password" type="password" name="password" required autocomplete="current-password" class="field-input">
                                @error('password')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <label class="flex items-start gap-2 text-sm text-ink-700">
                                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-700">
                                I understand that some transaction records may be retained where legally required.
                            </label>
                            <button type="submit" class="btn btn-danger">Request deletion</button>
                        </form>
                    @endif
                </section>

                <div class="rounded-lg border border-ink-200 bg-ink-50 p-4 text-xs leading-5 text-ink-600">
                    Analytics is optional and consent-based when enabled. We do not send names, emails, phone numbers or order notes to analytics tools.
                </div>
            </div>
        </div>
    </div>
@endsection
