@php
    $messages = [];
    foreach (['success' => 'green', 'error' => 'red', 'info' => 'blue', 'warning' => 'amber'] as $key => $tone) {
        if (session($key)) {
            $messages[] = ['key' => $key, 'tone' => $tone, 'text' => session($key)];
        }
    }
    if ($errors->any()) {
        $messages[] = ['key' => 'error', 'tone' => 'red', 'text' => $errors->first()];
    }
@endphp

@foreach($messages as $message)
    <div x-data="{ show: true }" x-show="show" x-transition class="mb-3 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-sm
        @if($message['tone'] === 'green') border-emerald-200 bg-emerald-50 text-emerald-900
        @elseif($message['tone'] === 'red') border-red-200 bg-red-50 text-red-900
        @elseif($message['tone'] === 'amber') border-amber-200 bg-amber-50 text-amber-900
        @else border-sky-200 bg-sky-50 text-sky-900 @endif" role="status">
        <span class="flex-1">{{ $message['text'] }}</span>
        <button type="button" class="rounded p-0.5 opacity-70 hover:opacity-100" @click="show = false" aria-label="Dismiss message">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>
@endforeach
