@props(['filters', 'filterGroups' => [], 'filterBrands' => [], 'prefix' => 'f'])

@php
    $action = url()->current();
    $clearUrl = $action;
@endphp

<form method="get" action="{{ $action }}" class="space-y-5" aria-label="Filter products">
    @if(!empty($filters['q']))
        <input type="hidden" name="q" value="{{ $filters['q'] }}">
    @endif

    <div>
        <label for="{{ $prefix }}-sort" class="field-label">Sort by</label>
        <select id="{{ $prefix }}-sort" name="sort" class="field-input">
            @foreach(['relevance' => 'Relevance', 'newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['sort'] ?? 'relevance') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <fieldset>
        <legend class="field-label">Availability</legend>
        <label class="flex items-center gap-2 text-sm text-ink-700">
            <input type="checkbox" name="availability" value="in_stock" class="h-4 w-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500" @checked(($filters['availability'] ?? '') === 'in_stock')>
            In stock only
        </label>
    </fieldset>

    <fieldset>
        <legend class="field-label">Price (KES)</legend>
        <div class="flex items-center gap-2">
            <label class="sr-only" for="{{ $prefix }}-price-min">Minimum price</label>
            <input id="{{ $prefix }}-price-min" type="number" name="price_min" min="0" step="1" inputmode="numeric" value="{{ $filters['price_min'] ?? '' }}" placeholder="Min" class="field-input">
            <span class="text-ink-400" aria-hidden="true">–</span>
            <label class="sr-only" for="{{ $prefix }}-price-max">Maximum price</label>
            <input id="{{ $prefix }}-price-max" type="number" name="price_max" min="0" step="1" inputmode="numeric" value="{{ $filters['price_max'] ?? '' }}" placeholder="Max" class="field-input">
        </div>
    </fieldset>

    @if($filterBrands->isNotEmpty())
        <fieldset>
            <legend class="field-label">Brand</legend>
            <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                @foreach($filterBrands as $brand)
                    <label class="flex items-start gap-2 text-sm text-ink-700">
                        <input
                            type="checkbox"
                            name="brand[]"
                            value="{{ $brand->slug }}"
                            class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                            @checked(in_array($brand->slug, (array) ($filters['brand'] ?? []), true))
                        >
                        <span class="flex-1">{{ $brand->name }} <span class="text-xs text-ink-400">({{ $brand->published_products_count }})</span></span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    @foreach($filterGroups as $group)
        @if($group['type'] === 'number' && ($group['min'] !== null || $group['max'] !== null))
            <fieldset>
                <legend class="field-label">{{ $group['name'] }}@if($group['unit']) ({{ $group['unit'] }})@endif</legend>
                <label class="sr-only" for="{{ $prefix }}-{{ $group['slug'] }}">Minimum {{ $group['name'] }}</label>
                <input
                    id="{{ $prefix }}-{{ $group['slug'] }}"
                    type="number"
                    name="attr_min[{{ $group['slug'] }}]"
                    step="any"
                    min="{{ $group['min'] }}"
                    max="{{ $group['max'] }}"
                    value="{{ $filters['attr_min'][$group['slug']] ?? '' }}"
                    placeholder="At least {{ rtrim(rtrim(number_format((float) $group['min'], 2, '.', ''), '0'), '.') }}"
                    class="field-input"
                >
                <p class="mt-1 text-xs text-ink-400">Available range: {{ $group['min'] }}–{{ $group['max'] }}</p>
            </fieldset>
        @elseif(!empty($group['values']))
            <fieldset>
                <legend class="field-label">{{ $group['name'] }}@if($group['unit']) ({{ $group['unit'] }})@endif</legend>
                <div class="max-h-48 space-y-2 overflow-y-auto pr-1">
                    @foreach($group['values'] as $value)
                        <label class="flex items-start gap-2 text-sm text-ink-700">
                            <input
                                type="checkbox"
                                name="attr[{{ $group['slug'] }}][]"
                                value="{{ $value['slug'] }}"
                                class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-700 focus:ring-brand-500"
                                @checked(in_array($value['slug'], (array) ($filters['attr'][$group['slug']] ?? []), true))
                            >
                            <span class="flex-1">{{ $value['label'] }} <span class="text-xs text-ink-400">({{ $value['count'] }})</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
    @endforeach

    <div class="flex flex-wrap gap-2 pt-1">
        <button type="submit" class="btn btn-primary flex-1">Apply filters</button>
        <a href="{{ $clearUrl }}" class="btn btn-secondary">Clear</a>
    </div>
</form>
