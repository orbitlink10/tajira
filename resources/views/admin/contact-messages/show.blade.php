@extends('layouts.admin')

@section('title', 'Contact message')
@section('heading', $contactMessage->subject ?: 'Contact message')
@section('subheading', $contactMessage->created_at->format('j F Y, H:i'))

@section('content')
    <div class="mb-4"><a href="{{ route('admin.contact-messages.index') }}" class="text-sm font-semibold text-brand-700">← All messages</a></div>

    <div class="grid gap-4 xl:grid-cols-[1fr_320px]">
        <section class="card p-5">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-ink-500">Name</dt><dd class="font-semibold">{{ $contactMessage->name }}</dd></div>
                <div><dt class="text-xs text-ink-500">Email</dt><dd><a class="text-brand-700" href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></dd></div>
                <div><dt class="text-xs text-ink-500">Phone</dt><dd>{{ $contactMessage->phone ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-500">Status</dt><dd>{{ ucfirst($contactMessage->status) }}</dd></div>
            </dl>
            <div class="mt-4 whitespace-pre-line rounded-lg bg-ink-50 p-4 text-sm leading-6 text-ink-700">{{ $contactMessage->message }}</div>
        </section>

        <aside class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Update status</h2>
            <form method="post" action="{{ route('admin.contact-messages.update', $contactMessage) }}" class="mt-3 space-y-3">
                @csrf
                <select name="status" class="field-input">
                    @foreach(['new', 'read', 'replied', 'closed'] as $status)
                        <option value="{{ $status }}" @selected($contactMessage->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary w-full">Save</button>
            </form>
            <a href="mailto:{{ $contactMessage->email }}?subject={{ rawurlencode('Re: '.($contactMessage->subject ?: 'Your enquiry')) }}" class="btn btn-secondary mt-3 w-full">Reply by email</a>
        </aside>
    </div>
@endsection
