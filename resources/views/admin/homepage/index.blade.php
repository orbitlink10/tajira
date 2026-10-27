@extends('layouts.admin')

@section('title', 'Homepage')
@section('heading', 'Homepage sections')
@section('subheading', 'Controlled content blocks. Product lists come from published catalogue data.')

@section('content')
    <form method="post" action="{{ route('admin.homepage.update') }}" class="space-y-4">
        @csrf
        @foreach(['hero' => 'Hero', 'categories' => 'Categories', 'featured' => 'Featured products', 'guides' => 'Guides', 'quote' => 'Quotation band'] as $key => $label)
            @php($section = $sections->get($key))
            <section class="card p-5">
                <input type="hidden" name="sections[{{ $key }}][position]" value="{{ $section?->position ?? $loop->index }}">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-ink-900">{{ $label }}</h2>
                    <label class="flex items-center gap-2 text-xs text-ink-600"><input type="checkbox" name="sections[{{ $key }}][is_active]" value="1" class="h-4 w-4 rounded" @checked($section?->is_active ?? true)> Active</label>
                </div>
                <div class="mt-3 grid gap-3">
                    <div><label for="{{ $key }}-title" class="field-label">Title</label><input id="{{ $key }}-title" name="sections[{{ $key }}][title]" value="{{ old('sections.'.$key.'.title', $section?->title) }}" class="field-input"></div>
                    <div><label for="{{ $key }}-subtitle" class="field-label">Subtitle</label><input id="{{ $key }}-subtitle" name="sections[{{ $key }}][subtitle]" value="{{ old('sections.'.$key.'.subtitle', $section?->subtitle) }}" class="field-input"></div>
                    <div><label for="{{ $key }}-body" class="field-label">Body (HTML, sanitised — optional)</label><textarea id="{{ $key }}-body" name="sections[{{ $key }}][body]" rows="3" class="field-input font-mono text-xs">{{ old('sections.'.$key.'.body', $section?->body) }}</textarea></div>
                </div>
            </section>
        @endforeach
        <button class="btn btn-primary">Save homepage content</button>
    </form>
@endsection
