{{-- Page title block. The actions slot holds the screen's single primary action (plus optional secondaries). --}}
@props(['title', 'subtitle' => null, 'eyebrow' => null, 'count' => null])
<div {{ $attributes->merge(['class' => 'ls-page-head']) }}>
    <div>
        @if ($eyebrow)<div class="ls-eyebrow">{{ $eyebrow }}</div>@endif
        <h1 class="ls-title">{{ $title }}@if (! is_null($count))<span class="ls-count">{{ $count }}</span>@endif</h1>
        @if ($subtitle)<div class="ls-subtitle">{{ $subtitle }}</div>@endif
    </div>
    @isset($actions)<div class="ls-actions">{{ $actions }}</div>@endisset
</div>
