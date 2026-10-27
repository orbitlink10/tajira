@extends('layouts.admin')

@section('title', $item->exists ? 'Edit navigation item' : 'New navigation item')
@section('heading', $item->exists ? 'Edit navigation item' : 'New navigation item')

@section('content')
    <form method="post" action="{{ $item->exists ? route('admin.navigation.update', $item) : route('admin.navigation.store') }}" class="max-w-2xl space-y-4">
        @csrf
        @if($item->exists) @method('put') @endif
        <section class="card p-5 grid gap-4 sm:grid-cols-2">
            <div><label for="label" class="field-label">Label</label><input id="label" name="label" value="{{ old('label', $item->label) }}" required class="field-input"></div>
            <div><label for="url" class="field-label">URL</label><input id="url" name="url" value="{{ old('url', $item->url) }}" required class="field-input" placeholder="/pages/delivery"></div>
            <div><label for="location" class="field-label">Location</label>
                <select id="location" name="location" class="field-input">
                    @foreach(['header' => 'Header', 'footer' => 'Footer', 'utility' => 'Utility bar'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('location', $item->location) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="parent_id" class="field-label">Parent</label>
                <select id="parent_id" name="parent_id" class="field-input">
                    <option value="">Top level</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((int) old('parent_id', $item->parent_id) === $parent->id)>{{ $parent->label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="target" class="field-label">Target</label>
                <select id="target" name="target" class="field-input">
                    <option value="_self" @selected(old('target', $item->target) === '_self')>Same window</option>
                    <option value="_blank" @selected(old('target', $item->target) === '_blank')>New window</option>
                </select>
            </div>
            <div><label for="position" class="field-label">Position</label><input id="position" name="position" type="number" min="0" value="{{ old('position', $item->position) }}" class="field-input"></div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $item->is_active))> Active</label>
        </section>
        <div class="flex gap-2"><button class="btn btn-primary">Save item</button><a href="{{ route('admin.navigation.index') }}" class="btn btn-secondary">Cancel</a></div>
    </form>
@endsection
