@props(['rating' => 0, 'count' => 0, 'showCount' => true])

@if($count > 0)
    <div class="flex items-center gap-1.5" aria-label="{{ number_format($rating, 1) }} out of 5 from {{ $count }} review{{ $count === 1 ? '' : 's' }}">
        <span class="flex text-sand-500" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
                <svg class="h-4 w-4 {{ $i <= round($rating) ? 'fill-current' : 'fill-ink-200' }}" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.77l-5.2 2.74.99-5.79-4.21-4.1 5.82-.85L10 1.5z"/></svg>
            @endfor
        </span>
        @if($showCount)
            <span class="text-xs text-ink-500">{{ number_format($rating, 1) }} ({{ $count }})</span>
        @endif
    </div>
@endif
