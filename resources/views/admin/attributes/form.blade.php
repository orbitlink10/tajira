@extends('layouts.admin')

@section('title', $attribute->exists ? 'Edit attribute' : 'New attribute')
@section('heading', $attribute->exists ? 'Edit attribute' : 'New attribute')
@section('subheading', 'Only expose filters backed by verified structured values.')

@section('content')
    <form method="post" action="{{ $attribute->exists ? route('admin.attributes.update', $attribute) : route('admin.attributes.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if($attribute->exists) @method('put') @endif

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Definition</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" value="{{ old('name', $attribute->name) }}" required class="field-input" placeholder="PoE budget">
                </div>
                <div>
                    <label for="type" class="field-label">Type</label>
                    <select id="type" name="type" class="field-input">
                        @foreach(['select' => 'Select list', 'number' => 'Number', 'boolean' => 'Yes / No', 'text' => 'Free text'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $attribute->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="slug" class="field-label">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $attribute->slug) }}" class="field-input" placeholder="Generated if blank">
                </div>
                <div>
                    <label for="code" class="field-label">Code</label>
                    <input id="code" name="code" value="{{ old('code', $attribute->code) }}" class="field-input" placeholder="Generated if blank">
                </div>
                <div>
                    <label for="unit" class="field-label">Unit (optional)</label>
                    <input id="unit" name="unit" value="{{ old('unit', $attribute->unit) }}" class="field-input" placeholder="W, V, ports, mm">
                </div>
                <div>
                    <label for="group" class="field-label">Group (optional)</label>
                    <input id="group" name="group" value="{{ old('group', $attribute->group) }}" class="field-input" placeholder="Switching, Wireless, Fibre">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="field-label">Internal description</label>
                    <input id="description" name="description" value="{{ old('description', $attribute->description) }}" class="field-input">
                </div>
                <div>
                    <label for="position" class="field-label">Position</label>
                    <input id="position" type="number" name="position" min="0" value="{{ old('position', $attribute->position) }}" class="field-input">
                </div>
                <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_filterable" value="1" class="h-4 w-4 rounded" @checked(old('is_filterable', $attribute->is_filterable))> Show as filter</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_comparable" value="1" class="h-4 w-4 rounded" @checked(old('is_comparable', $attribute->is_comparable))> Show in comparison</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_searchable" value="1" class="h-4 w-4 rounded" @checked(old('is_searchable', $attribute->is_searchable))> Searchable</label>
                </div>
            </div>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Select values</h2>
            <p class="mt-1 text-xs text-ink-500">One value per line. Optionally add numeric value and unit after a pipe: <code>802.3af | 15.4 | W</code>. Renaming a value can detach it from existing products; review affected products afterwards.</p>
            <textarea name="values_text" rows="10" class="field-input mt-3 font-mono text-xs" @disabled($attribute->type !== 'select')>@php
                echo old('values_text', $attribute->values?->map(fn ($value) => $value->value.($value->numeric_value !== null ? ' | '.rtrim(rtrim($value->numeric_value, '0'), '.') : '').($value->unit ? ' | '.$value->unit : ''))->implode("\n"));
            @endphp</textarea>
        </section>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save attribute</button>
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
