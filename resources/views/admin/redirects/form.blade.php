@extends('layouts.admin')

@section('title', $redirect->exists ? 'Edit redirect' : 'New redirect')
@section('heading', $redirect->exists ? 'Edit redirect' : 'New redirect')

@section('content')
    <form method="post" action="{{ $redirect->exists ? route('admin.redirects.update', $redirect) : route('admin.redirects.store') }}" class="max-w-2xl space-y-4">
        @csrf
        @if($redirect->exists) @method('put') @endif
        <section class="card p-5 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="from_path" class="field-label">From path</label><input id="from_path" name="from_path" value="{{ old('from_path', $redirect->from_path) }}" required class="field-input" placeholder="/old-product-url"></div>
            <div class="sm:col-span-2"><label for="to_path" class="field-label">To path</label><input id="to_path" name="to_path" value="{{ old('to_path', $redirect->to_path) }}" class="field-input" placeholder="/products/new-product"></div>
            <div><label for="status_code" class="field-label">Status code</label>
                <select id="status_code" name="status_code" class="field-input">
                    <option value="301" @selected((int) old('status_code', $redirect->status_code) === 301)>301 Permanent</option>
                    <option value="302" @selected((int) old('status_code', $redirect->status_code) === 302)>302 Temporary</option>
                    <option value="410" @selected((int) old('status_code', $redirect->status_code) === 410)>410 Gone</option>
                </select>
            </div>
            <label class="flex items-center gap-2 self-end text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $redirect->is_active))> Active</label>
            <div class="sm:col-span-2"><label for="note" class="field-label">Internal note</label><input id="note" name="note" value="{{ old('note', $redirect->note) }}" class="field-input"></div>
        </section>
        <div class="flex gap-2"><button class="btn btn-primary">Save redirect</button><a href="{{ route('admin.redirects.index') }}" class="btn btn-secondary">Cancel</a></div>
    </form>
@endsection
