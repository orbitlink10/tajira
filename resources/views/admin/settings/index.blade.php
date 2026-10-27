@extends('layouts.admin')

@section('title', 'Business settings')
@section('heading', 'Business settings')
@section('subheading', 'Configurable placeholders must be replaced with approved business facts.')

@section('content')
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Payment credentials, database passwords and API secrets stay in environment variables. This screen stores non-secret business settings. Do not claim tax or eTIMS compliance unless the integration has been implemented and verified.
    </div>

    <form method="post" action="{{ route('admin.settings.update') }}" class="space-y-4">
        @csrf
        @foreach($groups as $group => $fields)
            <section class="card p-5">
                <h2 class="text-base font-bold text-ink-900">{{ $group }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach($fields as $key => $definition)
                        @php($value = $values[$key] ?? null)
                        <div class="{{ in_array($definition['type'], ['text'], true) ? 'sm:col-span-2' : '' }}">
                            <label for="setting-{{ str_replace('.', '-', $key) }}" class="field-label">{{ $definition['label'] }}</label>
                            @if($definition['type'] === 'boolean')
                                <input type="hidden" name="settings[{{ $key }}]" value="0">
                                <label class="flex items-center gap-2 text-sm">
                                    <input id="setting-{{ str_replace('.', '-', $key) }}" type="checkbox" name="settings[{{ $key }}]" value="1" class="h-4 w-4 rounded" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))>
                                    Enabled
                                </label>
                            @elseif($definition['type'] === 'text')
                                <textarea id="setting-{{ str_replace('.', '-', $key) }}" name="settings[{{ $key }}]" rows="3" class="field-input">{{ $value }}</textarea>
                            @elseif(in_array($definition['type'], ['integer', 'decimal'], true))
                                <input id="setting-{{ str_replace('.', '-', $key) }}" type="number" step="{{ $definition['type'] === 'decimal' ? '0.01' : '1' }}" name="settings[{{ $key }}]" value="{{ $value }}" class="field-input">
                            @else
                                <input id="setting-{{ str_replace('.', '-', $key) }}" name="settings[{{ $key }}]" value="{{ $value }}" class="field-input">
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
        <button class="btn btn-primary">Save settings</button>
    </form>
@endsection
