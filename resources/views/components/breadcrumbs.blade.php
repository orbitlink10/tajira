@props(['crumbs' => []])

@if(count($crumbs))
    <nav aria-label="Breadcrumb" class="text-sm">
        <ol class="flex flex-wrap items-center gap-1.5 text-ink-500">
            @foreach($crumbs as $index => $crumb)
                <li class="flex items-center gap-1.5">
                    @if(!$loop->first)
                        <svg class="h-3.5 w-3.5 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                    @endif
                    @if($loop->last)
                        <span class="max-w-[18rem] truncate font-medium text-ink-700" aria-current="page">{{ $crumb['name'] }}</span>
                    @else
                        <a class="hover:text-brand-700" href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
