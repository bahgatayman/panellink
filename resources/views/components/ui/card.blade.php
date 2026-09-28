{{-- Bordered panel. <x-ui.card title="…" flush><x-slot:actions>…</x-slot:actions>…</x-ui.card> --}}
@props(['title' => null, 'flush' => false])
<section {{ $attributes->merge(['class' => 'ls-card'.($flush ? ' ls-card--flush' : '')]) }}>
    @if ($title || isset($actions))
        <div class="ls-card-head">
            @if ($title)<h3 class="ls-card-title">{{ $title }}</h3>@endif
            @isset($actions)<div class="ls-actions">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="ls-card-body">{{ $slot }}</div>
</section>
