{{--
    Button / link-button.
    <x-ui.button variant="primary|secondary|ghost|danger|danger-quiet" size="sm" icon="plus" href="…" arrow block>Label</x-ui.button>
    One primary per screen; label it with the outcome ("Collect EGP 1,948", not "Submit").
--}}
@props(['variant' => 'secondary', 'size' => null, 'icon' => null, 'href' => null, 'type' => 'button', 'block' => false, 'arrow' => false, 'iconOnly' => false])
@php
    $classes = collect([
        'ls-btn', 'ls-btn--'.$variant,
        $size === 'sm' ? 'ls-btn--sm' : null,
        $block ? 'ls-btn--block' : null,
        $iconOnly ? 'ls-btn--icon' : null,
    ])->filter()->implode(' ');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-ui.icon :name="$icon" />@endif
        @unless($iconOnly)<span>{{ $slot }}</span>@endunless
        @if ($arrow)<x-ui.icon name="arrow-right" class="ls-arrow" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-ui.icon :name="$icon" />@endif
        @unless($iconOnly)<span>{{ $slot }}</span>@endunless
        @if ($arrow)<x-ui.icon name="arrow-right" class="ls-arrow" />@endif
    </button>
@endif
