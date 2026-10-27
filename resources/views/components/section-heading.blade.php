@props(['eyebrow' => null, 'title', 'description' => null, 'link' => null, 'linkLabel' => 'View all'])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="max-w-2xl">
        @if($eyebrow)
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">{{ $eyebrow }}</p>
        @endif
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $title }}</h2>
        @if($description)
            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $description }}</p>
        @endif
    </div>
    @if($link)
        <a href="{{ $link }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">{{ $linkLabel }} →</a>
    @endif
</div>
