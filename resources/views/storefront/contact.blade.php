@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Contact', 'url' => route('contact')]]" />
    </div>

    <div class="container-page py-8">
        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Contact Tajira</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">Ask about a product, confirm compatibility, request a quotation or get help with an order. Include model numbers and quantities where possible.</p>

                <form method="post" action="{{ route('contact.send') }}" class="card mt-6 space-y-4 p-5">
                    @csrf
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="field-label">Your name</label>
                            <input id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required autocomplete="name" class="field-input">
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="field-label">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email" class="field-input">
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="field-label">Phone (optional)</label>
                            <input id="phone" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" autocomplete="tel" class="field-input">
                        </div>
                        <div>
                            <label for="subject" class="field-label">Subject (optional)</label>
                            <input id="subject" name="subject" value="{{ old('subject') }}" class="field-input" placeholder="Quotation, compatibility, order support">
                        </div>
                    </div>
                    <div>
                        <label for="message" class="field-label">Message</label>
                        <textarea id="message" name="message" rows="6" required class="field-input" placeholder="Tell us the models, quantities, location and timeline.">{{ old('message') }}</textarea>
                        @error('message')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Send message</button>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Contact details</h2>
                    <dl class="mt-3 space-y-3 text-sm">
                        @if(setting('business.phone'))
                            <div><dt class="text-xs text-ink-500">Phone</dt><dd><a class="font-medium text-brand-700 hover:text-brand-900" href="{{ tel_url() }}">{{ setting('business.phone') }}</a></dd></div>
                        @endif
                        @if(setting('business.email'))
                            <div><dt class="text-xs text-ink-500">Sales email</dt><dd><a class="font-medium text-brand-700 hover:text-brand-900" href="mailto:{{ setting('business.email') }}">{{ setting('business.email') }}</a></dd></div>
                        @endif
                        @if(setting('business.support_email'))
                            <div><dt class="text-xs text-ink-500">Support email</dt><dd><a class="font-medium text-brand-700 hover:text-brand-900" href="mailto:{{ setting('business.support_email') }}">{{ setting('business.support_email') }}</a></dd></div>
                        @endif
                        <div>
                            <dt class="text-xs text-ink-500">Location</dt>
                            <dd class="text-ink-800">
                                @if(setting('business.address_line')){{ setting('business.address_line') }}, @endif
                                {{ setting('business.city') }}, {{ setting('business.country', 'Kenya') }}
                            </dd>
                        </div>
                    </dl>

                    @if(setting('business.map_url'))
                        <a href="{{ setting('business.map_url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary mt-4 w-full">Open map</a>
                    @endif
                    @if(whatsapp_url())
                        <a href="{{ whatsapp_url('Hello Tajira, I would like some help.') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary mt-2 w-full">Chat on WhatsApp</a>
                    @endif
                </div>

                <div class="rounded-xl border border-ink-200 bg-ink-50 p-5">
                    <h2 class="text-sm font-bold text-ink-900">Faster for bulk requirements</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">Use the quotation form to send multiple models and quantities in one request.</p>
                    <a href="{{ route('quotes.create') }}" class="btn btn-secondary mt-3 w-full">Request a quotation</a>
                </div>
            </aside>
        </div>
    </div>
@endsection
